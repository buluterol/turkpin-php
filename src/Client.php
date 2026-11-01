<?php

declare(strict_types=1);

namespace Turkpin;

/**
 * Turkpin dealer API client.
 *
 * Every call is an HTTP POST to api.php with one form field, DATA, holding
 * <APIRequest><params>...</params></APIRequest>. Responses are XML.
 * Documentation: https://dev.turkpin.com
 */
final class Client
{
    public const LIVE_URL = 'https://www.turkpin.com/api.php';
    public const TEST_URL = 'https://www.turkpin.net/api.php';

    /** @var callable(string $url, string $body, int $timeout): string */
    private $transport;

    private string $url;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        bool $test = false,
        private readonly int $timeout = 30,
        private readonly ?string $baseUrl = null,
        ?callable $transport = null,
    ) {
        $this->url = $baseUrl ?? ($test ? self::TEST_URL : self::LIVE_URL);
        $this->transport = $transport ?? self::curlTransport(...);
    }

    /** @return Game[] */
    public function getGames(): array
    {
        $p = $this->call('epinOyunListesi');
        $out = [];
        foreach ($p->oyunListesi->oyun ?? [] as $g) {
            $out[] = new Game((int) $g->id, (string) $g->name);
        }
        return $out;
    }

    /** @return Product[] */
    public function getProducts(int $gameId, ?int $productId = null): array
    {
        $params = ['oyunKodu' => $gameId];
        if ($productId !== null) {
            $params['urunKodu'] = $productId;
        }
        $p = $this->call('epinUrunleri', $params);
        $out = [];
        foreach ($p->epinUrunListesi->urun ?? [] as $u) {
            $out[] = new Product(
                id: (int) $u->id,
                name: (string) $u->name,
                stock: (int) $u->stock,
                minOrder: max(1, (int) $u->min_order),
                maxOrder: (int) $u->max_order,
                price: (string) $u->price,
                taxType: (string) $u->tax_type,
                preOrder: strtolower((string) $u->pre_order) === 'true',
                minBarem: self::opt($u->min_barem),
                maxBarem: self::opt($u->max_barem),
                baremStep: self::opt($u->barem_step),
            );
        }
        return $out;
    }

    /**
     * Places an e-pin order.
     *
     * @param string|null $character Buyer / character info, if the product needs it.
     * @param bool $preOrder Must be true for products listed with preOrder = true.
     * @param string|null $barem Amount for tiered products (between minBarem and maxBarem, in baremStep steps).
     */
    public function createOrder(
        int $gameId,
        int $productId,
        int $quantity = 1,
        ?string $character = null,
        bool $preOrder = false,
        ?string $barem = null,
    ): Order {
        $params = ['oyunKodu' => $gameId, 'urunKodu' => $productId, 'adet' => $quantity];
        if ($character !== null) {
            $params['character'] = $character;
        }
        if ($preOrder) {
            $params['pre_order'] = 'true';
        }
        if ($barem !== null) {
            $params['barem'] = $barem;
        }
        $p = $this->call('epinSiparisYarat', $params);
        return new Order(
            orderNo: (string) $p->siparisNo,
            result: (string) $p->siparisSonuc,
            amount: (string) $p->siparisTutari,
            epins: self::epins($p),
        );
    }

    public function getOrderStatus(string $orderNo): OrderInfo
    {
        return self::orderInfo($this->call('siparisDurumu', ['siparisNo' => $orderNo]));
    }

    /**
     * Lists orders between two dates (inclusive, at most 30 days apart).
     *
     * @return OrderInfo[]
     */
    public function listOrders(\DateTimeInterface|string $start, \DateTimeInterface|string $end): array
    {
        $fmt = static fn ($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : $d;
        $p = $this->call('siparisListesi', ['baslangicTarihi' => $fmt($start), 'bitisTarihi' => $fmt($end)]);
        $list = $p->SIPARISLER ?? null;
        $out = [];
        foreach ($list->SIPARIS ?? [] as $s) {
            $out[] = self::orderInfo($s);
        }
        return $out;
    }

    public function getBalance(): Balance
    {
        $b = $this->call('balance')->balanceInformation;
        return new Balance((string) $b->balance, (string) $b->credit, (string) $b->bonus, (string) $b->spending);
    }

    /**
     * Sends one command and returns the <params> element of the response.
     *
     * @param array<string, scalar> $params
     */
    public function call(string $cmd, array $params = []): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<APIRequest><params/></APIRequest>');
        $all = ['cmd' => $cmd, 'username' => $this->username, 'password' => $this->password] + $params;
        foreach ($all as $k => $v) {
            $xml->params->addChild($k, htmlspecialchars((string) $v, ENT_XML1));
        }
        $body = http_build_query(['DATA' => $xml->asXML()]);
        $raw = ($this->transport)($this->url, $body, $this->timeout);

        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string(trim($raw));
        libxml_use_internal_errors($prev);
        if ($doc === false || !isset($doc->params)) {
            throw new TurkpinException('Invalid response from Turkpin API: ' . substr($raw, 0, 200));
        }
        $p = $doc->params;
        $code = (string) ($p->error ?? '') ?: (string) ($p->HATA_NO ?? '');
        if ($code !== '' && $code !== '000') {
            $msg = (string) ($p->error_desc ?? '') ?: (string) ($p->HATA_ACIKLAMA ?? '') ?: 'Turkpin API error';
            throw new TurkpinException($msg, $code);
        }
        return $p;
    }

    private static function curlTransport(string $url, string $body, int $timeout): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($res === false) {
            throw new TurkpinException('HTTP request failed: ' . $err);
        }
        if ($status >= 400 && $status !== 401) {
            throw new TurkpinException("HTTP $status from Turkpin API");
        }
        return (string) $res;
    }

    private static function opt(?\SimpleXMLElement $e): ?string
    {
        return $e === null || trim((string) $e) === '' ? null : (string) $e;
    }

    /** @return Epin[] */
    private static function epins(\SimpleXMLElement $p): array
    {
        $out = [];
        foreach ($p->epin_list->epin ?? [] as $e) {
            $out[] = new Epin(trim((string) $e->code), (string) $e->desc, (string) $e->id);
        }
        return $out;
    }

    private static function orderInfo(\SimpleXMLElement $s): OrderInfo
    {
        return new OrderInfo(
            orderNo: (string) $s->SIPARIS_NO,
            status: (int) $s->SIPARIS_DURUMU,
            description: (string) $s->SIPARIS_DURUMU_ACIKLAMA,
            stateCode: (string) $s->DURUM_KODU,
            checkedAt: (string) $s->KONTROL_TARIHI,
            amount: (string) $s->SIPARIS_TUTARI,
            extra: (string) ($s->EKSTRA ?? '') ?: (string) ($s->EXTRA ?? ''),
            epins: self::epins($s),
        );
    }
}
