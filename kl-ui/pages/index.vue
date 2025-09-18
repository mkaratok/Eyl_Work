<template>
  <div class="container mx-auto">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-primary to-secondary rounded-xl p-8 mb-12 text-white text-center animate-fade-in">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Kaçlira.com - Fiyat Karşılaştırma</h1>
      <p class="text-xl mb-6">Yüzlerce satıcıdan fiyat karşılaştırması yaparak en iyi fırsatları bulun</p>
      
      <!-- Search Bar -->
      <div class="max-w-2xl mx-auto">
        <SearchBar @search="handleSearch" />
      </div>
      
      <!-- Stats -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto">
        <div class="p-4 bg-white/10 rounded-lg backdrop-blur-sm">
          <div class="text-2xl font-bold">100+</div>
          <div class="text-sm">Satıcı</div>
        </div>
        <div class="p-4 bg-white/10 rounded-lg backdrop-blur-sm">
          <div class="text-2xl font-bold">1M+</div>
          <div class="text-sm">Ürün</div>
        </div>
        <div class="p-4 bg-white/10 rounded-lg backdrop-blur-sm">
          <div class="text-2xl font-bold">500K+</div>
          <div class="text-sm">Mutlu Müşteri</div>
        </div>
      </div>
    </div>
    
    <!-- Featured Categories -->
    <section class="mb-12">
      <h2 class="text-3xl font-bold mb-6 text-center">Kategorilere Göre Gözat</h2>
      
      <div v-if="loading" class="text-center py-8">
        <p>Kategoriler yükleniyor...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-8">
        <p class="text-red-500">Hata: {{ error }}</p>
        <button @click="fetchCategoriesData" class="mt-4 bg-primary text-white px-4 py-2 rounded hover:bg-blue-700 transition">
          Tekrar Dene
        </button>
      </div>
      
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="category in categories.slice(0, 4)" 
          :key="category.id"
          class="bg-white rounded-lg shadow-md p-6 text-center cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group"
          @click="goToCategory(category.slug)"
        >
          <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-primary/10 group-hover:bg-primary/20 transition-colors">
            <svg 
              v-if="category.slug === 'electronics'" 
              class="w-8 h-8 text-primary" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
            </svg>
            <svg 
              v-else-if="category.slug === 'clothing'" 
              class="w-8 h-8 text-primary" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <svg 
              v-else-if="category.slug === 'home'" 
              class="w-8 h-8 text-primary" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <svg 
              v-else 
              class="w-8 h-8 text-primary" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24" 
              xmlns="http://www.w3.org/2000/svg"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 group-hover:text-primary transition-colors">{{ category.name || 'Unnamed Category' }}</h3>
        </div>
      </div>
    </section>
    
    <!-- How It Works -->
    <section class="mb-12 bg-gray-100 rounded-xl p-8">
      <h2 class="text-3xl font-bold mb-6 text-center">Nasıl Çalışır</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-white rounded-lg shadow-sm">
          <div class="bg-primary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl font-bold">1</span>
          </div>
          <h3 class="text-xl font-semibold mb-2 text-gray-800">Ürün Ara</h3>
          <p class="text-gray-600">Veritabanımızda aradığınız ürünü bulun</p>
        </div>
        <div class="text-center p-4 bg-white rounded-lg shadow-sm">
          <div class="bg-primary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl font-bold">2</span>
          </div>
          <h3 class="text-xl font-semibold mb-2 text-gray-800">Fiyatları Karşılaştır</h3>
          <p class="text-gray-600">n11 ve PTT AVM gibi satıcılardan gerçek zamanlı fiyatları görün</p>
        </div>
        <div class="text-center p-4 bg-white rounded-lg shadow-sm">
          <div class="bg-primary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl font-bold">3</span>
          </div>
          <h3 class="text-xl font-semibold mb-2 text-gray-800">En İyi Satıcıya Git</h3>
          <p class="text-gray-600">Satın almak için en iyi fiyata sahip satıcıya gidin</p>
        </div>
      </div>
    </section>
    
    <!-- Top Sellers -->
    <section class="mb-12">
      <h2 class="text-3xl font-bold mb-6 text-center">En İyi Satıcılarımız</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center transition-all duration-300 hover:shadow-lg">
          <div class="flex items-center justify-center w-16 h-16 mr-4 rounded-lg bg-blue-100">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-800">n11</h3>
            <p class="text-gray-600">n11 kataloğundan gerçek zamanlı fiyatlar</p>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center transition-all duration-300 hover:shadow-lg">
          <div class="flex items-center justify-center w-16 h-16 mr-4 rounded-lg bg-red-100">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-800">PTT AVM</h3>
            <p class="text-gray-600">PTT AVM envanterinden gerçek zamanlı fiyatlar</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCategories } from '~/composables/useCategories'

// Use our composable for categories data
const { categories, loading, error, fetchCategories } = useCategories()

// Function to fetch categories data
const fetchCategoriesData = async () => {
  try {
    await fetchCategories()
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

const handleSearch = (query) => {
  // In a real app, this would navigate to the products page with the search query
  alert(`Searching for: ${query}`)
}

const goToCategory = (categorySlug) => {
  // In a real app, this would navigate to the category page
  alert(`Going to category: ${categorySlug}`)
}

// Fetch categories data when component mounts
onMounted(() => {
  fetchCategoriesData()
})
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.5s ease-out forwards;
}
</style>