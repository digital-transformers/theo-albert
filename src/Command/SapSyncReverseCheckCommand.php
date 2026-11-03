<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SapSyncProjectionService;
use Pimcore\Model\DataObject\Frame;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sap-sync:reverse-check',
    description: 'Read-only comparison of Pimcore-derived SAP frame objects with an optional SAP JSON snapshot.',
)]
final class SapSyncReverseCheckCommand extends Command
{
    public function __construct(private readonly SapSyncProjectionService $projection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sap-json', null, InputOption::VALUE_REQUIRED, 'JSON array of live SAP frame objects keyed by itemCode')
            ->addOption('family', null, InputOption::VALUE_REQUIRED, 'Only check a Pimcore family code')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of frames', '0')
            ->addOption('details', null, InputOption::VALUE_NONE, 'Print every difference and non-derivable cause');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sap = $this->loadSapSnapshot($input->getOption('sap-json'));
        $familyFilter = trim((string) $input->getOption('family'));
        $limit = max(0, (int) $input->getOption('limit'));
        $details = (bool) $input->getOption('details');
        $counts = ['Match' => 0, 'Difference' => 0, 'Not derivable' => 0, 'Ready (no SAP snapshot)' => 0];

        $listing = new Frame\Listing();
        $listing->setUnpublished(true);
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');
        if ($limit > 0) {
            $listing->setLimit($limit);
        }

        foreach ($listing as $frame) {
            $result = $this->projection->project($frame);
            $expected = $result['expected'];
            if ($familyFilter !== '' && strcasecmp((string) $expected['family'], $familyFilter) !== 0) {
                continue;
            }

            $code = (string) $expected['itemCode'];
            if ($result['missing'] !== []) {
                ++$counts['Not derivable'];
                if ($details) {
                    $io->writeln(sprintf('%s #%d — Not derivable: %s', $code ?: '(no SAP code)', $frame->getId(), implode(', ', $result['missing'])));
                }
                continue;
            }
            if ($sap === null) {
                ++$counts['Ready (no SAP snapshot)'];
                continue;
            }
            if (!isset($sap[$code])) {
                ++$counts['Difference'];
                if ($details) {
                    $io->writeln(sprintf('%s — Difference: missing from SAP snapshot', $code));
                }
                continue;
            }

            $difference = $this->difference($expected, $sap[$code]);
            ++$counts[$difference === [] ? 'Match' : 'Difference'];
            if ($details && $difference !== []) {
                $io->writeln(sprintf('%s — Difference: %s', $code, implode(', ', $difference)));
            }
        }

        $io->table(['Status', 'Count'], array_map(static fn (string $status, int $count): array => [$status, $count], array_keys($counts), $counts));
        $io->note($sap === null
            ? 'No SAP snapshot was supplied: derivability was checked without changing data.'
            : 'The SAP snapshot comparison was read-only.');

        return $counts['Not derivable'] === 0 && $counts['Difference'] === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /** @return array<string, array<string, mixed>>|null */
    private function loadSapSnapshot(mixed $path): ?array
    {
        if (!is_string($path) || trim($path) === '') {
            return null;
        }
        $json = @file_get_contents($path);
        $rows = $json === false ? null : json_decode($json, true);
        if (!is_array($rows)) {
            throw new \RuntimeException(sprintf('Cannot read a JSON array from %s.', $path));
        }

        $index = [];
        foreach ($rows as $row) {
            if (is_array($row) && isset($row['itemCode']) && is_scalar($row['itemCode'])) {
                $index[(string) $row['itemCode']] = $row;
            }
        }

        return $index;
    }

    /** @param array<string, mixed> $expected @param array<string, mixed> $actual @return list<string> */
    private function difference(array $expected, array $actual): array
    {
        $keys = [];
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual) || $actual[$key] !== $value) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
