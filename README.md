# WP Social Publisher

WordPress yazıları için Twitter / X, LinkedIn, Facebook, Instagram, Reddit ve Pinterest paylaşım taslakları hazırlayan eklenti.

## Bu sürümde

Sürüm 0.9.4; X, LinkedIn, Instagram, Reddit ve Pinterest için OAuth 2.0 hesap bağlantısı; Facebook Sayfası için erişim belirteci bağlantısı içerir. **Share Now** onayından sonra tüm bağlı platformlar için gönderi API'leri kullanılır. X yazı taslağında 200 karakterlik yerel hedef aşılırsa gönderim engellenir; X'in gerçek kabul kuralları farklı olabilir. LinkedIn'de öne çıkan görsel yüklenir; Instagram ve Pinterest görsel gönderisi için HTTPS üzerinden herkese açık öne çıkan görsel gerekir; Reddit bağlantı gönderisini Accounts sayfasında seçilen subreddit'e yollar. API ürün erişimi, uygulama incelemesi ve hesap izinleri platform sağlayıcısının kontrolündedir.

**WP Auto Post** ve yazı düzenleyicisindeki tarih alanı seçilen bağlı hesaplar için WordPress tek seferlik cron görevleri oluşturur ve zamanı gelince canlı yayın dener. Gönderim sonuçları **Logs**, durumları **WP Post Schedules** ve **Calendar** ekranlarında gösterilir. WP-Cron ziyaret trafiğiyle çalıştığından düşük trafikli sitelerde gönderi tam dakikasında çalışmayabilir; dakiklik için hosting sağlayıcınızın zamanlanmış cron özelliğiyle `wp-cron.php` dosyasını düzenli çalıştırın. Kuyruk aynı planı yinelenen cron tetiklemelerine karşı korur; başarısız/belirsiz gönderiyi otomatik tekrar denemez.

**Calendar** planları ve Share Now gönderim sonuçlarını gerçekleştiği tarihte, platform ve durum etiketiyle gösterir. **Logs** en son 200 Share Now gönderim girişimini platform, yazı, sonuç ve platform gönderi kimliğiyle listeler; platform, durum ve site saat dilimindeki gün ile filtrelenebilir. Paylaşım metni ve erişim belirteçleri kayıtlara yazılmaz. Ağ hatalarında platform gönderimi almış olabilir; tekrar denemeden önce hedef hesabı kontrol edin.

Hesap parolaları istenmez; bağlantı resmi OAuth akışları üzerinden yapılır. Geliştirici uygulaması oluşturma, OAuth Redirect URI kaydı ve platformun yayınlama izinlerinin onaylanması kullanıcı tarafından tamamlanmalıdır. Bu depo içindeki testler gerçek platform hesabına gönderi yapmaz.

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
| `{hashtags}` | Yazı etiketleri `#WordPress #Sosyalmedya` biçiminde, boşlukla ayrılmış olarak eklenir |
| `{site_title}` | WordPress site adı |

Hashtag kullanmak için ilgili platformun **Etiketler** alanındaki `{tags}` yerine `{hashtags}` yazın ve şablonu kaydedin. Yazıya özel metinde de kullanılabilir. Boşluklar ve noktalama işaretleri kaldırılır, harfler (Türkçe karakterler dahil), rakamlar ve alt çizgiler korunur; platformlar hashtagleri farklı değerlendirebilir. Mevcut şablonlar otomatik değiştirilmez.

İçerik, URL ve etiket alanlarının boş olmayanları paylaşım metninde sırasıyla aralarında boş satır olacak şekilde birleştirilir. Şablon ekranı, öne çıkan görseli bulunan en son yayımlanmış yazının görselini örnek kartta gösterir; öne çıkan görsel yoksa görsel alanının boş olduğu belirtilir.

Yazı kutusundaki yazıya özel metin, varsayılan şablonun tamamının yerine kullanılır. Özel metni boş bırakırsanız varsayılan şablon kullanılır. Özel metin de aynı yer tutucuları destekler.

Yazının **öne çıkan görseli** LinkedIn'e ayrı medya olarak yüklenir; Instagram ve Pinterest API gönderilerinde görsel URL'si olarak kullanılır. Facebook bağlantı önizlemesini kendi kurallarına göre oluşturabilir. Platform sırası Twitter / X, LinkedIn, Facebook, Instagram, Reddit ve Pinterest'tir. Eski Medium/Substack yazı meta verileri veritabanından silinmez; yeni arayüzde görünmez.

## X karakter sayacı

