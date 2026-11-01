<?php

declare(strict_types=1);

namespace Turkpin;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $stock,
        public readonly int $minOrder,
        /** 0 means no upper limit. */
        public readonly int $maxOrder,
        /** Unit price as a decimal string, e.g. "0.001". */
        public readonly string $price,
        public readonly string $taxType,
        public readonly bool $preOrder,
        /** Tiered (barem) products: allowed range and step, otherwise null. */
        public readonly ?string $minBarem = null,
        public readonly ?string $maxBarem = null,
        public readonly ?string $baremStep = null,
    ) {
    }

    public function isTiered(): bool
    {
        return $this->minBarem !== null;
    }
}
