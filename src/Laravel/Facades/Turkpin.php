<?php

declare(strict_types=1);

namespace Turkpin\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Turkpin\Client;

/**
 * @method static \Turkpin\Game[] getGames()
 * @method static \Turkpin\Product[] getProducts(int $gameId, ?int $productId = null)
 * @method static \Turkpin\Order createOrder(int $gameId, int $productId, int $quantity = 1, ?string $character = null, bool $preOrder = false, ?string $barem = null)
 * @method static \Turkpin\OrderInfo getOrderStatus(string $orderNo)
 * @method static \Turkpin\OrderInfo[] listOrders(\DateTimeInterface|string $start, \DateTimeInterface|string $end)
 * @method static \Turkpin\Balance getBalance()
 *
 * @see Client
 */
final class Turkpin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
