<template>
  <div class="flex">
    <!-- Sidebar -->
    <div class="w-64 min-h-screen bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">My Account</h2>
      </div>
      <nav class="p-4">
        <ul class="space-y-2">
          <li>
            <NuxtLink 
              to="/user" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
              exact
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <span>Profile</span>
            </NuxtLink>
          </li>
         <!-- <li>
            <NuxtLink 
              to="/user/orders" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
              <span>My Orders</span>
            </NuxtLink>
          </li>
          <li>
            <NuxtLink 
              to="/user/addresses" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span>Addresses</span>
            </NuxtLink>
          </li>-->
          <li>
            <NuxtLink 
              to="/user/wishlist" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
              </svg>
              <span>Wishlist</span>
            </NuxtLink>
          </li>
          <li>
            <NuxtLink 
              to="/user/settings" 
              class="flex items-center p-3 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
              active-class="bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400"
            >
              <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span>Settings</span>
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