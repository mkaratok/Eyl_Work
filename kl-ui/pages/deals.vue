<template>
  <div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">En İyi Fiyat Fırsatları</h1>
    <p class="text-gray-600 mb-6">Birden fazla satıcıdan fiyat karşılaştırması yaparak en iyi fırsatları bulun</p>
    
    <div class="mb-8">
      <div class="bg-gradient-to-r from-primary to-secondary rounded-xl p-8 text-white">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="mb-6 md:mb-0">
            <h2 class="text-2xl font-bold mb-2">Günün En İyi Fırsatları</h2>
            <p class="mb-4">Satıcılar arasında bulunan en iyi fiyat farkları. Sınırlı süreli kampanyalar!</p>
            <div class="flex items-center">
              <span class="mr-2">Fırsat bitiş süresi:</span>
              <div class="flex space-x-2">
                <div class="bg-white/20 rounded px-2 py-1">
                  <span class="font-bold">{{ timeLeft.days }}</span>
                  <span class="text-sm">Gün</span>
                </div>
                <div class="bg-white/20 rounded px-2 py-1">
                  <span class="font-bold">{{ timeLeft.hours }}</span>
                  <span class="text-sm">Saat</span>
                </div>
                <div class="bg-white/20 rounded px-2 py-1">
                  <span class="font-bold">{{ timeLeft.minutes }}</span>
                  <span class="text-sm">Dakika</span>
                </div>
                <div class="bg-white/20 rounded px-2 py-1">
                  <span class="font-bold">{{ timeLeft.seconds }}</span>
                  <span class="text-sm">Saniye</span>
                </div>
              </div>
            </div>
          </div>
          <NuxtLink to="/products" class="btn-primary bg-white text-primary hover:bg-gray-100">
            Fiyatları Karşılaştır
          </NuxtLink>
        </div>
      </div>
    </div>
    
    <h2 class="text-2xl font-bold mb-4">En Büyük Fiyat Farkları</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div 
        v-for="deal in featuredDeals" 
        :key="deal.id"
        class="card overflow-hidden"
      >
        <div class="relative">
          <div class="bg-gray-200 border-2 border-dashed w-full h-48 flex items-center justify-center">
            <span class="text-gray-500">Ürün Resmi</span>
          </div>
          <div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full font-bold">
            {{ deal.savings }} TL KAZAN
          </div>
        </div>
        <div class="p-4">
          <h3 class="text-lg font-semibold mb-1">{{ deal.name }}</h3>
          <div class="mb-2">
            <div class="flex justify-between text-sm">
              <span>En İyi Fiyat:</span>
              <span class="font-bold text-primary">{{ deal.bestPrice }} TL</span>
            </div>
            <div class="flex justify-between text-sm">
              <span>En Yüksek Fiyat:</span>
              <span class="text-gray-500 line-through">{{ deal.highestPrice }} TL</span>
            </div>
          </div>
          <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">{{ deal.description }}</p>
          <NuxtLink :to="`/product/${deal.id}`" class="btn-primary w-full text-center">Fiyatları Karşılaştır</NuxtLink>
        </div>
      </div>
    </div>
    
    <h2 class="text-2xl font-bold mb-4">Son Fiyat Düşüşleri</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div 
        v-for="product in dealProducts" 
        :key="product.id"
        class="card transition-transform duration-300 hover:-translate-y-1"
      >
        <div class="relative">
          <div class="bg-gray-200 border-2 border-dashed w-full h-32 flex items-center justify-center">
            <span class="text-gray-500 text-sm">Ürün Resmi</span>
          </div>
          <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold">
            -{{ product.discount }}%
          </div>
        </div>
        <div class="p-3">
          <h3 class="font-semibold text-sm mb-1">{{ product.name }}</h3>
          <div class="flex items-center">
            <span class="font-bold text-primary">{{ product.bestPrice }} TL</span>
            <span class="ml-2 text-gray-500 line-through text-sm">{{ product.previousPrice }} TL</span>
          </div>
          <div class="text-xs text-gray-500 mt-1">
            {{ product.bestSeller }} satıcısından
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Mock data for biggest price differences
const featuredDeals = [
  { 
    id: 1, 
    name: 'Smartphone X', 
    highestPrice: 3000, 
    bestPrice: 2100, 
    savings: 900,
    description: 'Latest model with advanced features - biggest price difference found' 
  },
  { 
    id: 2, 
    name: 'Coffee Maker Pro', 
    highestPrice: 400, 
    bestPrice: 280, 
    savings: 120,
    description: 'Automatic coffee maker for your kitchen - great savings available' 
  },
  { 
    id: 3, 
    name: 'Wireless Headphones', 
    highestPrice: 500, 
    bestPrice: 350, 
    savings: 150,
    description: 'Noise-cancelling wireless headphones - compare prices now' 
  }
]

// Mock data for recent price drops
const dealProducts = [
  { id: 1, name: 'Smartphone', bestPrice: 2500, previousPrice: 3000, discount: 17, bestSeller: 'n11' },
  { id: 2, name: 'Laptop', bestPrice: 4500, previousPrice: 5000, discount: 10, bestSeller: 'PTT AV M' },
  { id: 3, name: 'Tablet', bestPrice: 1200, previousPrice: 1500, discount: 20, bestSeller: 'n11' },
  { id: 4, name: 'Smart Watch', bestPrice: 800, previousPrice: 1000, discount: 20, bestSeller: 'Hepsiburada' },
  { id: 5, name: 'Bluetooth Speaker', bestPrice: 300, previousPrice: 400, discount: 25, bestSeller: 'Trendyol' },
  { id: 6, name: 'Gaming Console', bestPrice: 2500, previousPrice: 3000, discount: 17, bestSeller: 'n11' },
  { id: 7, name: 'Camera', bestPrice: 3500, previousPrice: 4000, discount: 13, bestSeller: 'PTT AV M' },
  { id: 8, name: 'Fitness Tracker', bestPrice: 400, previousPrice: 500, discount: 20, bestSeller: 'n11' }
]

// Countdown timer
const timeLeft = ref({
  days: 0,
  hours: 0,
  minutes: 0,
  seconds: 0
})

let countdownTimer = null

const calculateTimeLeft = () => {
  // Set end date to 5 days from now
  const endDate = new Date()
  endDate.setDate(endDate.getDate() + 5)
  
  const now = new Date()
  const difference = endDate.getTime() - now.getTime()
  
  if (difference > 0) {
    timeLeft.value = {
      days: Math.floor(difference / (1000 * 60 * 60 * 24)),
      hours: Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
      minutes: Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)),
      seconds: Math.floor((difference % (1000 * 60)) / 1000)
    }
  }
}

onMounted(() => {
  calculateTimeLeft()
  countdownTimer = setInterval(calculateTimeLeft, 1000)
})

onUnmounted(() => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
  }
})
</script>