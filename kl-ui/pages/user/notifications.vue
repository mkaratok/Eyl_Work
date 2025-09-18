<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notification Settings</h1>
      <NuxtLink to="/user" class="text-primary hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
        ← Back to Dashboard
      </NuxtLink>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 mb-8">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Email Notifications</h2>
      
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">Price Alerts</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Get notified when prices drop for your favorite products</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">New Products</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Get notified about new products in categories you follow</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">Special Offers</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Get notified about special offers and discounts</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">Order Updates</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Get notified about your order status and shipping updates</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
      </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Push Notifications</h2>
      
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">Enable Push Notifications</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Receive real-time notifications on your device</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" checked>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-medium text-gray-900 dark:text-white">Silent Hours</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Disable notifications between 10 PM and 8 AM</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})
</script>