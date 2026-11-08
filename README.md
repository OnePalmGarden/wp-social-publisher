# WP Social Publisher

WordPress yazıları için Twitter / X, LinkedIn, Facebook, Medium ve Substack paylaşım taslakları hazırlayan eklenti.

## Bu sürümde

Sürüm 0.6.0; X ve LinkedIn kullanıcı hesapları için resmi OAuth 2.0 giriş ve profil doğrulaması sunar, Facebook Sayfası bağlantısı önceki Sayfa erişim belirteci formuyla sürer. **Share Now**, Facebook ve LinkedIn'e tekil gönderi için önce önizleme sonra ayrı bir kullanıcı onayı ister. LinkedIn gönderileri, onaydan sonra Posts API'ye `w_member_social` yetkisiyle gönderilir. Hesap bağlantısı, uygulamanın LinkedIn'de **Share on LinkedIn** ürün iznini veya gönderi yetkisini garanti etmez. Eklenti yazı yayımlanınca otomatik paylaşım yapmaz; planlanan tarihler yalnızca WordPress'te saklanır ve takvimde listelenir.

**Logs** en son 200 Share Now gönderim girişimini platform, yazı, sonuç ve platform gönderi kimliğiyle listeler. Paylaşım metni ve erişim belirteçleri kayıtlara yazılmaz. Ağ hatalarında platform gönderimi almış olabilir; tekrar denemeden önce hedef hesabı kontrol edin.

Medium ve Substack için şu an desteklenen resmi OAuth bağlantısı bulunmadığından eklenti parola istemez veya tarayıcı otomasyonu kullanmaz. İleride platformların desteklediği resmi bir yayınlama yöntemi sunulursa bu alan güncellenebilir.

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

Yazı kutusundaki yazıya özel metin, varsayılan şablonun tamamının yerine kullanılır. Özel metni boş bırakırsanız varsayılan şablon kullanılır. Özel metin de aynı yer tutucuları destekler.

Yazının **öne çıkan görseli** eklenti tarafından Facebook'a ayrı olarak yüklenmez; Facebook, bağlantı önizlemesini kendi kurallarına göre oluşturabilir. Platform sırası Twitter / X, LinkedIn, Facebook, Medium ve Substack'tir. Önceki sürümdeki Instagram tercihi yeni sürüm arayüzünde gösterilmez; mevcut yazı verileri otomatik olarak silinmez.

## X karakter sayacı

Yazının Twitter / X bölümünde tüm oluşturulmuş mesajın Unicode karakter sayısı ve **140 karakterlik kişisel hedef** gösterilir. Yazıya özel metni değiştirince sayaç ve o alanın önizlemesi canlı güncellenir; başlık, özet, bağlantı ve etiketler en son kaydedilmiş yazı bilgileridir. Kaydedip düzenleyiciyi yeniden açarak güncel yazı bilgileriyle kontrol edin.

Bu bir tahmindir: X, bağlantıları ve bazı Unicode karakterlerini farklı ağırlıklarla hesaplayabilir. Gösterge, X'in gerçek kabul sınırını doğrulamaz ve hiçbir metni otomatik olarak kısaltmaz.

## Hesapları bağlama ve bağlantıyı test etme

**Social Publisher → Accounts** ekranında soldan Twitter / X, LinkedIn veya Facebook seçin. Tüm parola, gizli anahtar ve belirteç girişleri yalnızca HTTPS üzerindeki WordPress yönetim panelinde yapılmalıdır. Bunları sohbet, e-posta, ekran görüntüsü veya genel depoda paylaşmayın.

### Twitter / X

1. X Developer Portal'da uygulama oluşturun ve **OAuth 2.0 Authorization Code Flow with PKCE** kullanan **Web App / Confidential Client** yapılandırın.
2. Eklentide Twitter / X hesabını seçin. Gösterilen **OAuth Redirect URI** adresini X uygulamasındaki callback listesine aynen ekleyin.
3. X'in verdiği **Client ID** ve **Client Secret** değerlerini eklentiye girip kaydedin.
4. **Twitter / X ile güvenli giriş** düğmesine basıp X'te yetkilendirin. Dönüşte kullanıcı profili doğrulanır ve hesap WordPress'e kaydedilir.
5. **Bağlantıyı test et** düğmesi profili yeniden API'den okur. Bu sürümde X'e gönderi yayınlanmaz.