Yazının Twitter / X bölümünde isteğe bağlı dört alanlı özel taslağı etkinleştirin. Başlık, içerik, link ve etiketleri düzenleyebilir, her alanın ve tamamının Unicode karakter sayısını görebilirsiniz. Başlangıçta 🔥, 👉 ve 🔍 içeren alanlarda akıllı etiketler kullanılabilir. Etkinleştirilmezse önceki özel metin veya Templates şablonu korunur. Başlık, özet, bağlantı ve etiketler en son kaydedilmiş yazı bilgileridir; güncel yazı bilgileri için kaydedip düzenleyiciyi yeniden açın.

Bu bir tahmindir: X, bağlantıları ve bazı Unicode karakterlerini farklı ağırlıklarla hesaplayabilir. Gösterge X'in gerçek kabul sınırını doğrulamaz. Taslak kaydedilebilir ama toplam 200 karakteri aşarsa gönderim engellenir; metin otomatik kısaltılmaz.

## Hesapları bağlama ve bağlantıyı test etme

**Social Publisher → Accounts** ekranında platformu seçin. X, LinkedIn, Instagram, Reddit ve Pinterest için ilgili geliştirici uygulamasının Client ID/Secret değerlerini kaydedip resmi OAuth düğmesini kullanın. Reddit'te hedef subreddit, Pinterest'te pano seçimi de aynı uygulama formunda ayarlanır. Facebook bağlantısı Sayfa erişim belirteciyle sürer. Tüm kimlik bilgileri yalnızca HTTPS üzerindeki WordPress yönetim paneline girilmelidir; sohbet, e-posta, ekran görüntüsü veya genel depoda paylaşmayın.

### Twitter / X

1. X Developer Portal'da uygulama oluşturun ve **OAuth 2.0 Authorization Code Flow with PKCE** kullanan **Web App / Confidential Client** yapılandırın.
2. Eklentide Twitter / X hesabını seçin. Gösterilen **OAuth Redirect URI** adresini X uygulamasındaki callback listesine aynen ekleyin.
3. X'in verdiği **Client ID** ve **Client Secret** değerlerini eklentiye girip kaydedin.
4. **Twitter / X ile güvenli giriş** düğmesine basıp X'te yetkilendirin. Dönüşte kullanıcı profili doğrulanır ve hesap WordPress'e kaydedilir.
5. **Bağlantıyı test et** profili yeniden API'den okur. Share Now ile X'e metin ve bağlantı gönderilebilir; birleşik metin 200 karakteri aşarsa eklenti göndermeyi reddeder.

Eklenti `users.read`, `tweet.read`, `tweet.write` ve `offline.access` izinlerini ister; API erişim planı, hesap ve gönderi yazma hakları X tarafından belirlenir. Giriş ve profil testi başarılı olsa bile gönderi izni garanti edilmez.

### LinkedIn

1. LinkedIn Developer Portal'da uygulama oluşturun ve **Sign In with LinkedIn using OpenID Connect** ürününü etkinleştirin.
2. Eklentide LinkedIn'i seçin ve gösterilen **OAuth Redirect URI** adresini uygulamanızdaki Authorized redirect URLs listesine aynen ekleyin.
3. LinkedIn'in verdiği **Client ID** ve **Client Secret** değerlerini eklentiye girip kaydedin.
4. **LinkedIn ile güvenli giriş** düğmesine basıp yetkilendirin. Dönüşte OpenID Connect kullanıcı profili doğrulanır.
5. **Bağlantıyı test et** düğmesi hesabı yeniden kontrol eder. Gönderi paylaşmak için LinkedIn'in ayrı **Share on LinkedIn** ürün izni gerekir; erişim LinkedIn onayına bağlıdır.

Bağlı hesaba deneme gönderisi için **Share Now** sayfasında LinkedIn'i ve yayımlanmış, şifresiz bir yazıyı seçin. Önizlemede öne çıkan görsel görünmelidir; JPEG/PNG ve en fazla 10 MB olmalıdır. Görsel yoksa paylaşım metin ve bağlantı olarak gönderilir. Gönderi ancak **Onayla ve LinkedIn'de paylaş** onayıyla yayımlanır. Başarı ve API hataları **Logs** sayfasına kaydedilir. LinkedIn uygulamasında **Share on LinkedIn** ürün erişimi ve OAuth sırasında verilen `w_member_social` yetkisi zorunludur. Ürün veya kapsam sonradan açıldıysa hesabı yeniden bağlayıp yetkiyi yeniden verin. Logs'taki HTTP durum kodunu kontrol edin; 401/403 genellikle token, ürün erişimi veya izin eksikliğini gösterir. Bağlantı testi gönderi iznini veya API yayınlamasını test etmez.

### Instagram

