# PrestaShop uyumlu WebService (FriSay geçiş katmanı)

Kargo / pazaryeri entegrasyon firmaları FriSay için ayrı API yazmıyor; PrestaShop WebService’i zaten bağlanmış durumda. Bu katman **mevcut `/api/v1` JSON Web API’sine dokunmadan** PrestaShop’un URL ve XML/JSON şeklini taklit eder.

> Kalıcı mimari bu değil. Asıl API `/api/v1`’dir. Bu uç, entegrasyonlar FriSay’i tanıyana kadar kullanılır.

Kök eleman bilinçli olarak `<prestashop>` kalır — istemciler bu adı arar.

---

## Adresler

| | |
|--|--|
| **Base** | `https://siteadresiniz.com/api/` |
| **Yerel** | `http://localhost/fshop/api/` |
| **Anahtar** | Admin → API’de oluşturulan aynı Web API key |

```
GET /api/orders?ws_key=YOUR_KEY
GET /api/orders/1453?ws_key=YOUR_KEY
GET /api/orders?ws_key=YOUR_KEY&display=full&limit=80&sort=[id_DESC]
GET /api/orders?ws_key=YOUR_KEY&date=1&filter[date_add]=[2024-01-01,2024-12-31]
GET /api/orders?ws_key=YOUR_KEY&output_format=JSON
```

Kimlik doğrulama (PrestaShop ile aynı):

- `?ws_key=...` (tarayıcı testi)
- HTTP Basic: kullanıcı = **32 karakterlik** PrestaShop anahtarı (Admin → API’de “Prapazar” satırı), şifre boş
- İsteğe bağlı: `X-API-Key` / `Authorization: Bearer`

Prapazar PrestaShop istemcisi anahtarı URL’ye koymaz; HTTP Basic gönderir. Anahtar **32 karakter** olmalı (PS standardı). Admin panelindeki uzun JSON anahtarının **ilk 32 karakteri** de kabul edilir.

Dil listesinde `id=1` her zaman **Türkçe** (`iso_code=tr`, ad: `Türkçe (Turkish)`). Prapazar bunu arar.

Prapazar ürün vitrin linki PrestaShop biçimindedir (`/index.php?id_product=13&rewrite=...&controller=product`). Mağaza bunu kendi SEO URL’sine 301 yönlendirir; `/kategori/slug-13` yapısı değişmez.

Web API kapalıysa veya key yoksa `401` döner (`WWW-Authenticate: Basic`).

---

## Kaynaklar

Sipariş detayındaki `xlink:href` değerleri ayrı kaynaklardır; istemci onları da çeker.

| Kaynak | GET liste / detay | Yazma |
|--------|-------------------|--------|
| `orders` | evet | `PUT` — `shipping_number`, `current_state`, `id_carrier` (`PUT /api/orders` gövdede `<id>`) |
| `addresses` | sipariş teslimat adresi (`id` = sipariş id); `id_state` = ilçe (`/api/states/{id}`) | — |
| `customers` | evet | — |
| `products` | evet | `POST` / `PUT` — ürün oluştur / güncelle (`PUT /api/products` gövdede `<id>`; PS `price` KDV hariç katalog fiyatı, specific_prices öncesi; FShop KDV dahil) |
| `categories` | evet | — |
| `manufacturers` | markalar | — |
| `carriers` | kargo firmaları | — |
| `order_states` | FriSay durum id’leri (1–7) | — |
| `order_histories` | evet (`id` = sipariş id) | `POST` — sipariş durumu |
| `order_carriers` | evet (`id` = sipariş id) | `POST` / `PUT` — `tracking_number`, `id_carrier` |
| `stock_availables` | evet (`id` = ürün id) | `PUT` — `quantity` |
| `specific_prices` | evet (`id` = ürün id) | `POST` / `PUT` — indirim katalog (liste) fiyatından bir kez uygulanır; amount KDV dahil |
| `combinations`, `product_option_values`, `product_features`, `tags` | evet (boş liste) | — |
| `languages`, `currencies`, `shops`, `countries`, `states` | evet | — |
| `taxes`, `tax_rule_groups`, `tax_rules` | evet (FriSay KDV oranları) | — |
| `images/products/{id}` | XML (görsel listesi; yoksa boş `declination`) | `POST` — multipart `image` / `file` |
| `images/products/{id}/{imageId}` | JPEG | `DELETE` — görseli sil |

