# CNC Industrial — FriSay 3.0.1 mağaza teması

## Kurulum ve önizleme

- Tema klasörü: templates/cnc-industrial/
- Esas alınan uyumlu tema: shopmore; orijinal temadan bağımsız kopyadır.
- Yönetim paneli ve e-ticaret çekirdeğine değişiklik yapılmaz.
- Coolify üzerinde staging dalından Redeploy yap.
- Geçici önizleme: https://<test-alan-adi>/?theme_preview=cnc-industrial
- Yönetim paneli → Temalar → CNC Industrial → Etkinleştir. Kodu deploy etmeden panelde temayı seçme.

## İlk sürüm

- Mobil ve masaüstü kurumsal üst menü, ürün arama ve dinamik kategoriler
- Teknik çizimden ilham alan SVG ana sayfa banner'ı
- Öne çıkan ürünler, fırsatlar ve kategori blokları
- Mevcut FriSay sepet, sipariş ve ürün varyasyon şablonları
- Responsive ürün kartları ve katalog CSS
- Gerçek ürün yoksa boş katalog mesajı

## Sıradaki modüller

- Kategori bazlı teknik ürün alanları (BT40, ER32, çap, radyüs, malzeme)
- Özellik bazlı filtreleme
- Türkiye kargo/ödeme/vergiler entegrasyonu ve canlı işlem testleri

## Bakım

- Site adı, logo ve ürünler yönetim panelinden gelir; sahte stok/fiyat yoktur.
- Tasarımın ana CSS dosyası: templates/cnc-industrial/css/cnc-industrial.css.
- Mevcut Shopmore ürün, sepet ve API işlevleri korunmuştur.
- Güncellemeler GitHub staging üzerinden yapılmalıdır.
