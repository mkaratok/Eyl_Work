<template>
  <div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-4">Ürün Karşılaştırma</h1>
    <p class="text-gray-600 mb-6">Yüzlerce satıcıdan fiyat karşılaştırması yaparak en iyi fırsatları bulun</p>
    
    <!-- Search Bar -->
    <div class="mb-6">
      <SearchBar @search="handleSearch" />
    </div>
    
    <div v-if="loading" class="text-center py-8">
      <p>Ürünler yükleniyor...</p>
    </div>
    
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-500">Hata: {{ error }}</p>
      <button @click="fetchProductsData" class="mt-4 bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        Tekrar Dene
      </button>
    </div>
    
    <div v-else class="flex flex-col md:flex-row">
      <!-- Filters Sidebar -->
      <div class="md:w-1/4 md:pr-4 mb-6 md:mb-0">
        <ProductFilters @filter="handleFilter" />
      </div>
      
      <!-- Products Grid -->
      <div class="md:w-3/4">
        <div v-if="filteredProducts.length === 0" class="text-center py-8">
          <p class="text-gray-500">Kriterlerinize uygun ürün bulunamadı.</p>
        </div>
        
        <div v-else class="grid grid-cols-1 gap-4">
          <div 
            v-for="product in filteredProducts" 
            :key="product.id" 
            class="border p-4 rounded transition-transform duration-300 hover:shadow-lg"
          >
            <div class="flex flex-col md:flex-row">
              <div class="md:w-1/4 mb-4 md:mb-0 flex items-center justify-center">
                <div v-if="product.thumbnail_url" class="w-24 h-24 flex items-center justify-center">
                  <img 
                    :src="product.thumbnail_url" 
                    :alt="product.name"
                    class="w-full h-full object-contain"
                    @error="$event.target.style.display='none'"
                  >
                </div>
                <div v-else class="bg-gray-200 border-2 border-dashed rounded-xl w-24 h-24 flex items-center justify-center">
                  <span class="text-gray-500 text-sm">No Image</span>
                </div>
              </div>
              
              <div class="md:w-3/4 md:pl-4">
                <h2 class="text-xl font-semibold">{{ product.name }}</h2>
                <p class="text-sm text-gray-500 mb-2">{{ product.category?.name || product.category }}</p>
                <p class="text-gray-600 mb-3">{{ product.description }}</p>
                
                <!-- Price Information -->
                <div class="mb-3">
                  <div class="flex items-center">
                    <span class="text-gray-700 mr-2">Price:</span>
                    <span class="text-lg font-bold text-primary">{{ formatPrice(getProductPrice(product)) }} TL</span>
                  </div>
                  <div v-if="getSellerCount(product) > 0" class="text-sm text-gray-500 mt-1">
                    Available from {{ getSellerCount(product) }}+ sellers
                  </div>
                </div>
                
                <NuxtLink 
                  :to="`/product/${product.id}`" 
                  class="text-blue-500 hover:underline inline-block"
                >
                  Fiyat Karşılaştırmasını Görüntüle
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProducts } from '~/composables/useProducts'

// Use our composable for products data
const { products, loading, error, fetchProducts } = useProducts()

const searchQuery = ref('')
const filters = ref({
  minPrice: '',
  maxPrice: '',
  category: ''
})

const filteredProducts = computed(() => {
  let result = products.value || []
  
  // Apply search filter
  if (searchQuery.value) {
    result = result.filter(product => 
      (product.name && product.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (product.description && product.description.toLowerCase().includes(searchQuery.value.toLowerCase()))
    )
  }
  
  // Apply price filters
  if (filters.value.minPrice) {
    result = result.filter(product => {
      const price = getProductPrice(product);
      return parseFloat(price) >= parseFloat(filters.value.minPrice);
    });
  }
  
  if (filters.value.maxPrice) {
    result = result.filter(product => {
      const price = getProductPrice(product);
      return parseFloat(price) <= parseFloat(filters.value.maxPrice);
    });
  }
  
  // Apply category filter
  if (filters.value.category) {
    result = result.filter(product => {
      const categoryName = product.category?.name || product.category;
      return categoryName && categoryName === filters.value.category;
    });
  }
  
  return result;
})

// Function to fetch products data
const fetchProductsData = async () => {
  try {
    await fetchProducts()
  } catch (err) {
    console.error('Failed to fetch products:', err)
  }
}

// Helper method to get product price
const getProductPrice = (product) => {
  // Check if product has active prices
  if (product.active_prices && product.active_prices.length > 0) {
    // Return the first active price
    return product.active_prices[0].price;
  }
  
  // Fallback to product.price if available
  return product.price || 0;
}

// Helper method to format price
const formatPrice = (price) => {
  if (!price) return '0';
  return parseFloat(price).toFixed(2);
}

// Helper method to get seller count
const getSellerCount = (product) => {
  if (product.active_prices && product.active_prices.length > 0) {
    return product.active_prices.length;
  }
  return 0;
}

const handleSearch = (query) => {
  searchQuery.value = query
}

// Handle search query from URL
const route = useRoute()
if (route.query.search) {
  searchQuery.value = route.query.search
}

const handleFilter = (filterData) => {
  filters.value = filterData
}

// Fetch products data when component mounts
onMounted(() => {
  fetchProductsData()
})
</script>