Para birimi PrestaShop 1.7+ XML’idir: liste yalnızca `id` + `xlink:href`; detayda `names` / `symbol` / `numeric_iso_code` / `precision` (TRY = Türk Lirası, ₺, 949). KDV `taxes` tablosundan gelir; ürün `id_tax_rules_group` ilgili orana bağlanır.

Liste varsayılanı PrestaShop gibi yalnızca id + link:

```xml
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <orders>
    <order id="1" xlink:href="https://site/api/orders/1"/>
  </orders>
</prestashop>
```

`display=full` tam nesne (siparişte `associations/order_rows` dahil).

`GET /api/` indeksi PrestaShop gibi her kaynakta `description` + `schema=blank` / `schema=synopsis` döner. KDV kaynakları (`taxes`, `tax_rule_groups`, `tax_rules`) bu listede yer alır.

---

## Sipariş durumu (current_state)

WebService **PrestaShop 1.7 varsayılan id’lerini** döner. Prapazar “kargola” için `id_order_state=4` gönderir (Kargoya verildi). FriSay iç id’leri (1–7) API’de kullanılmaz.

| PS id | Ad | FriSay |
|-------|----|--------|
| 1 | Çek ile ödeme bekleniyor | Ödeme bekleniyor |
| 2 | Ödeme kabul edildi | Hazırlanıyor |
| 3 | Hazırlanıyor | Hazırlanıyor |
| 4 | Kargoya verildi (`shipped=1`) | Kargoya verildi |
| 5 | Teslim edildi | Teslim edildi |
| 6 | İptal edildi | İptal |
| 7 | İade edildi | İade |
| 8 | Ödeme hatası | Ödeme bekleniyor |
| 9 | Stokta yok (ödendi) | Hazırlanıyor |
| 10 | Havale ile ödeme bekleniyor | Ödeme bekleniyor |
| 11 | Uzaktan ödeme kabul edildi | Hazırlanıyor |
| 12 | Stokta yok (ödenmedi) | Ödeme bekleniyor |
| 13 | Kapıda ödeme bekleniyor | Ödeme bekleniyor |
| 14 | Beklemede | Hazırlanıyor |

Durum güncelleme (PrestaShop alışkanlığı):

```http
POST /api/order_histories?ws_key=KEY
Content-Type: text/xml
```

```xml
<prestashop>
  <order_history>
    <id_order>1453</id_order>
    <id_order_state>4</id_order_state>
  </order_history>
</prestashop>
```

Takip no:

```http
PUT /api/orders/1453?ws_key=KEY
```

```xml
<prestashop>
  <order>
    <id>1453</id>
    <shipping_number>2782599187840</shipping_number>
  </order>
</prestashop>
```

---

## Yetkiler

Aynı API key izinleri geçerli: sipariş okuma/yazma, ürün okuma, stok için ürün güncelleme, kategori/marka okuma.

Asıl JSON API: [WEBAPI.md](WEBAPI.md).

---

## Prapazar istek kaydı

Gelen her `/api/` isteği `logs/psws-requests.jsonl` dosyasına yazılır; Admin → API sayfasının altında görünür. Anahtarlar maskelenir. Prapazar ürün gönderimini bir kez çalıştırıp kayda bakın: `405` yazılamayan kaynak, `404` eksik kaynak, POST gövdesi ise yollanan XML’dir.
