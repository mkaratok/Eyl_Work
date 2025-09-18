<template>
  <AdminSidebar>
    <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
      <button @click="handleLogout" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
        Çıkış Yap
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
        <div class="space-y-3">
          <NuxtLink to="/admin/users" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              <span>User Management</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/admin/products" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              <span>Product Management</span>
            </div>
          </NuxtLink>
          
          <NuxtLink to="/admin/categories" class="block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
              <span>Category Management</span>
            </div>
          </NuxtLink>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">System Stats</h2>
        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Total Users</span>
            <span class="font-bold text-gray-900 dark:text-white">1,248</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Total Products</span>
            <span class="font-bold text-gray-900 dark:text-white">24,562</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Total Sellers</span>
            <span class="font-bold text-gray-900 dark:text-white">142</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-600 dark:text-gray-400">Pending Approvals</span>
            <span class="font-bold text-red-500">24</span>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-full mr-3">
              <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">New user registered</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Category updated</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </AdminSidebar>
</template>

<script setup>
import { useAuth } from '~/composables/useAuth'
import { useRouter } from 'vue-router'
import AdminSidebar from '@/components/AdminSidebar.vue'

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