Instagram'da **Professional (Business veya Creator)** hesap ve Instagram Login destekli geliştirici uygulaması gerekir. Redirect URI'yi uygulamaya aynen ekleyin; `instagram_business_basic` ve `instagram_business_content_publish` izinlerini etkinleştirin. Bu sürüm tek görselli gönderi oluşturur; yazı için herkese açık HTTPS öne çıkan görsel gerekir. Blog bağlantısı caption metninde görünür ancak normal Instagram caption'ında tıklanabilir değildir. Instagram'ın API/App Review koşulları ve hesabın profesyonel durumu gönderim iznini belirler.

### Reddit

Reddit Developer settings'te **web app** oluşturun ve callback URL'yi aynen kaydedin. Eklentide uygulama Client ID/Secret'ını, hedef subreddit adını (`r/` olmadan) kaydedin, ardından **Reddit ile güvenli giriş** adımını tamamlayın. Gönderi yazının başlığı ve bağlantısı olarak oluşturulur; subreddit'in spam/otomasyon kuralları ve Reddit API erişimi geçerlidir.

### Pinterest

Pinterest Developer uygulamasında `user_accounts:read`, `boards:read` ve `pins:write` izinlerini etkinleştirip callback adresini kaydedin. OAuth ile bağlandıktan sonra Accounts formundaki pano alanında mevcut pano kimliğini seçin. Pin için herkese açık HTTPS öne çıkan görseli gerekir; başlık, açıklama ve yazı bağlantısı Pin'e eklenir.

### Facebook Sayfası

Meta Graph API Explorer ile uygulamanız için oluşturduğunuz **Sayfa erişim belirtecini** ve Sayfa kimliğini girin. Uygulama belirtecin sahibinin Sayfa kimliğiyle eşleştiğini doğrular; bu kontrol gönderme iznini garanti etmez. Facebook bağlantısı, WordPress veritabanında OpenSSL AES-256-GCM ile şifrelenir. Anahtar WordPress güvenlik anahtarlarından türetilir; bu anahtarlar değişirse hesabı yeniden bağlayın.

Facebook deneme gönderisi için **Share Now** sayfasında yayımlanmış ve şifresiz bir yazı seçin, önizlemeyi inceleyin ve yalnızca **Onayla ve Facebook’ta paylaş** düğmesine basın. Tek onay tek kullanımlıktır. Ağ hatası veya beklenmedik Meta yanıtı sonucu belirsiz bırakabilir; yeniden denemeden önce Sayfayı kontrol edin.

### Bağlantıyı kaldırma ve güvenlik

OAuth Client Secret ve erişim/yenileme belirteçleri veritabanında şifrelenir. Şifreleme anahtarı WordPress güvenlik anahtarlarına bağlı olduğundan `wp-config.php` anahtarlarını koruyun. Bu koruma sunucuya ve WordPress anahtarlarına erişimi olan saldırgana karşı koruma sağlamaz. Bir uygulamanın Client ID'si değişirse eski hesap bağlantısı kaldırılır ve tekrar giriş gerekir.

**Hesap bağlantısını kaldır** yerel belirteçleri siler; X, LinkedIn veya Meta tarafındaki uygulama yetkisini ayrıca iptal etmeniz gerekebilir. Belirteçlerin süresi dolarsa yeniden giriş yapın.

## Planlar ve takvim

**WP Auto Post** sayfasında kategoriyle yazı listesini süzebilir, bağlı platform hesaplarını seçebilir ve tarih/saat planlayabilirsiniz. Yan panel son planlanan 10 gönderiyi kategori, saat ve durumla gösterir. **WP Post Schedules** kategori ve tarih aralığı filtreleriyle planları listeler. **Calendar** üzerindeki gelecekteki planlarda düzenle düğmesi yazı düzenleyicisini açar; plan tarihi tüm seçili platformlar için ortaktır. Sil düğmesi yalnız seçilen platformu plandan çıkarır; son platform da çıkarılırsa plan ve cron görevi kaldırılır. Yazı veya gönderim kayıtları silinmez. İşlenmiş ve geçmiş planlar için bu düğmeler gösterilmez. Bu saat WordPress'in **Ayarlar → Genel** ekranındaki saat dilimindedir. Plan saatinde WP-Cron canlı gönderim yapar; başarısız/belirsiz sonuçlar Logs'a yazılır ve yinelenme riskine karşı otomatik yeniden denenmez.

## Uyumluluk

PHP 8.0 ve WordPress 6.2 veya üzeri hedeflenmektedir. PHP 8.5.12 ve WordPress 7.1.4 kullandığınızı belirttiniz; gerçek WordPress sitenizde güncellemeden önce yedek alın ve hesap bağlantılarını deneme gönderilerinden ayrı aşamalarda sınayın.
