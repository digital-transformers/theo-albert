<?php
declare(strict_types=1);

namespace App\Service;

final class AutomaticAssetMoveGuard
{
    private int $depth = 0;

    public function run(callable $operation): mixed
    {
        ++$this->depth;

        try {
            return $operation();
        } finally {
            --$this->depth;
        }
    }

    public function isActive(): bool
    {
        return $this->depth > 0;
    }
}
