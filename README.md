# turkpin-php

[![test](https://github.com/buluterol/turkpin-php/actions/workflows/test.yml/badge.svg)](https://github.com/buluterol/turkpin-php/actions/workflows/test.yml)

Turkpin bayi API'si için PHP istemcisi; Laravel ve Symfony entegrasyonu dahil: e-pin oyun ve ürün listesi, sipariş verme, sipariş durumu, sipariş listesi ve bakiye.

> Bu paket topluluk tarafından yazılmıştır; Turkpin'in resmi paketi değildir. API'nin güncel ve bağlayıcı tanımı için [dev.turkpin.com](https://dev.turkpin.com) dokümanına bakın.

Diğer diller / Other languages: [PHP](https://github.com/buluterol/turkpin-php) · [Node.js](https://github.com/buluterol/turkpin-node) · [Python](https://github.com/buluterol/turkpin-python) · [Go](https://github.com/buluterol/turkpin-go) · [Java](https://github.com/buluterol/turkpin-java) · [.NET](https://github.com/buluterol/turkpin-dotnet) · [Ruby](https://github.com/buluterol/turkpin-ruby) · [Rust](https://github.com/buluterol/turkpin-rust)

## Gereksinimler

PHP 8.1+ (ext-curl, ext-simplexml)

## Kurulum

```sh
composer require buluterol/turkpin:dev-main
```

Paket Packagist'te yayınlı değil; `composer.json` içine VCS deposu olarak ekleyin:

```json
"repositories": [{ "type": "vcs", "url": "https://github.com/buluterol/turkpin-php" }]
```

## Kullanım

```php
<?php
use Turkpin\Client;
use Turkpin\OrderStatus;
use Turkpin\TurkpinException;

$tp = new Client(getenv('TURKPIN_USER'), getenv('TURKPIN_PASS'), test: true);

foreach ($tp->getGames() as $game) {
    echo "{$game->id} {$game->name}\n";
}
$products = $tp->getProducts(gameId: 1);

try {
    $order = $tp->createOrder(gameId: 1, productId: $products[0]->id, quantity: 1);
    if ($order->isPending()) {
        $info = $tp->getOrderStatus($order->orderNo); // kodlar hazır olunca SIPARIS_DURUMU = 2
    }
    foreach ($order->epins as $epin) {
        echo $epin->code, "\n";
    }
} catch (TurkpinException $e) {
    echo "Hata {$e->apiCode}: {$e->getMessage()}\n";
}

echo $tp->getBalance()->balance;
```

## Laravel

Servis sağlayıcı ve `Turkpin` facade'ı otomatik keşifle yüklenir (Laravel 11+). `.env`:

```dotenv
TURKPIN_USERNAME=...
TURKPIN_PASSWORD=...
TURKPIN_TEST=true
```

Ayar dosyasını yayınlamak isterseniz: `php artisan vendor:publish --tag=turkpin-config`

```php
use Turkpin\Client;
use Turkpin\Laravel\Facades\Turkpin;

$games = Turkpin::getGames();

// ya da bağımlılık enjeksiyonuyla
public function store(Client $turkpin) { $order = $turkpin->createOrder(1, 4, 1); }
```

## Symfony

`config/bundles.php`:

```php
Turkpin\Symfony\TurkpinBundle::class => ['all' => true],
```

`config/packages/turkpin.yaml`:

```yaml
turkpin:
    username: '%env(TURKPIN_USERNAME)%'
    password: '%env(TURKPIN_PASSWORD)%'
    test: false
    timeout: 30
```

`Turkpin\Client` servisi autowire ile enjekte edilir (Symfony 6.4+).

## Desteklenen komutlar

| API komutu | Açıklama |
|---|---|
| `epinOyunListesi` | Oyun listesi |
| `epinUrunleri` | Bir oyunun e-pin ürünleri (stok, fiyat, min/max adet, ön sipariş, barem) |
| `epinSiparisYarat` | Sipariş verme (karakter adı, ön sipariş ve barem tutarı isteğe bağlı) |
| `siparisDurumu` | Sipariş durumu ve teslim edilen kodlar |
| `siparisListesi` | İki tarih arasındaki siparişler (en çok 30 gün) |
| `balance` | Bakiye, kredi, bonus ve harcama |

Listede olmayan komutlar için istemcideki genel `call` metodu kullanılabilir.

**Sipariş durumları (`SIPARIS_DURUMU`):** 1 işleniyor, 2 tamamlandı, 3 iptal, 99 teslimatta, 199 ön sipariş teslimatta. `DURUM_KODU` 999 ise Turkpin siparişle ilgili bir sorunu inceliyor demektir; iptal sebebi ya da not `EKSTRA` alanında gelir.

**Ön sipariş ve barem:** Ürünün `pre_order` alanı `true` ise sipariş ön sipariş olarak verilmelidir. Barem tutarlı ürünlerde tutar `min_barem` ile `max_barem` arasında, `barem_step` adımlarıyla seçilir. Bu siparişlerin sonucu `Pending` döner; kodlar için sipariş durumunu sorgulayın.

**Hatalar:** API `000` dışında bir hata kodu döndürdüğünde istemci, kodu ve açıklamayı taşıyan bir hata fırlatır. HTTP ya da XML hatalarında kod boştur.

## Test ortamı

- Test: `https://www.turkpin.net/api.php`, canlı: `https://www.turkpin.com/api.php`.
- Test ortamına erişmek için sunucu IP adresinizin Turkpin'e tanıtılması gerekir; tanıtılmamış IP'den gelen isteklere API `002` hata koduyla cevap verir. Test hesabı ve IP tanımı için [dev.turkpin.com](https://dev.turkpin.com) adresindeki iletişim bilgilerini kullanın.
- Kullanıcı adı ve şifreyi koda yazmayın; ortam değişkeni ya da gizli anahtar deposundan okuyun.

## Geliştirme

Testler gerçek API'ye bağlanmaz; örnek XML cevaplarıyla çalışır.

```sh
composer install
composer test
```

## English

PHP client for the Turkpin dealer API with Laravel and Symfony integration (e-pin games, products, orders, order status, order history, balance). Community-maintained, not an official Turkpin package; see [dev.turkpin.com](https://dev.turkpin.com) for the authoritative API reference. The sandbox (`turkpin.net`) only accepts requests from server IP addresses registered with Turkpin (otherwise error `002`). Install and usage are shown above; method names are in English, while API command and field names follow the original Turkish API.

## Lisans

MIT
