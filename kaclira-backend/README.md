# Kaçlıra Backend API

## Genel Bakış

Kaçlıra Backend API, Laravel 10 üzerine inşa edilmiş bir e-ticaret platformunun backend servisidir. Bu API, müşteriler, satıcılar ve yöneticiler için kapsamlı bir API seti sunar.

## Kurulum

```bash
# Bağımlılıkları yükleyin
composer install

# .env dosyasını oluşturun
cp .env.example .env

# Uygulama anahtarını oluşturun
php artisan key:generate

# Veritabanını migrate edin ve seed verilerini ekleyin
php artisan migrate --seed

# API sunucusunu başlatın
php artisan serve
```

For production deployment, see [DEPLOYMENT.md](DEPLOYMENT.md) for detailed instructions.

## Kimlik Doğrulama Sistemi

Kaçlıra Backend API, güvenli kimlik doğrulama için Laravel Sanctum kullanmaktadır. Sanctum, SPA (Single Page Applications) ve mobil uygulamalar için token tabanlı kimlik doğrulama sağlar.

### Token Sistemi

- **Token Oluşturma**: Kullanıcılar giriş yaptığında, Sanctum tarafından bir kişisel erişim tokeni oluşturulur.
- **Token Saklama**: Tokenler veritabanında `personal_access_tokens` tablosunda güvenli bir şekilde saklanır.
- **Token Doğrulama**: Her API isteği için `Authorization: Bearer {token}` başlığı kullanılarak token doğrulaması yapılır.
- **Token Süresi**: Tokenler varsayılan olarak süresiz geçerlidir, ancak `config/sanctum.php` dosyasından süre sınırlaması yapılandırılabilir.
- **Token İptali**: Kullanıcı çıkış yaptığında veya güvenlik nedeniyle token iptal edilebilir.

### Rol Tabanlı Erişim Kontrolü

Sistem, farklı kullanıcı türleri için rol tabanlı erişim kontrolü sağlar:

- **Admin**: Tam yönetici erişimi
- **Seller**: Satıcı paneli ve ürün yönetimi erişimi
- **Customer**: Müşteri işlemleri erişimi

### Özel Middleware'ler

- **SanctumSellerMiddleware**: Satıcı rolüne sahip kullanıcıların kimlik doğrulaması için özel middleware
- **RequestValidationMiddleware**: API isteklerinin doğrulanması için middleware

## API Endpoint'leri

### Kimlik Doğrulama

- `POST /api/auth/login`: Kullanıcı girişi ve token oluşturma
- `POST /api/auth/register`: Yeni kullanıcı kaydı
- `POST /api/auth/logout`: Oturumu kapatma ve token iptal etme
- `GET /api/auth/me`: Mevcut kimliği doğrulanmış kullanıcı bilgilerini alma

### Satıcı Endpoint'leri

- `POST /api/seller/login`: Satıcı girişi
- `POST /api/seller/register`: Satıcı kaydı
- `GET /api/seller/dashboard-direct`: Satıcı panosu verilerini alma
- `GET /api/seller/products`: Satıcının ürünlerini listeleme
- `POST /api/seller/products`: Yeni ürün ekleme

### Admin Endpoint'leri

- `POST /api/admin/login`: Admin girişi
- `GET /api/admin/dashboard/stats`: Dashboard istatistiklerini alma
- `GET /api/admin/users`: Kullanıcıları listeleme

## Güvenlik Önlemleri

- CORS koruması
- Rate limiting
- SQL enjeksiyon koruması
- XSS koruması
- CSRF koruması (web rotaları için)

## Önemli Notlar ve Dikkat Edilmesi Gerekenler

1. **Token Yönetimi**: Token'lar güvenli bir şekilde saklanmalı ve taşınmalıdır. Frontend uygulamasında token'ları localStorage yerine HttpOnly çerezlerde saklamak daha güvenlidir.

2. **Middleware Yapılandırması**: Özel middleware'lerin doğru sırada uygulandığından emin olun. Middleware sırası `app/Http/Kernel.php` dosyasında tanımlanmıştır.

3. **Route Önbelleği**: Rotalarda değişiklik yaptıktan sonra `php artisan route:clear` komutunu çalıştırarak rota önbelleğini temizleyin.

4. **Sanctum Yapılandırması**: Sanctum yapılandırması `config/sanctum.php` dosyasında bulunmaktadır. Token süresi, stateful domainler ve diğer ayarlar buradan yapılandırılabilir.

5. **API Sürümlendirme**: API'yi sürümlendirmek için prefix kullanılmaktadır (örn. `/api/v1/...`). Büyük değişiklikler için yeni bir sürüm oluşturun.

## Hata Ayıklama

API hata ayıklama için:

1. Laravel log dosyalarını kontrol edin: `storage/logs/laravel.log`
2. Debug modunu etkinleştirin: `.env` dosyasında `APP_DEBUG=true` olarak ayarlayın
3. Test endpoint'lerini kullanın: `/api/test/...` rotaları hata ayıklama için kullanılabilir

## Yapılan Son Güncellemeler

1. **JWT'den Sanctum'a Geçiş**: Kimlik doğrulama sistemi JWT'den Laravel Sanctum'a geçirildi. Bu değişiklik, daha güvenli ve kolay yönetilebilir bir token sistemi sağladı.

2. **Özel Middleware Eklendi**: Satıcı rolüne sahip kullanıcılar için `sanctum.seller` adında özel bir middleware eklendi. Bu middleware, token doğrulaması ve rol kontrolü yapar.

3. **Dashboard Endpoint'i Düzeltildi**: Satıcı dashboard endpoint'i (`/api/seller/dashboard-direct`) düzeltildi ve Sanctum ile korunacak şekilde yapılandırıldı.

4. **Frontend Entegrasyonu**: Frontend API utility'si (`useApi.ts`) güncellendi ve yeni dashboard endpoint'i ile çalışacak şekilde yapılandırıldı.

## Teknik Detaylar

### Sanctum Token Kullanımı

```php
// Token oluşturma örneği (SellerAuthController'dan)
public function login(Request $request)
{
    $seller = User::where('email', $request->email)
                  ->where('role', 'seller')
                  ->first();
                  
    if (!$seller || !Hash::check($request->password, $seller->password)) {
        return $this->error('Invalid credentials', 401);
    }
    
    // Token oluşturma
    $token = $seller->createToken('seller-token')->plainTextToken;
    
    return $this->success([
        'user' => $seller,
        'token' => $token
    ]);
}

// Token doğrulama örneği (SanctumSellerMiddleware'den)
public function handle($request, Closure $next)
{
    $token = $request->bearerToken();
    if (!$token) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    $accessToken = PersonalAccessToken::findToken($token);
    if (!$accessToken) {
        return response()->json(['message' => 'Invalid token'], 401);
    }
    
    $user = $accessToken->tokenable;
    if ($user->role !== 'seller') {
        return response()->json(['message' => 'Unauthorized. Not a seller account'], 403);
    }
    
    Auth::login($user);
    return $next($request);
}
```

## İletişim

Proje ile ilgili sorularınız için lütfen iletişime geçin.

## Project Status

✅ **COMPLETED** - This project is fully functional and ready for production deployment.

## Lisans

Kaçlıra Backend API, özel bir lisans altında geliştirilmiştir. Tüm hakları saklıdır.
