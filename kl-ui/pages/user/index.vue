<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">User Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your account and preferences</p>
      </div>
      <button @click="handleLogout" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
        Çıkış Yap
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Account Settings</h2>
        <div class="space-y-3">
          <NuxtLink to="/user/profile" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <span>Profile Information</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/user/addresses" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span>Shipping Addresses</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/user/notifications" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
              </svg>
              <span>Notification Settings</span>
            </div>
          </NuxtLink>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">My Activity</h2>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Favorite Products</span>
            <span class="font-bold text-gray-900 dark:text-white">24</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Price Alerts</span>
            <span class="font-bold text-gray-900 dark:text-white">8</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Compared Products</span>
            <span class="font-bold text-gray-900 dark:text-white">142</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Recent Searches</span>
            <span class="font-bold text-gray-900 dark:text-white">36</span>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Added product to favorites</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">2 minutes ago</p>
            </div>
          </div>
          
          <div class="flex items-start">
            <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Set price alert</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">15 minutes ago</p>
            </div>
          </div>
          
          <div class="flex items-start">
            <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Searched for "iPhone 14"</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: 'auth'
})

const router = useRouter()
const { user, loading, error, getCurrentUser, logout } = useAuth()

// Function to fetch user data
const fetchUserData = async () => {
  try {
    await getCurrentUser()
  } catch (err) {
    console.error('Failed to fetch user data:', err)
  }
}

// Function to handle logout
const handleLogout = async () => {
  try {
    await logout()
    router.push('/login')
  } catch (err) {
    console.error('Logout failed:', err)
  }
}

// Fetch user data when component mounts
onMounted(() => {
  fetchUserData()
})
</script>