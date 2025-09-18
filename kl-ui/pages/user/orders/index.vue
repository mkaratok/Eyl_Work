<template>
  <UserSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Orders</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">View and manage your orders</p>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Order History</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                type="text" 
                placeholder="Search orders..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select class="input-field px-4 py-2">
              <option>All Statuses</option>
              <option>Pending</option>
              <option>Processing</option>
              <option>Shipped</option>
              <option>Delivered</option>
              <option>Cancelled</option>
            </select>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Items</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-500 dark:text-blue-400">#ORD-001</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2023-06-12</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">3 items</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">1,260 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                    Delivered
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    View
                  </button>
                  <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Invoice
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-500 dark:text-blue-400">#ORD-002</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2023-06-11</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1 item</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">380 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400">
                    Shipped
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    View
                  </button>
                  <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Invoice
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-500 dark:text-blue-400">#ORD-003</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2023-06-10</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2 items</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">2,450 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400">
                    Processing
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    View
                  </button>
                  <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Invoice
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-500 dark:text-blue-400">#ORD-004</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2023-06-09</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1 item</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">870 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800/30 dark:text-purple-400">
                    Pending
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    View
                  </button>
                  <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Invoice
                  </button>
                </td>
              </tr>
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-500 dark:text-blue-400">#ORD-005</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2023-06-08</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2 items</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">520 TL</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400">
                    Cancelled
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                    View
                  </button>
                  <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Invoice
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">48</span> results
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
    </div>
  </UserSidebar>
</template>

<script setup>
import UserSidebar from '@/components/UserSidebar.vue'
</script>