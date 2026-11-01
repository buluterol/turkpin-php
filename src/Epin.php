<?php

declare(strict_types=1);

namespace Turkpin;

final class Epin
{
    public function __construct(
        public readonly string $code,
        public readonly string $description,
        public readonly string $id,
    ) {
    }
}
