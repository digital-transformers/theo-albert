<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\PrestaShopExportConverter;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:prestashop-export:convert',
    description: 'Convert a PrestaShop JSON export into Pimcore Datahub importer payloads.',
)]
final class ConvertPrestaShopExportCommand extends Command
{
    public function __construct(private readonly PrestaShopExportConverter $converter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'Path to export.zip or an extracted export directory')
            ->addArgument('output', InputArgument::REQUIRED, 'Directory where converted JSON files are written')
            ->addOption(
                'parent-path',
                null,
                InputOption::VALUE_REQUIRED,
                'Pimcore parent folder used by the family importer',
                '/Imports/ProductHierarchy/Families'
            )
            ->addOption(
                'model-limit',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit conversion to the first N resolved models and their child frames'
            )
            ->addOption(
                'models',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated model codes or exact names to convert'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputPath = (string) $input->getArgument('input');
        $outputPath = rtrim((string) $input->getArgument('output'), '/');
        $parentPath = (string) $input->getOption('parent-path');
        $modelLimit = $this->modelLimit($input);
        $modelFilters = $this->modelFilters($input);

        try {
            $result = $this->converter->convert($inputPath, $parentPath, $modelLimit, $modelFilters);
            if (!is_dir($outputPath) && !mkdir($outputPath, 0775, true) && !is_dir($outputPath)) {
                throw new \RuntimeException(sprintf('Unable to create output directory: %s', $outputPath));
            }

            foreach (['families', 'models', 'frames', 'report'] as $name) {
                $this->writeJson($outputPath . '/' . $name . '.json', $result[$name]);
            }
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $summary = $result['report']['summary'];
        $io->success(sprintf(
            'Converted %d families, %d models, and %d frames.',
            $summary['output_family_records'],
            $summary['output_model_records'],
            $summary['output_frame_records']
        ));
        $io->writeln(sprintf('Output: %s', $outputPath));
        $io->writeln(sprintf(
            'Review report.json: %d model/family conflicts and %d duplicate product codes.',
            count($result['report']['model_family_conflicts']),
            count($result['report']['duplicate_product_codes'])
        ));

        return Command::SUCCESS;
    }

    private function modelLimit(InputInterface $input): ?int
    {
        $value = $input->getOption('model-limit');
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            throw new \InvalidArgumentException('--model-limit must be a positive integer.');
        }

        return (int) $value;
    }

    /**
     * @return list<string>
     */
    private function modelFilters(InputInterface $input): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', (string) $input->getOption('models'))),
            static fn (string $value): bool => $value !== ''
        ));
    }

    /**
     * @throws JsonException
     */
    private function writeJson(string $path, mixed $value): void
    {
        if (is_array($value) && array_is_list($value)) {
            $this->writeJsonList($path, $value);

            return;
        }

        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            throw new \RuntimeException(sprintf('Unable to write output file: %s', $path));
        }
    }

    /**
     * @param list<mixed> $values
     *
     * @throws JsonException
     */
    private function writeJsonList(string $path, array $values): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to write output file: %s', $path));
        }

        try {
            fwrite($handle, '[');
            foreach ($values as $index => $value) {
                $json = json_encode(
                    $value,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
                $indented = preg_replace('/^/m', '    ', $json);
                fwrite($handle, ($index === 0 ? "\n" : ",\n") . $indented);
            }
            fwrite($handle, $values === [] ? "]\n" : "\n]\n");
        } finally {
            fclose($handle);
        }
    }
}
