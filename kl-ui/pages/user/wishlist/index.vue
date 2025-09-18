<template>
  <UserSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Wishlist</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Your favorite products</p>
        </div>
      </div>

      <!-- Wishlist Items -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <!-- Product Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
          <div class="relative">
            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-full h-48"></div>
            <button class="absolute top-3 right-3 p-2 bg-white rounded-full shadow-md text-red-500 hover:text-red-700 dark:bg-gray-700">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          <div class="p-4">
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">Wireless Headphones</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">TechStore</p>
            <div class="flex items-center mb-3">
              <div class="flex text-amber-400">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
              </div>
              <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">(128)</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-gray-900 dark:text-white">1,260 TL</span>
              <button class="btn-primary text-sm px-3 py-1">
                Add to Cart
              </button>
            </div>
          </div>
        </div>

        <!-- Product Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
          <div class="relative">
            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-full h-48"></div>
            <button class="absolute top-3 right-3 p-2 bg-white rounded-full shadow-md text-red-500 hover:text-red-700 dark:bg-gray-700">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          <div class="p-4">
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">Smartphone Case</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">MobileHub</p>
            <div class="flex items-center mb-3">
              <div class="flex text-amber-400">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
              </div>
              <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">(96)</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-gray-900 dark:text-white">380 TL</span>
              <button class="btn-primary text-sm px-3 py-1">
                Add to Cart
              </button>
            </div>
          </div>
        </div>

        <!-- Product Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
          <div class="relative">
            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-full h-48"></div>
            <button class="absolute top-3 right-3 p-2 bg-white rounded-full shadow-md text-red-500 hover:text-red-700 dark:bg-gray-700">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          <div class="p-4">
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">Bluetooth Speaker</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">SoundTech</p>
            <div class="flex items-center mb-3">
              <div class="flex text-amber-400">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
              </div>
              <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">(242)</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-gray-900 dark:text-white">870 TL</span>
              <button class="btn-primary text-sm px-3 py-1">
                Add to Cart
              </button>
            </div>
          </div>
        </div>

        <!-- Product Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
          <div class="relative">
            <div class="bg-gray-200 border-2 border-dashed rounded-xl w-full h-48"></div>
            <button class="absolute top-3 right-3 p-2 bg-white rounded-full shadow-md text-red-500 hover:text-red-700 dark:bg-gray-700">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
              </svg>
            </button>
          </div>
          <div class="p-4">
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">USB-C Charger</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">TechStore</p>
            <div class="flex items-center mb-3">
              <div class="flex text-amber-400">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
                <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                </svg>
              </div>
              <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">(78)</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-gray-900 dark:text-white">520 TL</span>
              <button class="btn-primary text-sm px-3 py-1">
                Add to Cart
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-8">
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing <span class="font-medium">1</span> to <span class="font-medium">4</span> of <span class="font-medium">24</span> results
        </div>
        <div class="flex space-x-2">
          <button class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            Previous
          </button>
          <button class="px-3 py-1 rounded-md bg-blue-500 text-white">
            1
          </button>
          <button class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            2
          </button>
          <button class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            3
          </button>
          <button class="px-3 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            Next
          </button>
        </div>
      </div>
    </div>
  </UserSidebar>
</template>

<script setup>
import UserSidebar from '@/components/UserSidebar.vue'
</script>