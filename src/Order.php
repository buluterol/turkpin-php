<?php

declare(strict_types=1);

namespace Turkpin;

final class Order
{
    /** @param Epin[] $epins */
    public function __construct(
        public readonly string $orderNo,
        /** "Success" (codes delivered) or "Pending" (pre-order / tiered; poll getOrderStatus). */
        public readonly string $result,
        public readonly string $amount,
        public readonly array $epins,
    ) {
    }

    public function isPending(): bool
    {
        return $this->result !== 'Success';
    }
}
