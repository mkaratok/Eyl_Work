<template>
  <AdminSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reports</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Platform analytics and insights</p>
        </div>
        <div class="flex space-x-3 mt-4 md:mt-0">
          <select 
            v-model="selectedPeriod"
            @change="handlePeriodChange"
            class="input-field px-4 py-2"
          >
            <option value="7">Last 7 Days</option>
            <option value="30">Last 30 Days</option>
            <option value="90">Last 90 Days</option>
            <option value="365">Year to Date</option>
          </select>
          <button 
            @click="exportReport"
            class="btn-primary flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Export Report
          </button>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Revenue</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">128,480 TL</h3>
              <p class="text-green-500 text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                <span>15.3% increase</span>
              </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Orders</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">1,842</h3>
              <p class="text-green-500 text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                <span>12.7% increase</span>
              </p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Conversion Rate</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">3.8%</h3>
              <p class="text-red-500 text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <span>0.2% decrease</span>
              </p>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Avg. Order Value</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">698 TL</h3>
              <p class="text-green-500 text-sm mt-2 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
                <span>5.4% increase</span>
              </p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Revenue Trend</h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
              <button 
                @click="setRevenueChartPeriod('month')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  revenueChartPeriod === 'month' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Month
              </button>
              <button 
                @click="setRevenueChartPeriod('quarter')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  revenueChartPeriod === 'quarter' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Quarter
              </button>
              <button 
                @click="setRevenueChartPeriod('year')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  revenueChartPeriod === 'year' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Year
              </button>
            </div>
          </div>
          <div class="h-72">
            <Chart 
              type="line" 
              :data="revenueChartData" 
              :labels="revenueChartLabels" 
            />
          </div>
        </div>

        <!-- Orders Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Orders Trend</h2>
            <div class="flex space-x-2 mt-2 sm:mt-0">
              <button 
                @click="setOrdersChartPeriod('month')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  ordersChartPeriod === 'month' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Month
              </button>
              <button 
                @click="setOrdersChartPeriod('quarter')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  ordersChartPeriod === 'quarter' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Quarter
              </button>
              <button 
                @click="setOrdersChartPeriod('year')"
                :class="[
                  'text-sm px-3 py-1 rounded transition-colors',
                  ordersChartPeriod === 'year' 
                    ? 'bg-blue-500 text-white' 
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                ]"
              >
                Year
              </button>
            </div>
          </div>
          <div class="h-72">
            <Chart 
              type="bar" 
              :data="ordersChartData" 
              :labels="ordersChartLabels" 
            />
          </div>
        </div>
      </div>

      <!-- Top Products and Categories -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Products -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Top Selling Products</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sales</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Wireless Headphones</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1,248</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">158,480 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Smartphone Case</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">986</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">42,800 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Bluetooth Speaker</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">752</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">65,400 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">USB-C Charger</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">634</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">32,800 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Smart Watch</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">521</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">1,280,480 TL</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top Categories -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Top Categories</h2>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead>
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Products</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Revenue</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Electronics</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">2,486</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">2,158,480 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Clothing</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1,842</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">842,800 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Home & Garden</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">1,248</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">665,400 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Books</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">986</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">332,800 TL</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Sports</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">752</td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">280,480 TL</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AdminSidebar>
</template>

<script setup>
import { ref, computed } from 'vue'
import AdminSidebar from '@/components/AdminSidebar.vue'
import Chart from '@/components/Chart.vue'

definePageMeta({
  middleware: 'auth'
})

// Reactive state
const selectedPeriod = ref('30')
const revenueChartPeriod = ref('month')
const ordersChartPeriod = ref('month')

// Chart data
const chartDataSets = {
  revenue: {
    month: {
      data: [128000, 132000, 124000, 142000, 138000, 148000, 152000],
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
    },
    quarter: {
      data: [384000, 420000, 456000, 510000],
      labels: ['Q1', 'Q2', 'Q3', 'Q4']
    },
    year: {
      data: [1200000, 1450000, 1680000, 1920000, 2100000],
      labels: ['2019', '2020', '2021', '2022', '2023']
    }
  },
  orders: {
    month: {
      data: [1280, 1320, 1240, 1420, 1380, 1480, 1520],
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
    },
    quarter: {
      data: [3840, 4200, 4560, 5100],
      labels: ['Q1', 'Q2', 'Q3', 'Q4']
    },
    year: {
      data: [12000, 14500, 16800, 19200, 21000],
      labels: ['2019', '2020', '2021', '2022', '2023']
    }
  }
}

// Computed properties for chart data
const revenueChartData = computed(() => {
  return chartDataSets.revenue[revenueChartPeriod.value].data
})

const revenueChartLabels = computed(() => {
  return chartDataSets.revenue[revenueChartPeriod.value].labels
})

const ordersChartData = computed(() => {
  return chartDataSets.orders[ordersChartPeriod.value].data
})

const ordersChartLabels = computed(() => {
  return chartDataSets.orders[ordersChartPeriod.value].labels
})

// Methods
const handlePeriodChange = () => {
  console.log('Period changed to:', selectedPeriod.value)
  // Here you would typically fetch new data based on the selected period
}

const exportReport = () => {
  console.log('Exporting report for period:', selectedPeriod.value)
  
  // Simple CSV export functionality
  const csvData = [
    ['Metric', 'Value'],
    ['Total Revenue', '128,480 TL'],
    ['Total Orders', '1,842'],
    ['Conversion Rate', '3.8%'],
    ['Avg. Order Value', '698 TL']
  ]
  
  const csvContent = csvData.map(row => row.join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `report_${selectedPeriod.value}_days.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

const setRevenueChartPeriod = (period) => {
  revenueChartPeriod.value = period
  console.log('Revenue chart period changed to:', period)
}

const setOrdersChartPeriod = (period) => {
  ordersChartPeriod.value = period
  console.log('Orders chart period changed to:', period)
}
</script>

<style scoped>
.btn-primary {
  @apply bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors;
}

.input-field {
  @apply border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>