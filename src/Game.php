<?php

declare(strict_types=1);

namespace Turkpin;

final class Game
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
