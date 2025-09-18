<template>
  <div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Tüm Kategoriler</h1>
    <p class="text-gray-600 mb-6">Kategoriler arasında gezinerek yüzlerce satıcıdan fiyat karşılaştırması yapın</p>
    
    <div v-if="loading" class="text-center py-8">
      <p>Kategoriler yükleniyor...</p>
    </div>
    
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-500">Hata: {{ error }}</p>
      <button @click="fetchCategoriesData" class="mt-4 bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        Tekrar Dene
      </button>
    </div>
    
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <!-- Debugging: Show raw categories data -->
      <div v-if="!Array.isArray(categories) || categories.length === 0" class="col-span-full text-center py-8">
        <p class="text-red-500">Kategori verisi bulunamadı veya geçersiz formatta: {{ JSON.stringify(categories) }}</p>
      </div>
      
      <div 
        v-else
        v-for="(category, index) in categories" 
        :key="category?.id || index"
        class="bg-white rounded-lg shadow-md overflow-hidden cursor-pointer transform transition duration-300 hover:-translate-y-2 hover:shadow-lg group"
        @click="goToCategory(category?.slug)"
      >
        <div class="relative h-40 overflow-hidden">
          <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-primary/10 to-secondary/10">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-white/80 group-hover:bg-white transition-colors">
              <svg 
                v-if="category?.slug === 'electronics'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'clothing'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'home-kitchen'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'books'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'sports'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10l3 1 1 3 2 2 1.657-1.657a8 8 0 011.657 1.657zM13 9l2 2m0 0l3-3m-3 3l-3-3m10 0a8 8 0 11-16 0 8 8 0 0116 0z"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'beauty'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'toys'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <svg 
                v-else-if="category?.slug === 'automotive'" 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <svg 
                v-else 
                class="w-8 h-8 text-primary" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
              </svg>
            </div>
          </div>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-primary transition-colors">{{ category?.name || 'Unnamed Category' }}</h3>
          <p class="text-gray-500 text-sm mt-1">{{ category?.children ? category.children.length : 0 }}+ alt kategori</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCategories } from '~/composables/useCategories'

// Use our composable for categories data
const { categories, loading, error, fetchCategories } = useCategories()
console.log('Categories ref:', categories); // Debugging line

// Function to fetch categories data
const fetchCategoriesData = async () => {
  try {
    console.log('Calling fetchCategories...');
    await fetchCategories()
    console.log('Categories fetched successfully:', categories.value);
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

const goToCategory = (slug) => {
  // In a real app, this would navigate to the category page
  alert(`Navigating to category: ${slug}`)
}

// Fetch categories data when component mounts
onMounted(() => {
  console.log('Fetching categories...'); // Debugging line
  fetchCategoriesData()
})
</script>