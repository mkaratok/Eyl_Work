import { defineNuxtPlugin } from '#imports'
import { createI18n } from 'vue-i18n'

// Define messages for Turkish only
const messages = {
  tr: {
    common: {
      home: 'Ana Sayfa',
      products: 'Ürün Karşılaştırma',
      categories: 'Kategoriler',
      deals: 'En İyi Fırsatlar',
      about: 'Hakkında',
      profile: 'Profil',
      search: 'Ürün ara...',
      darkMode: 'Koyu Mod',
      lightMode: 'Açık Mod',
      login: 'Giriş',
      logout: 'Çıkış',
      account: 'Hesap',
      welcome: 'Hoş geldin'
    },
    homepage: {
      title: 'Kaçlira.com - Fiyat Karşılaştırma',
      subtitle: 'En iyi fiyatları bulmak için satıcılara göre karşılaştırın',
      featuredCategories: 'Kategoriye Göre Gözat',
      featuredProducts: 'Popüler Ürünler',
      viewAll: 'Tümünü Gör',
      specialOffers: 'Nasıl Çalışır',
      freeShipping: 'Fiyatları Karşılaştır',
      freeShippingDesc: 'Satıcılar arasında en iyi fırsatları bulun',
      returns: 'Para Tasarrufu',
      returnsDesc: 'Her alışverişte garanti edilen tasarruf',
      support: '7/24 Destek',
      supportDesc: 'Yardım etmek için buradayız'
    },
    products: {
      title: 'Ürün Karşılaştırma',
      noProducts: 'Kriterlerinize uygun ürün bulunamadı.',
      price: 'Fiyat',
      category: 'Kategori',
      allCategories: 'Tüm Kategoriler',
      minPrice: 'Minimum Fiyat',
      maxPrice: 'Maksimum Fiyat',
      applyFilters: 'Filtreleri Uygula',
      reset: 'Sıfırla'
    },
    product: {
      addToCart: 'Satıcıya Git',
      buyNow: 'En İyi Fırsat',
      quantity: 'Miktar',
      productDetails: 'Ürün Detayları',
      reviews: 'değerlendirme',
      priceComparison: 'Fiyat Karşılaştırma',
      seller: 'Satıcı',
      shipping: 'Kargo',
      total: 'Toplam',
      visitStore: 'Mağazayı Ziyaret Et',
      bestDeal: 'En İyi Fırsat'
    },
    user: {
      title: 'Hesabım',
      personalInfo: 'Kişisel Bilgiler',
      orderHistory: 'Sipariş Geçmişi',
      addresses: 'Adresler',
      updateProfile: 'Profili Güncelle',
      noOrders: 'Henüz sipariş vermediniz.',
      noAddresses: 'Henüz adres eklememişsiniz.',
      addAddress: 'Adres Ekle',
      editAddress: 'Adresi Düzenle',
      saveAddress: 'Adresi Kaydet',
      cancel: 'İptal',
      fullName: 'Ad Soyad',
      email: 'E-posta Adresi',
      phone: 'Telefon Numarası',
      addressName: 'Adres Adı',
      street: 'Cadde / Sokak',
      city: 'Şehir',
      postalCode: 'Posta Kodu',
      country: 'Ülke',
      setDefault: 'Varsayılan Yap',
      default: 'Varsayılan'
    }
  }
}

// Create i18n instance with Turkish only
const i18n = createI18n({
  legacy: false, // Use Composition API
  locale: 'tr', // Only Turkish locale
  messages
})

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(i18n)
})