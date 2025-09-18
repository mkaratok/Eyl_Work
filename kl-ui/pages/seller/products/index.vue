<template>
  <SellerSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your product catalog</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
          <button 
            @click="showXmlUpload = true"
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition-colors flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            XML Import
          </button>
          <button class="btn-primary flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add New Product
          </button>
        </div>
      </div>

      <!-- XML Upload Modal -->
      <div v-if="showXmlUpload" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
          <XmlProductUpload
            user-role="seller"
            api-endpoint="/api/v1/seller/products"
            @close="showXmlUpload = false"
            @success="handleXmlImportSuccess"
          />
        </div>
      </div>

      <!-- Products Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Product List</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                type="text" 
                placeholder="Search products..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select class="input-field px-4 py-2">
              <option>All Categories</option>
              <option>Electronics</option>
              <option>Clothing</option>
              <option>Home & Garden</option>
            </select>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3"></div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">Wireless Headphones</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: WH-001</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">Electronics</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">1,260 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-700 dark:text-gray-300">42</span>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 dark:bg-gray-700">
                    <div class="bg-green-500 h-1.5 rounded-full" style="width: 84%"></div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                    Active
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    Edit
                  </button>
                  <button class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3"></div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">Smartphone Case</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: SC-002</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">Electronics</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">380 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-700 dark:text-gray-300">38</span>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 dark:bg-gray-700">
                    <div class="bg-green-500 h-1.5 rounded-full" style="width: 76%"></div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                    Active
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    Edit
                  </button>
                  <button class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3"></div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">Bluetooth Speaker</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: BS-003</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">Electronics</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">870 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-700 dark:text-gray-300">8</span>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 dark:bg-gray-700">
                    <div class="bg-yellow-500 h-1.5 rounded-full" style="width: 16%"></div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400">
                    Low Stock
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    Edit
                  </button>
                  <button class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3"></div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">USB-C Charger</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: UC-004</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">Electronics</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">520 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-700 dark:text-gray-300">26</span>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 dark:bg-gray-700">
                    <div class="bg-green-500 h-1.5 rounded-full" style="width: 52%"></div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                    Active
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    Edit
                  </button>
                  <button class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3"></div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">Smart Watch</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: SW-005</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">Electronics</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">2,450 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-700 dark:text-gray-300">0</span>
                  <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1 dark:bg-gray-700">
                    <div class="bg-red-500 h-1.5 rounded-full" style="width: 0%"></div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400">
                    Out of Stock
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    Edit
                  </button>
                  <button class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">12</span> results
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
      
      <!-- Inventory Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Products</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">86</h3>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Low Stock</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">8</h3>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Out of Stock</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">3</h3>
            </div>
            <div class="bg-red-50 dark:bg-red-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SellerSidebar>
</template>

<script setup>
import { ref } from 'vue'
import SellerSidebar from '@/components/SellerSidebar.vue'
import XmlProductUpload from '@/components/XmlProductUpload.vue'

// Reactive data
const showXmlUpload = ref(false)

// Methods
const handleXmlImportSuccess = (results) => {
  console.log('XML import successful:', results)
  // Refresh products list here if needed
  showXmlUpload.value = false
}
</script>