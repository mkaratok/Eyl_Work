<template>
  <div class="flex">
    <!-- Sidebar -->
    <div class="w-64 min-h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Seller Panel</h2>
      </div>
      <nav class="p-4">
        <ul class="space-y-2">
          <li>
            <NuxtLink 
              to="/seller" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
              exact
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              <span>Dashboard</span>
            </NuxtLink>
          </li>
          <li>
            <NuxtLink 
              to="/seller/products" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              <span>Products</span>
            </NuxtLink>
          </li>
          <li>
            <NuxtLink 
              to="/seller/campaigns" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
              </svg>
              <span>Campaigns</span>
            </NuxtLink>
          </li>
        </ul>
      </nav>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1">
      <slot />
    </div>
  </div>
</template>

<script setup>
</script>