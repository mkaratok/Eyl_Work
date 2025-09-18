<template>
  <SellerSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Campaigns</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your marketing campaigns</p>
        </div>
        <button class="btn-primary flex items-center mt-4 md:mt-0">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create Campaign
        </button>
      </div>

      <!-- Active Campaigns -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Active Campaigns</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                type="text" 
                placeholder="Search campaigns..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select class="input-field px-4 py-2">
              <option>All Status</option>
              <option>Active</option>
              <option>Scheduled</option>
              <option>Completed</option>
            </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Campaign Card 1 -->
          <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="font-bold text-gray-900 dark:text-white">Summer Sale</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">June 1 - July 31, 2023</p>
              </div>
              <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                Active
              </span>
            </div>
            <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">
              20% off on all electronics products for summer season.
            </p>
            <div class="flex justify-between items-center mb-4">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Discount</p>
                <p class="font-bold text-gray-900 dark:text-white">20%</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Products</p>
                <p class="font-bold text-gray-900 dark:text-white">42</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Revenue</p>
                <p class="font-bold text-gray-900 dark:text-white">12,480 TL</p>
              </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 mb-2">
              <div class="bg-blue-500 h-2 rounded-full" style="width: 65%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
              <span>Progress</span>
              <span>65%</span>
            </div>
            <div class="flex space-x-2 mt-4">
              <button class="flex-1 btn-primary text-center text-sm py-2">Edit</button>
              <button class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded py-2 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition">Pause</button>
            </div>
          </div>
          
          <!-- Campaign Card 2 -->
          <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="font-bold text-gray-900 dark:text-white">Back to School</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">August 15 - September 15, 2023</p>
              </div>
              <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400">
                Scheduled
              </span>
            </div>
            <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">
              Special discounts on school supplies and electronics.
            </p>
            <div class="flex justify-between items-center mb-4">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Discount</p>
                <p class="font-bold text-gray-900 dark:text-white">15%</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Products</p>
                <p class="font-bold text-gray-900 dark:text-white">28</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Revenue</p>
                <p class="font-bold text-gray-900 dark:text-white">-</p>
              </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 mb-2">
              <div class="bg-gray-300 h-2 rounded-full" style="width: 0%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
              <span>Progress</span>
              <span>0%</span>
            </div>
            <div class="flex space-x-2 mt-4">
              <button class="flex-1 btn-primary text-center text-sm py-2">Edit</button>
              <button class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded py-2 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition">Start</button>
            </div>
          </div>
          
          <!-- Campaign Card 3 -->
          <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="font-bold text-gray-900 dark:text-white">Spring Clearance</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">April 1 - May 31, 2023</p>
              </div>
              <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800/30 dark:text-purple-400">
                Completed
              </span>
            </div>
            <p class="text-gray-700 dark:text-gray-300 text-sm mb-4">
              Clear old inventory with up to 50% off selected items.
            </p>
            <div class="flex justify-between items-center mb-4">
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Discount</p>
                <p class="font-bold text-gray-900 dark:text-white">50%</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Products</p>
                <p class="font-bold text-gray-900 dark:text-white">16</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Revenue</p>
                <p class="font-bold text-gray-900 dark:text-white">8,750 TL</p>
              </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700 mb-2">
              <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
              <span>Progress</span>
              <span>100%</span>
            </div>
            <div class="flex space-x-2 mt-4">
              <button class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded py-2 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition">View Report</button>
              <button class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded py-2 text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition">Duplicate</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Campaign Performance -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Campaign Revenue</h2>
          <div class="h-64">
            <Chart 
              type="bar" 
              :data="[4500, 5200, 4800, 6100, 5500, 6700]" 
              :labels="['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']" 
            />
          </div>
        </div>
        
        <!-- Top Performing Campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Top Performing Campaigns</h2>
          <div class="space-y-4">
            <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
              <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mr-3">
                  <span class="font-bold text-blue-500 dark:text-blue-400">1</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">Summer Sale</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">20% off electronics</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900 dark:text-white">12,480 TL</p>
                <p class="text-sm text-green-500">+15.3%</p>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
              <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center mr-3">
                  <span class="font-bold text-green-500 dark:text-green-400">2</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">New Year Special</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Bundle offers</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900 dark:text-white">9,850 TL</p>
                <p class="text-sm text-green-500">+8.7%</p>
              </div>
            </div>
            
            <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg transition-colors">
              <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mr-3">
                  <span class="font-bold text-amber-500 dark:text-amber-400">3</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900 dark:text-white">Valentine's Day</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Gift packages</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold text-gray-900 dark:text-white">7,240 TL</p>
                <p class="text-sm text-green-500">+12.1%</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Campaign Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Campaigns</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">12</h3>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Active Campaigns</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">3</h3>
            </div>
            <div class="bg-green-50 dark:bg-green-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Revenue</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">42,680 TL</h3>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Avg. Conversion</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">8.4%</h3>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SellerSidebar>
</template>

<script setup>
import SellerSidebar from '@/components/SellerSidebar.vue'
import Chart from '@/components/Chart.vue'
</script>