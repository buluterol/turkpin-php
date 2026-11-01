<?php

declare(strict_types=1);

namespace Turkpin;

final class OrderInfo
{
    /** @param Epin[] $epins */
    public function __construct(
        public readonly string $orderNo,
        /** One of the OrderStatus constants. */
        public readonly int $status,
        public readonly string $description,
        /** "000" normally, "999" when the order has a problem being handled by Turkpin. */
        public readonly string $stateCode,
        public readonly string $checkedAt,
        public readonly string $amount,
        /** Cancellation reason or problem note, if any. */
        public readonly string $extra,
        public readonly array $epins,
    ) {
    }
}
