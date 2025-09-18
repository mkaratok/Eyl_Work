<template>
  <div class="container mx-auto animate-fade-in">
    <div v-if="loading" class="text-center py-8">
      <p>Ürün bilgileri yükleniyor...</p>
    </div>
    
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-500">Hata: {{ error }}</p>
      <button @click="fetchProductData" class="mt-4 bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        Tekrar Dene
      </button>
    </div>
    
    <div v-else-if="product" class="bg-white rounded-lg shadow-md overflow-hidden">
      <div class="md:flex">
        <!-- Product Image -->
        <div class="md:w-1/2 p-6 flex items-center justify-center bg-gray-100">
          <div v-if="product.thumbnail_url" class="w-64 h-64 flex items-center justify-center">
            <img 
              :src="product.thumbnail_url" 
              :alt="product.name"
              class="max-w-full max-h-full object-contain"
              @error="$event.target.style.display='none'"
            >
          </div>
          <div v-else class="bg-gray-200 border-2 border-dashed rounded-xl w-64 h-64 flex items-center justify-center">
            <span class="text-gray-500">No Image Available</span>
          </div>
        </div>
        
        <!-- Product Details -->
        <div class="md:w-1/2 p-6">
          <nav class="text-sm mb-4">
            <NuxtLink to="/" class="text-blue-500 hover:underline">Ana Sayfa</NuxtLink> / 
            <NuxtLink to="/products" class="text-blue-500 hover:underline"> Ürünler</NuxtLink> / 
            <span class="text-gray-500"> {{ product.name }}</span>
          </nav>
          
          <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>
          
          <div class="flex items-center mb-4">
            <div class="flex text-amber-400">
              <svg v-for="i in 5" :key="i" class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
              </svg>
            </div>
            <span class="ml-2 text-gray-600">(128 reviews)</span>
          </div>
          
          <p class="text-gray-700 mb-6">{{ product.description }}</p>
          
          <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2">Kategori</h3>
            <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700">
              {{ product.category?.name || product.category }}
            </span>
          </div>
          
          <div class="border-t pt-4">
            <h3 class="text-lg font-semibold mb-2">Ürün Detayları</h3>
            <ul class="list-disc pl-5 space-y-1 text-gray-600">
              <li>Yüksek kaliteli malzemeler</li>
              <li>1 yıl garanti</li>
              <li>Ücretsiz kargo</li>
              <li>30 gün iade garantisi</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Price Comparison Section -->
    <div v-if="product && !loading" class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
      <div class="p-6">
        <h2 class="text-2xl font-bold mb-4">Fiyat Karşılaştırma</h2>
        <p class="text-gray-600 mb-6">En iyi fırsatı bulmak için farklı satıcılardan fiyat karşılaştırması yapın</p>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satıcı</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fiyat</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kargo</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Toplam</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">İşlem</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="price in product.active_prices" :key="price.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-8 h-8 mr-2"></div>
                    <div class="font-medium text-gray-900">{{ price.seller?.name || 'Unknown Seller' }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-primary">{{ formatPrice(price.price) }} TL</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">Standart Kargo</td>
                <td class="px-6 py-4 whitespace-nowrap text-lg font-bold">{{ formatPrice(price.price) }} TL</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <button 
                    @click="visitSeller(price.seller?.name || 'Unknown Seller', '#')"
                    class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition"
                  >
                    Mağazayı Ziyaret Et
                  </button>
                </td>
              </tr>
              <tr v-if="!product.active_prices || product.active_prices.length === 0">
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                  Bu ürün için fiyat teklifi bulunmamaktadır.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
          <h3 class="font-semibold text-lg mb-2">En İyi Fırsat</h3>
          <p class="text-gray-700" v-if="bestOffer">
            <span class="font-semibold">{{ product.name }}</span> için en iyi fiyat 
            <span class="font-semibold">{{ bestOffer.seller?.name || 'Unknown Seller' }}</span> satıcısında 
            <span class="font-bold text-primary">{{ formatPrice(bestOffer.price) }} TL</span>
          </p>
          <p class="text-gray-700" v-else>
            Şu anda fiyat teklifi bulunmamaktadır.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useProducts } from '~/composables/useProducts'
import { useRoute } from '#imports'

const route = useRoute()
const productId = route.params.id

// Use our composable for product data
const { product, loading, error, fetchProduct } = useProducts()

// Computed property for the best offer
const bestOffer = computed(() => {
  if (!product.value || !product.value.active_prices || product.value.active_prices.length === 0) return null
  
  // Find the price with the lowest value
  return product.value.active_prices.reduce((best, current) => {
    const bestPrice = parseFloat(best.price);
    const currentPrice = parseFloat(current.price);
    return currentPrice < bestPrice ? current : best;
  });
})

// Function to fetch product data
const fetchProductData = async () => {
  try {
    await fetchProduct(productId)
  } catch (err) {
    console.error('Failed to fetch product:', err)
  }
}

// Helper method to format price
const formatPrice = (price) => {
  if (!price) return '0.00';
  return parseFloat(price).toFixed(2);
}

// Visit seller function
const visitSeller = (seller, url) => {
  // In a real app, this would redirect to the seller's page
  alert(`Visiting ${seller} at ${url}`)
}

// Fetch product data when component mounts
onMounted(() => {
  fetchProductData()
})
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out forwards;
}
</style>