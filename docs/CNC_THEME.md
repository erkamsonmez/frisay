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

## Marka paleti — Usta Rafı

- Antrasit: `#202A32` (üst menü, koyu banner, metin)
- Yeşil: `#A3CB64` (buton, vurgu, etkileşim)
- Kırık beyaz: `#F4F5F2` (sayfa arka planı)
- Beyaz: `#FFFFFF` (ürün kartları, formlar)
- Küçük metinlerde beyaz/kırık beyaz üzerinde açık yeşil yerine koyu yeşil kullanılır.

## Şema ile düzenlenen alanlar

- Yönetim paneli → Temalar → Usta Rafı → Özelleştir
- Marka sekmesi: renkler; yazı tipi; logo yükleme
- Ana Sayfa sekmesi: üst bant, slogan, banner başlığı ve açıklama, butonlar, avantaj alanları, kategori/ürün bloklarını göster/gizle, ürün yok mesajı, yardım bannerı
- Gelişmiş sekmesi: içerik genişliği
- Tüm metin alanları `themeOptions` üzerinden escaped Smarty olarak görüntülenir.
- Değişkenler `theme.schema.json` → `colors.css` → `cnc-industrial.css` şeklinde bağlanır.
- Yönetim panelinden düzenlenen `colors.css` ve `custom.css` sunucuda kalıcı volume'de saklanır; redeploy sonrası korunur.
- Yüklenen yeni logolar `img/branding/` üzerinden kalıcı volume'de saklanır; `SiteAssets` veritabanı ayarını burada tutar.

- Logo yükleme bölümüne CNC Industrial banner görseli eklendi. Bu görseli yükledikten sonra Ana Sayfa sekmesinden 'Yüklenen banner görselini kullan' ayarını etkinleştirebilirsiniz.
