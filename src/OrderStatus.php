<?php

declare(strict_types=1);

namespace Turkpin;

/** Values of SIPARIS_DURUMU returned by siparisDurumu and siparisListesi. */
final class OrderStatus
{
    public const PROCESSING = 1;
    public const COMPLETED = 2;
    public const CANCELLED = 3;
    public const DELIVERING = 99;
    public const PRE_ORDER_DELIVERING = 199;
}
