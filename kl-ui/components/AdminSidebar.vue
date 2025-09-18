<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="flex">
      <!-- Sidebar -->
      <div class="w-64 bg-white dark:bg-gray-800 shadow-lg h-screen fixed left-0 top-0">
        <div class="p-4">
          <div class="flex items-center mb-8">
            <div class="w-8 h-8 bg-blue-500 rounded mr-3">
              <svg class="w-full h-full text-white p-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900 dark:text-white">Admin Panel</span>
          </div>
          
          <nav class="space-y-2">
            <NuxtLink 
              to="/admin"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path === '/admin' }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-3-2V7"></path>
              </svg>
              Dashboard
            </NuxtLink>
            
            <NuxtLink 
              v-if="hasPermission('user.view') || hasPermission('user.manage')"
              to="/admin/users"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path.startsWith('/admin/users') }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              Users
            </NuxtLink>
            
            <NuxtLink 
              v-if="hasPermission('product.view') || hasPermission('product.manage_all')"
              to="/admin/products"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path.startsWith('/admin/products') }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              Products
            </NuxtLink>
            
            <NuxtLink 
              v-if="hasPermission('category.view') || hasPermission('category.manage')"
              to="/admin/categories"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path.startsWith('/admin/categories') }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
              Categories
            </NuxtLink>
            
            <NuxtLink 
              v-if="hasPermission('order.view')"
              to="/admin/orders"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path.startsWith('/admin/orders') }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
              Orders
            </NuxtLink>
            
            <NuxtLink 
              v-if="hasPermission('reports.view')"
              to="/admin/reports"
              class="sidebar-link"
              :class="{ 'sidebar-link-active': $route.path.startsWith('/admin/reports') }"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              Reports
            </NuxtLink>
          </nav>
        </div>
        
        <!-- User Profile Section -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center mb-3">
            <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full mr-3"></div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user?.name || 'Admin User' }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ user?.email || 'admin@example.com' }}</p>
            </div>
          </div>
          <button 
            @click="handleLogout"
            class="w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
          >
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
          </button>
        </div>
      </div>
      
      <!-- Main Content -->
      <div class="flex-1 ml-64">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '~/composables/useAuth'
import { usePermissions } from '~/composables/usePermissions'

const router = useRouter()
const { logout, user } = useAuth()
const { hasPermission } = usePermissions()

const handleLogout = async () => {
  try {
    await logout()
    router.push('/login')
  } catch (err) {
    console.error('Logout failed:', err)
  }
}
</script>

<style scoped>
.sidebar-link {
  @apply flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

.sidebar-link-active {
  @apply bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-r-2 border-blue-500;
}

.btn-primary {
  @apply bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors;
}

.input-field {
  @apply border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>