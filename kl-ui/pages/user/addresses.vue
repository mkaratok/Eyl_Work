<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Shipping Addresses</h1>
      <NuxtLink to="/user" class="text-primary hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
        ← Back to Dashboard
      </NuxtLink>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Add New Address</h2>
        <form @submit.prevent="addAddress">
          <div class="mb-4">
            <label for="address-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address Title</label>
            <input
              id="address-title"
              v-model="newAddress.title"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              placeholder="Home, Work, etc."
              required
            />
          </div>
          
          <div class="mb-4">
            <label for="address-line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address Line 1</label>
            <input
              id="address-line1"
              v-model="newAddress.line1"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              placeholder="Street address"
              required
            />
          </div>
          
          <div class="mb-4">
            <label for="address-line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address Line 2</label>
            <input
              id="address-line2"
              v-model="newAddress.line2"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              placeholder="Apartment, suite, etc. (optional)"
            />
          </div>
          
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">City</label>
              <input
                id="city"
                v-model="newAddress.city"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                placeholder="City"
                required
              />
            </div>
            
            <div>
              <label for="postal-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Postal Code</label>
              <input
                id="postal-code"
                v-model="newAddress.postalCode"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                placeholder="Postal code"
                required
              />
            </div>
          </div>
          
          <button
            type="submit"
            class="w-full bg-primary hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
          >
            Add Address
          </button>
        </form>
      </div>
      
      <div v-for="address in addresses" :key="address.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ address.title }}</h3>
          <button @click="removeAddress(address.id)" class="text-red-500 hover:text-red-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
        
        <p class="text-gray-700 dark:text-gray-300 mb-2">{{ address.line1 }}</p>
        <p v-if="address.line2" class="text-gray-700 dark:text-gray-300 mb-2">{{ address.line2 }}</p>
        <p class="text-gray-700 dark:text-gray-300">{{ address.city }}, {{ address.postalCode }}</p>
        
        <button class="mt-4 text-primary hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
          Edit Address
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

definePageMeta({
  middleware: 'auth'
})

const addresses = ref([
  {
    id: 1,
    title: 'Home',
    line1: '123 Main Street',
    line2: 'Apartment 4B',
    city: 'Istanbul',
    postalCode: '34000'
  },
  {
    id: 2,
    title: 'Work',
    line1: '456 Business Avenue',
    line2: 'Office 1200',
    city: 'Istanbul',
    postalCode: '34100'
  }
])

const newAddress = ref({
  title: '',
  line1: '',
  line2: '',
  city: '',
  postalCode: ''
})

const addAddress = () => {
  if (newAddress.value.title && newAddress.value.line1 && newAddress.value.city && newAddress.value.postalCode) {
    addresses.value.push({
      id: addresses.value.length + 1,
      ...newAddress.value
    })
    
    // Reset form
    newAddress.value = {
      title: '',
      line1: '',
      line2: '',
      city: '',
      postalCode: ''
    }
  }
}

const removeAddress = (id) => {
  addresses.value = addresses.value.filter(address => address.id !== id)
}
</script>