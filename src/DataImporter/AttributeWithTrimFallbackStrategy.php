<?php
declare(strict_types=1);

namespace App\DataImporter;

use Pimcore\Bundle\DataImporterBundle\Resolver\Load\AttributeStrategy;
use Pimcore\Model\Element\ElementInterface;

final class AttributeWithTrimFallbackStrategy extends AttributeStrategy
{
    public function loadElementByIdentifier($identifier): ?ElementInterface
    {
        foreach ($this->identifierCandidates($identifier) as $candidate) {
            $element = parent::loadElementByIdentifier($candidate);
            if ($element instanceof ElementInterface) {
                return $element;
            }
        }

        return null;
    }

    /** @return list<mixed> */
    private function identifierCandidates(mixed $identifier): array
    {
        if (!is_string($identifier)) {
            return [$identifier];
        }

        $trimmed = trim($identifier);
        if ($trimmed === '' || $trimmed === $identifier) {
            return [$identifier];
        }

        return [$trimmed, $identifier];
    }
}
