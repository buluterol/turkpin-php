<?php

declare(strict_types=1);

namespace Turkpin\Tests;

use PHPUnit\Framework\TestCase;
use Turkpin\Client;
use Turkpin\OrderStatus;
use Turkpin\TurkpinException;

final class ClientTest extends TestCase
{
    /** @var array{url: string, body: string}[] */
    private array $sent = [];

    private function client(string $fixture): Client
    {
        $xml = file_get_contents(__DIR__ . '/fixtures/' . $fixture);
        return new Client('dealer@example.com', 's3cr<et', test: true, transport: function ($url, $body) use ($xml) {
            $this->sent[] = ['url' => $url, 'body' => $body];
            return $xml;
        });
    }

    private function sentParams(): \SimpleXMLElement
    {
        parse_str(end($this->sent)['body'], $form);
        return simplexml_load_string($form['DATA'])->params;
    }

    public function testRequestFormat(): void
    {
        $this->client('games.xml')->getGames();
        $this->assertSame(Client::TEST_URL, $this->sent[0]['url']);
        $p = $this->sentParams();
        $this->assertSame('epinOyunListesi', (string) $p->cmd);
        $this->assertSame('dealer@example.com', (string) $p->username);
        $this->assertSame('s3cr<et', (string) $p->password, 'special characters are escaped');
    }

    public function testGames(): void
    {
        $games = $this->client('games.xml')->getGames();
        $this->assertCount(2, $games);
        $this->assertSame(1, $games[0]->id);
        $this->assertSame('Game 1', $games[0]->name);
    }

    public function testProducts(): void
    {
        $products = $this->client('products.xml')->getProducts(1);
        $this->assertSame('1', (string) $this->sentParams()->oyunKodu);
        $this->assertCount(2, $products);
        [$a, $b] = $products;
        $this->assertSame(26278, $a->stock);
        $this->assertSame(0, $a->maxOrder);
        $this->assertSame('0.001', $a->price);
        $this->assertFalse($a->preOrder);
        $this->assertFalse($a->isTiered());
        $this->assertTrue($b->preOrder);
        $this->assertTrue($b->isTiered());
        $this->assertSame('25', $b->minBarem);
        $this->assertSame('1250', $b->maxBarem);
        $this->assertSame('0.01', $b->baremStep);
        $this->assertSame(5, $b->maxOrder);
    }

    public function testCreateOrder(): void
    {
        $order = $this->client('order_success.xml')->createOrder(1, 4, 2, character: 'Player1');
        $p = $this->sentParams();
        $this->assertSame('epinSiparisYarat', (string) $p->cmd);
        $this->assertSame('2', (string) $p->adet);
        $this->assertSame('Player1', (string) $p->character);
        $this->assertFalse(isset($p->pre_order));
        $this->assertSame('25031013105101', $order->orderNo);
        $this->assertFalse($order->isPending());
        $this->assertSame('0.001', $order->amount);
        $this->assertCount(1, $order->epins);
        $this->assertSame('S3XS-EEDY-APAE-62EM', $order->epins[0]->code);
    }

    public function testPreOrderWithBarem(): void
    {
        $order = $this->client('order_pending.xml')->createOrder(1, 4, preOrder: true, barem: '30.50');
        $p = $this->sentParams();
        $this->assertSame('true', (string) $p->pre_order);
        $this->assertSame('30.50', (string) $p->barem);
        $this->assertTrue($order->isPending());
        $this->assertSame([], $order->epins);
    }

    public function testApiErrorThrows(): void
    {
        try {
            $this->client('order_out_of_stock.xml')->createOrder(1, 2);
            $this->fail('expected exception');
        } catch (TurkpinException $e) {
            $this->assertSame('012', $e->apiCode);
            $this->assertSame('Urun stok yetersiz.', $e->getMessage());
        }
    }

    public function testAuthErrorThrows(): void
    {
        $this->expectException(TurkpinException::class);
        $this->expectExceptionMessage('Kullanici bulunamadi');
        $this->client('error_auth.xml')->getGames();
    }

    public function testInvalidXmlThrows(): void
    {
        $c = new Client('u', 'p', transport: fn () => '<html>blocked</html');
        $this->expectException(TurkpinException::class);
        $c->getBalance();
    }

    public function testOrderStatus(): void
    {
        $s = $this->client('status_completed.xml')->getOrderStatus('25031013105101');
        $this->assertSame('25031013105101', (string) $this->sentParams()->siparisNo);
        $this->assertSame(OrderStatus::COMPLETED, $s->status);
        $this->assertSame('0.0010', $s->amount);
        $this->assertSame('S3XS-EEDY-APAE-62EM', $s->epins[0]->code);

        $c = $this->client('status_cancelled.xml')->getOrderStatus('25030620073001');
        $this->assertSame(OrderStatus::CANCELLED, $c->status);
        $this->assertSame('Stok yok', $c->extra);
        $this->assertSame([], $c->epins);
    }

    public function testListOrders(): void
    {
        $list = $this->client('orders.xml')->listOrders(new \DateTimeImmutable('2026-01-01'), '2026-01-30');
        $p = $this->sentParams();
        $this->assertSame('2026-01-01', (string) $p->baslangicTarihi);
        $this->assertSame('2026-01-30', (string) $p->bitisTarihi);
        $this->assertCount(2, $list);
        $this->assertSame(OrderStatus::PROCESSING, $list[0]->status);
        $this->assertSame('999', $list[1]->stateCode);
        $this->assertSame('CANCELLATION REASON', $list[1]->extra);
    }

    public function testBalance(): void
    {
        $b = $this->client('balance.xml')->getBalance();
        $this->assertSame('98689.0000', $b->balance);
        $this->assertSame('1311.0000', $b->spending);
    }

    public function testLiveUrlByDefault(): void
    {
        $c = new Client('u', 'p', transport: function ($url) {
            $this->sent[] = ['url' => $url, 'body' => ''];
            return file_get_contents(__DIR__ . '/fixtures/balance.xml');
        });
        $c->getBalance();
        $this->assertSame(Client::LIVE_URL, $this->sent[0]['url']);
    }
}
