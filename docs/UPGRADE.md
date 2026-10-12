# FShop sürüm güncelleme

Kanal sabittir: **GitHub Releases** → [`bera65/frisay`](https://github.com/bera65/frisay). Kullanıcı ayarı yoktur.

Güncel paket: **`frisay-3.0.0.zip`** veya **`fs-3.0.0.zip`** (eski `fshop-*.zip` / `frisay-2.5.6.zip` de kabul edilir). Asset yoksa kaynak zipball kullanılır.

> WAMP notu: HTTPS için `core/cacert.pem` (Mozilla CA) pakette gelir; SSL hatası alırsanız bu dosyanın mevcut olduğundan emin olun.

---

## 3.0.0 — mağaza sahibi özeti

2.5.6’dan yükseltenler için görünür farklar:

- Admin paneli yenilendi (SaaS görünüm, filtre kartları, ürün listesinde aktif/pasif anahtar, ⋮ menü, sütun sıralama, stok kodu araması).
- Varsayılan vitrin teması `dress` klasöründen `default` oldu; `THEME=dress` otomatik `default` yapılır. ShopNova / Healt temaları pakette.
- **Tema Edit** (modül `theme4`): sitede tıklayarak düzenleme; container ile yan yana kategori / resim / yazı.
- Kargo / Prapazar için PrestaShop uyumlu WebService (`/api/orders?ws_key=`). JSON `/api/v1` değişmedi.

Yükseltmeden önce: DB + `config/env.php` + `img/` yedeği. Sonra Admin → Güncelleme → **Şimdi güncelle**.

---

## Mağaza sahibi (tek tık)

1. Yedek alın (DB + `config/env.php` + `img/`).
2. Admin → **Güncelleme** (menüde sarı rozet varsa yeni sürüm vardır).
3. **Şimdi güncelle** — paket iner, dosyalar kopyalanır (`env.php` / `img/` korunur), migration çalışır.

Admin paneli yaklaşık 12 saatte bir GitHub’ı sessizce kontrol eder; rozet otomatik çıkar. İsterseniz **Güncellemeleri denetle** ile anında kontrol edin.

Elle FTP attıysanız aynı ekranda **Veritabanı güncellemesini uygula** yeter.

2.5.6 → 3.0.0 duman testi (yayıncı): bir kopyada zip yükseltmesi, vitrin (`THEME=dress` remap), sepet/checkout, bir sanal pos, Prapazar ürün/sipariş.

---

## Siz (yayıncı): yeni sürüm nasıl çıkarılır?

Repo: https://github.com/bera65/frisay

1. Kodda `core/FShop.php` → `VERSION` artır (örn. `3.0.0`).
2. Gerekirse `upgrade/migrations/3.0.0.php` ekle (`up()` idempotent).
3. `CHANGELOG.md` güncelle (tarih, “unreleased” kalksın); `install/schema.sql` içindeki `FS_VERSION` seed’ini eşitle.
4. `php tools/build-release.php` ile ZIP üret (**kökte** `index.php`, `core/`, `templates/`…; `config/env.php` ve `img/products` eklenmez). Çıktı: `dist/frisay-{sürüm}.zip`
5. GitHub → **Releases** → **Draft a new release**
   - Tag: `3.0.0` veya `v3.0.0`
   - Asset olarak `frisay-3.0.0.zip` veya `fs-3.0.0.zip` yükle (tercih edilir)
   - Asset yoksa installer zipball kullanır (yine çalışır)
6. Release’i **Publish** et (draft kalırsa mağazalar görmez).

Mağazalar bir sonraki kontrolde (veya “Denetle” ile) sürümü görür.

### ZIP’e koymayın

- `config/env.php`, `config/installed.lock`
- `img/products`, `img/returns`, `img/invoices`, müşteri medyası
- `cache/` içeriği, `.git/`

## Korumalı yollar (kurulumda üzerine yazılmaz)

`config/env.php`, `config/installed.lock`, `img/`, `cache/`

## Teknik

| Parça | Dosya |
|--------|--------|
| Kanal | `UpdateChecker::GITHUB_REPO = bera65/frisay` |
| Kurulum | `core/UpdateInstaller.php` |
| Migration | `core/Upgrade.php` |
| Staging | `cache/updates/` |
