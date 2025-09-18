<template>
  <UserSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Addresses</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your shipping addresses</p>
        </div>
        <button 
          @click="openAddressForm"
          class="btn-primary flex items-center mt-4 md:mt-0"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Add New Address
        </button>
      </div>

      <!-- Addresses Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Address Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Home</h3>
            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400">
              Default
            </span>
          </div>
          <div class="text-gray-700 dark:text-gray-300 mb-6">
            <p>John Doe</p>
            <p>123 Main Street</p>
            <p>Istanbul, 34000</p>
            <p>Turkey</p>
          </div>
          <div class="flex space-x-3">
            <button 
              @click="editAddress"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
            >
              Edit
            </button>
            <button 
              @click="setDefaultAddress"
              class="flex-1 btn-primary"
            >
              Set Default
            </button>
          </div>
        </div>

        <!-- Address Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Office</h3>
          </div>
          <div class="text-gray-700 dark:text-gray-300 mb-6">
            <p>John Doe</p>
            <p>456 Business Avenue</p>
            <p>Ankara, 06000</p>
            <p>Turkey</p>
          </div>
          <div class="flex space-x-3">
            <button 
              @click="editAddress"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
            >
              Edit
            </button>
            <button 
              @click="setDefaultAddress"
              class="flex-1 btn-primary"
            >
              Set Default
            </button>
          </div>
        </div>

        <!-- Empty Address Card (Add New) -->
        <div 
          @click="openAddressForm"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-primary dark:hover:border-primary cursor-pointer flex flex-col items-center justify-center h-full min-h-[200px]"
        >
          <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Add New Address</h3>
          <p class="text-gray-500 dark:text-gray-400 text-center">Click to add a new shipping address</p>
        </div>
      </div>

      <!-- Address Form Modal -->
      <div v-if="showAddressForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md dark:bg-gray-800">
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ editingAddress ? 'Edit Address' : 'Add New Address' }}</h3>
              <button 
                @click="closeAddressForm"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="saveAddress" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address Name</label>
                <input 
                  v-model="addressForm.name" 
                  type="text" 
                  class="input-field"
                  placeholder="Home, Office, etc."
                  required
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                <input 
                  v-model="addressForm.fullName" 
                  type="text" 
                  class="input-field"
                  required
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Street Address</label>
                <input 
                  v-model="addressForm.street" 
                  type="text" 
                  class="input-field"
                  required
                />
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                  <input 
                    v-model="addressForm.city" 
                    type="text" 
                    class="input-field"
                    required
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Postal Code</label>
                  <input 
                    v-model="addressForm.postalCode" 
                    type="text" 
                    class="input-field"
                    required
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                <select 
                  v-model="addressForm.country" 
                  class="input-field"
                  required
                >
                  <option value="">Select Country</option>
                  <option value="Turkey">Turkey</option>
                  <option value="United States">United States</option>
                  <option value="United Kingdom">United Kingdom</option>
                  <option value="Germany">Germany</option>
                </select>
              </div>
              
              <div class="flex items-center">
                <input 
                  v-model="addressForm.isDefault" 
                  type="checkbox" 
                  id="default-address"
                  class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                />
                <label for="default-address" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                  Set as default address
                </label>
              </div>
              
              <div class="pt-4 flex space-x-3">
                <button 
                  type="button"
                  @click="closeAddressForm"
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                >
                  Cancel
                </button>
                <button 
                  type="submit"
                  class="flex-1 btn-primary"
                >
                  Save Address
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </UserSidebar>
</template>

<script setup>
import UserSidebar from '@/components/UserSidebar.vue'
import { ref } from 'vue'

const showAddressForm = ref(false)
const editingAddress = ref(false)

const addressForm = ref({
  id: null,
  name: '',
  fullName: '',
  street: '',
  city: '',
  postalCode: '',
  country: '',
  isDefault: false
})

const openAddressForm = () => {
  showAddressForm.value = true
  editingAddress.value = false
  addressForm.value = {
    id: null,
    name: '',
    fullName: '',
    street: '',
    city: '',
    postalCode: '',
    country: '',
    isDefault: false
  }
}

const editAddress = () => {
  showAddressForm.value = true
  editingAddress.value = true
}

const closeAddressForm = () => {
  showAddressForm.value = false
}

const saveAddress = () => {
  // In a real app, you would save this data to your backend
  console.log('Saving address:', addressForm.value)
  closeAddressForm()
}

const setDefaultAddress = () => {
  // In a real app, you would update the default address on your backend
  console.log('Setting default address')
}
</script>