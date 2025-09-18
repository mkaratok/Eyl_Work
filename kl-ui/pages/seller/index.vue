<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Seller Dashboard</h1>
      <button @click="handleLogout" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
        Çıkış Yap
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
        <div class="space-y-3">
          <NuxtLink to="/seller/products" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              <span>Product Management</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/seller/prices" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Price Management</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/seller/analytics" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              <span>Analytics</span>
            </div>
          </NuxtLink>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Sales Overview</h2>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Total Products</span>
            <span class="font-bold text-gray-900 dark:text-white">142</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Active Products</span>
            <span class="font-bold text-gray-900 dark:text-white">128</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">This Month Sales</span>
            <span class="font-bold text-gray-900 dark:text-white">₺24,562</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Pending Orders</span>
            <span class="font-bold text-red-500">12</span>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Price updated for Product #1245</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">2 minutes ago</p>
            </div>
          </div>
          
          <div class="flex items-start">
            <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">New product added</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">15 minutes ago</p>
            </div>
          </div>
          
          <div class="flex items-start">
            <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Sales report generated</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuth } from '~/composables/useAuth'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: 'auth'
})

const router = useRouter()
const { logout } = useAuth()

const handleLogout = async () => {
  try {
    await logout()
    router.push('/login')
  } catch (err) {
    console.error('Logout failed:', err)
  }
}
</script>