Eklenti `users.read`, `tweet.read`, `tweet.write` ve `offline.access` izinlerini ister; API erişim planı, hesap ve gönderi yazma hakları X tarafından belirlenir. Giriş ve profil testi başarılı olsa bile gönderi izni garanti edilmez.

### LinkedIn

1. LinkedIn Developer Portal'da uygulama oluşturun ve **Sign In with LinkedIn using OpenID Connect** ürününü etkinleştirin.
2. Eklentide LinkedIn'i seçin ve gösterilen **OAuth Redirect URI** adresini uygulamanızdaki Authorized redirect URLs listesine aynen ekleyin.
3. LinkedIn'in verdiği **Client ID** ve **Client Secret** değerlerini eklentiye girip kaydedin.
4. **LinkedIn ile güvenli giriş** düğmesine basıp yetkilendirin. Dönüşte OpenID Connect kullanıcı profili doğrulanır.
5. **Bağlantıyı test et** düğmesi hesabı yeniden kontrol eder. Gönderi paylaşmak için LinkedIn'in ayrı **Share on LinkedIn** ürün izni gerekir; erişim LinkedIn onayına bağlıdır.

Bağlı hesaba deneme gönderisi için **Share Now** sayfasında LinkedIn'i ve yayımlanmış, şifresiz bir yazıyı seçin. Önizlemeyi inceleyin; gönderi ancak **Onayla ve LinkedIn'de paylaş** onayıyla yayımlanır. Yazı bağlantısı gönderi metnine eklenir. Başarı ve API hataları **Logs** sayfasına kaydedilir. LinkedIn uygulaması `w_member_social` iznine ve ürün erişimine sahip değilse LinkedIn isteği reddeder; bağlantı testi bu izni test etmez.

### Facebook Sayfası

Meta Graph API Explorer ile uygulamanız için oluşturduğunuz **Sayfa erişim belirtecini** ve Sayfa kimliğini girin. Uygulama belirtecin sahibinin Sayfa kimliğiyle eşleştiğini doğrular; bu kontrol gönderme iznini garanti etmez. Facebook bağlantısı, WordPress veritabanında OpenSSL AES-256-GCM ile şifrelenir. Anahtar WordPress güvenlik anahtarlarından türetilir; bu anahtarlar değişirse hesabı yeniden bağlayın.

Facebook deneme gönderisi için **Share Now** sayfasında yayımlanmış ve şifresiz bir yazı seçin, önizlemeyi inceleyin ve yalnızca **Onayla ve Facebook’ta paylaş** düğmesine basın. Tek onay tek kullanımlıktır. Ağ hatası veya beklenmedik Meta yanıtı sonucu belirsiz bırakabilir; yeniden denemeden önce Sayfayı kontrol edin.

### Bağlantıyı kaldırma ve güvenlik

OAuth Client Secret ve erişim/yenileme belirteçleri veritabanında şifrelenir. Şifreleme anahtarı WordPress güvenlik anahtarlarına bağlı olduğundan `wp-config.php` anahtarlarını koruyun. Bu koruma sunucuya ve WordPress anahtarlarına erişimi olan saldırgana karşı koruma sağlamaz. Bir uygulamanın Client ID'si değişirse eski hesap bağlantısı kaldırılır ve tekrar giriş gerekir.

**Hesap bağlantısını kaldır** yerel belirteçleri siler; X, LinkedIn veya Meta tarafındaki uygulama yetkisini ayrıca iptal etmeniz gerekebilir. Belirteçlerin süresi dolarsa yeniden giriş yapın.

## Planlar ve takvim

Yazı kutusundaki tarih ve saat seçili tüm platformlar için ortaktır ve WordPress'in **Ayarlar → Genel** ekranındaki saat dilimindedir. Yazıyı kaydettikten sonra tarihi **WP Post Schedules** ve **Calendar** ekranlarında görebilirsiniz. Tarih belirtmek veya yazıyı yayımlamak, otomatik gönderim başlatmaz.

## Uyumluluk

PHP 8.0 ve WordPress 6.2 veya üzeri hedeflenmektedir. PHP 8.5.12 ve WordPress 7.1.4 kullandığınızı belirttiniz; gerçek WordPress sitenizde güncellemeden önce yedek alın ve hesap bağlantılarını deneme gönderilerinden ayrı aşamalarda sınayın.
