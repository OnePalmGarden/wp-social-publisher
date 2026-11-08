# WP Social Publisher

WordPress yazıları için Twitter / X, LinkedIn, Facebook, Medium ve Substack paylaşım taslakları hazırlayan deneme eklentisi.

## Önemli: Bu bir önizleme sürümüdür

Sürüm 0.3.0 hesaplara bağlanmaz, paylaşım yapmaz ve otomatik gönderim kuyruğu çalıştırmaz. Plan tarihi yalnızca WordPress'te saklanır ve takvimde gösterilir. Hesaplar, otomatik paylaşım, şimdi paylaş ve kayıtlar sayfaları gelecekteki özellikler için bilgilendirme ekranlarıdır. Şifre veya erişim anahtarı istemezler.

## Kurulum ve güncelleme

1. GitHub deposunda **Code → Download ZIP** seçeneğiyle ZIP dosyasını indirin.
2. WordPress yönetiminde **Eklentiler → Yeni Eklenti Ekle → Eklenti Yükle** yolunu açın.
3. ZIP dosyasını seçip **Şimdi Yükle** düğmesine basın. Önceki sürüm yüklüyse, WordPress'in güncelleme onayı çıkarsa mevcut sürümü değiştirin.
4. Eklentiyi etkinleştirin. Yönetim panelinin sol menüsünde **Social Publisher** görünür.

Güncellemeden önce sitenizin yedeğini alın. Mevcut yazıların platform seçimleri, özel metinleri ve tarihleri korunur. WordPress ZIP'i önceki sürümle aynı eklenti olarak tanımazsa ikinci bir kopyayı etkinleştirmeyin: önceki kopyayı devre dışı bırakın ve sunucunuzun yedekleme/güncelleme yöntemini kullanın.

## Şablonlar ve yazı önizlemesi

**Social Publisher → Templates** ekranı üç bölümden oluşur: solda platform seçimi, ortada içerik/URL/etiket alanları ve sağda canlı örnek önizleme. Bir alanı seçip **Akıllı etiketleri göster** bölümünden bir yer tutucu ekleyin; yer tutucu imlecin olduğu konuma eklenir. Başlığın önüne emoji, açıklamaya ek bilgi veya etiketler alanına kendi hashtaglerinizi normal metin olarak yazabilirsiniz.

| Yer tutucu | Yazıdan alınan bilgi |
| --- | --- |
| `{title}` | Başlık |
| `{excerpt}` | Özet; WordPress'in varsayılan özet davranışı kullanılır |
| `{url}` | Yazı bağlantısı |
| `{tags}` | Yazı etiketleri; virgülle ayrılır, otomatik `#` eklenmez |
| `{site_title}` | WordPress site adı |

İçerik, URL ve etiket alanlarının boş olmayanları paylaşım metninde sırasıyla aralarında boş satır olacak şekilde birleştirilir. Şablon ekranındaki canlı önizleme örnek yazı verileri kullanır; gerçek yazıya ait önizleme yazı düzenleme ekranındaki taslak kutusunda görüntülenir.

Bir yazının düzenleme ekranındaki **Sosyal paylaşım taslağı** kutusunda hedef platformları seçin. Yazıya özel metin alanı boşsa o platformun varsayılan şablonu, doluysa bu özel metin kullanılır. Özel metin de aynı yer tutucuları destekler. Önizleme en son kaydedilen yazı bilgilerini gösterir; değişiklikleri kaydedip düzenleyiciyi yeniden açarak kontrol edin. Varsayılan şablon daha sonra değiştirilirse özel metni olmayan yazıların önizlemesi de değişir.

Yazının **öne çıkan görseli**, ileride gönderim için kullanılacak olası görsel kaynağıdır; bu sürümde görsel çekme, yükleme veya gönderme işlemi yapılmaz. Platformların bağlantı, görsel ve etiket kuralları farklıdır; gerçek entegrasyon eklendiğinde ayrıca doğrulanacaktır. Platform sırası Twitter / X, LinkedIn, Facebook, Medium ve Substack'tir. Önceki sürümdeki Instagram tercihi yeni sürüm arayüzünde gösterilmez; mevcut yazı verileri otomatik olarak silinmez.

## Planlar ve takvim

Yazı kutusundaki tarih ve saat seçili tüm platformlar için ortaktır ve WordPress'in **Ayarlar → Genel** ekranındaki saat dilimindedir. Yazıyı kaydettikten sonra tarihi **Social Publisher → WP Post Schedules** listesinde ve **Calendar** takviminde görebilirsiniz. Taslak yazılar ve platform seçilmemiş planlar da gösterilir. Tarih belirtmek veya yazıyı yayımlamak, bu deneme sürümünde hiçbir yere gönderim başlatmaz.

Kutuyu göremiyorsanız yazı düzenleyicisinin sağ üstündeki **Tercihler / Paneller** bölümünü kontrol edin. Menü ve şablon ayarları site yöneticileri tarafından kullanılabilir.

## Uyumluluk

PHP 8.0 ve WordPress 6.2 veya üzeri hedeflenmektedir. PHP 8.5.12 ve WordPress 7.1.4 kullandığınızı belirttiniz; bu ortamda uyumluluk henüz doğrulanmadı. Lütfen önce deneme sitesinde kullanın.
