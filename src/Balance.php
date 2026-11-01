<?php

declare(strict_types=1);

namespace Turkpin;

final class Balance
{
    public function __construct(
        public readonly string $balance,
        public readonly string $credit,
        public readonly string $bonus,
        public readonly string $spending,
    ) {
    }
}
