# WP Social Publisher

WordPress yazıları için Twitter / X, LinkedIn, Facebook, Medium ve Substack paylaşım taslakları hazırlayan deneme eklentisi.

## Önemli: Bu bir önizleme sürümüdür

Sürüm 0.4.0, Facebook Sayfasına tek tek ve açık onayla metin + bağlantı gönderme denemesi içerir. Otomatik gönderim kuyruğu çalıştırmaz; plan tarihi yalnızca WordPress'te saklanır ve takvimde gösterilir. Diğer platformlarla hesap bağlantısı veya gerçek gönderim henüz yoktur. Facebook bağlantısı, WordPress yönetiminde bir **Sayfa erişim belirteci** gerektirir; kişisel Facebook şifrenizi veya belirtecinizi sohbet üzerinden paylaşmayın.

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

Yazının **öne çıkan görseli** eklenti tarafından Facebook'a ayrı olarak yüklenmez; Facebook, bağlantı önizlemesini kendi kurallarına göre oluşturabilir. Platform sırası Twitter / X, LinkedIn, Facebook, Medium ve Substack'tir. Önceki sürümdeki Instagram tercihi yeni sürüm arayüzünde gösterilmez; mevcut yazı verileri otomatik olarak silinmez.

### X karakter sayacı

Yazının Twitter / X bölümünde tüm oluşturulmuş mesajın Unicode karakter sayısı ve **140 karakterlik kişisel hedef** gösterilir. Yazıya özel metni değiştirince sayaç ve o alanın önizlemesi canlı güncellenir; başlık, özet, bağlantı ve etiketler en son kaydedilmiş yazı bilgileridir. Kaydedip düzenleyiciyi yeniden açarak güncel yazı bilgileriyle kontrol edin.

Bu bir tahmindir: X, bağlantıları ve bazı Unicode karakterlerini farklı ağırlıklarla hesaplayabilir. Örneğin emojiler birden çok Unicode karakterinden oluşabilir. Gösterge, X'in gerçek kabul sınırını doğrulamaz ve hiçbir metni otomatik olarak kısaltmaz.

## Facebook Sayfasına deneme gönderisi

1. Meta for Developers'da bir uygulama oluşturun ve **Graph API Explorer** üzerinden o uygulamayı seçin. Meta arayüzü zamanla değişebilir.
2. Kendi kullanıcı hesabınız için gerekli Sayfa izinleriyle bir kullanıcı belirteci oluşturun. Meta, Sayfa listeleme ve gönderme için genellikle `pages_show_list`, `pages_read_engagement` ve `pages_manage_posts` izinlerini ve uygun uygulama/Sayfa rollerini gerektirir. Geliştirme modundaki uygulamalarda test hesabının uygulamada uygun rolü olmalıdır; başkalarının hesaplarına erişim için Meta incelemesi gerekebilir.
3. Explorer'da `GET /me/accounts?fields=id,name,access_token` isteğiyle yönettiğiniz Sayfaları listeleyin. İstediğiniz Sayfanın `id` ve `access_token` değerlerini kullanın. Bu yanıtta gizli belirteçler bulunur; ekran görüntüsünü veya değerleri kimseyle paylaşmayın.
4. HTTPS üzerinden WordPress yönetiminde **Social Publisher → Accounts** ekranına Sayfa kimliğini ve **Sayfa** erişim belirtecini girin. Uygulama belirtecin sahibinin Sayfa kimliğiyle eşleştiğini doğrular; bu kontrol gönderme iznini garanti etmez. Süresi biten veya iptal edilen belirteçler yeniden girilmelidir.
5. Şifresiz, herkese açık yayımlanmış bir yazı hazırlayın. Facebook özel metni boşsa Facebook şablonu kullanılır; doluysa özel metin kullanılır.
6. **Social Publisher → Share Now** ekranında yazıyı seçin. Gösterilen mesajı, bağlantıyı ve hedef Sayfayı kontrol edin. Yalnızca **Onayla ve Facebook’ta paylaş** düğmesi gerçek gönderim başlatır. Yazı seçimi, plan tarihi ve yazıyı yayımlamak kendiliğinden gönderim başlatmaz.

Bir onay tek kullanımlıktır; ağ hatası veya beklenmedik Meta yanıtı gönderimin sonucunu belirsiz bırakabilir. Tekrar denemeden önce mutlaka Facebook Sayfasını kontrol edin. Kalıcı gönderim geçmişi henüz yoktur. İlk sürüm bağlantısının gerçek Meta ortamında ve sitenizde çalışması ayrıca doğrulanmalıdır.

Belirteç veritabanında OpenSSL AES-256-GCM ile şifrelenir; anahtar WordPress güvenlik anahtarlarından türetilir. Bu, veritabanı yedeğinde düz metni gizler ancak sunucu/WordPress anahtarlarıyla birlikte ele geçirilmesini önlemez. Yönetici ve sunucu erişimini koruyun; WordPress anahtarları değişirse yeniden bağlayın. Bağlantıyı kaldırmak yerel belirteci siler; Meta'da yetkiyi ayrıca iptal etmek gerekebilir. OpenSSL ve HTTPS gereklidir.

## Planlar ve takvim

Yazı kutusundaki tarih ve saat seçili tüm platformlar için ortaktır ve WordPress'in **Ayarlar → Genel** ekranındaki saat dilimindedir. Yazıyı kaydettikten sonra tarihi **Social Publisher → WP Post Schedules** listesinde ve **Calendar** takviminde görebilirsiniz. Taslak yazılar ve platform seçilmemiş planlar da gösterilir. Tarih belirtmek veya yazıyı yayımlamak, bu deneme sürümünde hiçbir yere gönderim başlatmaz.

Kutuyu göremiyorsanız yazı düzenleyicisinin sağ üstündeki **Tercihler / Paneller** bölümünü kontrol edin. Menü ve şablon ayarları site yöneticileri tarafından kullanılabilir.

## Uyumluluk

PHP 8.0 ve WordPress 6.2 veya üzeri hedeflenmektedir. PHP 8.5.12 ve WordPress 7.1.4 kullandığınızı belirttiniz; bu ortamda uyumluluk henüz doğrulanmadı. Lütfen önce deneme sitesinde kullanın.
