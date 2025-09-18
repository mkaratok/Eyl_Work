<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-4">Frontend API Integration Test</h1>
    
    <div v-if="loading" class="text-center py-8">
      <p>Testing API integration...</p>
    </div>
    
    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <p><strong>Error:</strong> {{ error }}</p>
    </div>
    
    <div v-else class="space-y-6">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">API Health Check</h2>
        <div :class="[
          'p-4 rounded',
          apiHealth.success ? 'bg-green-100 border border-green-400' : 'bg-red-100 border border-red-400'
        ]">
          <p :class="apiHealth.success ? 'text-green-700' : 'text-red-700'">
            <strong>Status:</strong> {{ apiHealth.success ? 'Success' : 'Failed' }}
          </p>
          <p v-if="apiHealth.data" class="mt-2">
            <strong>Message:</strong> {{ apiHealth.data.message }}
          </p>
          <p v-if="apiHealth.data" class="mt-1">
            <strong>Version:</strong> {{ apiHealth.data.version }}
          </p>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Categories Data</h2>
        <div :class="[
          'p-4 rounded',
          categoriesData.success ? 'bg-green-100 border border-green-400' : 'bg-red-100 border border-red-400'
        ]">
          <p :class="categoriesData.success ? 'text-green-700' : 'text-red-700'">
            <strong>Status:</strong> {{ categoriesData.success ? 'Success' : 'Failed' }}
          </p>
          <p v-if="categoriesData.data" class="mt-2">
            <strong>Categories Loaded:</strong> {{ categoriesData.data.length }} categories
          </p>
          <div v-if="categoriesData.data && categoriesData.data.length > 0" class="mt-2">
            <p class="font-semibold">Sample Categories:</p>
            <ul class="list-disc pl-5 mt-1">
              <li v-for="category in categoriesData.data.slice(0, 3)" :key="category.id">
                {{ category.name || 'Unnamed Category' }}
              </li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">API Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p><strong>API Base URL:</strong> {{ apiConfig.API_BASE_URL }}</p>
            <p><strong>App URL:</strong> {{ apiConfig.APP_URL }}</p>
          </div>
          <div>
            <p><strong>Frontend Status:</strong> 
              <span class="text-green-500">Connected</span>
            </p>
            <p><strong>Backend Status:</strong> 
              <span :class="apiHealth.success ? 'text-green-500' : 'text-red-500'">
                {{ apiHealth.success ? 'Online' : 'Offline' }}
              </span>
            </p>
          </div>
        </div>
      </div>
      
      <div class="text-center">
        <NuxtLink to="/" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
          Return to Homepage
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '~/services/apiClient'
import { API_BASE_URL, APP_URL } from '~/services/apiConfig'

const loading = ref(true)
const error = ref(null)
const apiHealth = ref({})
const categoriesData = ref({})

const apiConfig = {
  API_BASE_URL,
  APP_URL
}

const testApiIntegration = async () => {
  try {
    // Test health endpoint
    const healthResponse = await apiClient.get('/health')
    apiHealth.value = {
      success: true,
      data: healthResponse
    }
    
    // Test categories endpoint
    const categoriesResponse = await apiClient.get('/v1/public/categories')
    // The actual categories data is in categoriesResponse.data, not categoriesResponse.data.data
    categoriesData.value = {
      success: true,
      data: Array.isArray(categoriesResponse.data) ? categoriesResponse.data : []
    }
  } catch (err) {
    error.value = err.message || 'API integration test failed'
    
    // Set failure status for both tests
    apiHealth.value = {
      success: false,
      data: null
    }
    
    categoriesData.value = {
      success: false,
      data: null
    }
  } finally {
    loading.value = false
  }
}

// Run the test when component mounts
onMounted(() => {
  testApiIntegration()
})
</script>