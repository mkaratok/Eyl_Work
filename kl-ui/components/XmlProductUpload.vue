<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">XML Product Import</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Import products from XML files (Google Merchant Center format supported)
        </p>
      </div>
      <div class="flex space-x-2">
        <button
          @click="downloadTemplate('custom')"
          class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md text-sm transition-colors"
        >
          Download Custom Template
        </button>
        <button
          @click="downloadTemplate('google_merchant')"
          class="px-3 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-md text-sm transition-colors"
        >
          Download Google Merchant Template
        </button>
      </div>
    </div>

    <!-- Upload Form -->
    <form @submit.prevent="handleUpload" class="space-y-4">
      <!-- File Upload -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          XML File *
        </label>
        <div 
          class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
          :class="{
            'border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/20': isDragOver,
            'border-green-500 dark:border-green-400 bg-green-50 dark:bg-green-900/20': selectedFile,
            'border-red-500 dark:border-red-400 bg-red-50 dark:bg-red-900/20': uploadErrors.file
          }"
          @dragover.prevent="isDragOver = true"
          @dragleave.prevent="isDragOver = false"
          @drop.prevent="handleFileDrop"
        >
          <div v-if="!selectedFile">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="mt-4">
              <label for="xml-file" class="cursor-pointer">
                <span class="text-blue-600 dark:text-blue-400 font-medium hover:text-blue-500 dark:hover:text-blue-300">
                  Upload XML file
                </span>
                <span class="text-gray-500 dark:text-gray-400"> or drag and drop</span>
              </label>
              <input
                id="xml-file"
                ref="fileInput"
                type="file"
                accept=".xml"
                class="hidden"
                @change="handleFileSelect"
              >
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">XML files up to 50MB</p>
          </div>
          
          <div v-else class="flex items-center justify-center">
            <div class="flex items-center space-x-3">
              <svg class="h-8 w-8 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedFile.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
              <button
                type="button"
                @click="removeFile"
                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div v-if="uploadErrors.file" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ uploadErrors.file }}
        </div>
      </div>

      <!-- Import Settings -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Import Type *
          </label>
          <select 
            v-model="importConfig.import_type" 
            required 
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="custom_xml">Custom XML Format</option>
            <option value="google_merchant">Google Merchant Center</option>
          </select>
        </div>

        <div v-if="categories.length > 0">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Default Category
          </label>
          <select 
            v-model="importConfig.default_category_id" 
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Select default category</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Checkboxes -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-3">
          <label class="flex items-center">
            <input 
              v-model="importConfig.update_existing" 
              type="checkbox" 
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Update existing products</span>
          </label>
          
          <label class="flex items-center">
            <input 
              v-model="importConfig.create_categories" 
              type="checkbox" 
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Create missing categories</span>
          </label>
        </div>

        <div class="space-y-3">
          <label v-if="userRole === 'admin' || userRole === 'super_admin'" class="flex items-center">
            <input 
              v-model="importConfig.auto_approve" 
              type="checkbox" 
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Auto-approve products</span>
          </label>
          
          <label class="flex items-center">
            <input 
              v-model="importConfig.preview_only" 
              type="checkbox" 
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Preview only (don't import)</span>
          </label>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
        >
          Cancel
        </button>
        <button
          type="submit"
          :disabled="!selectedFile || uploading"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-md transition-colors flex items-center"
        >
          <svg v-if="uploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ uploading ? 'Importing...' : (importConfig.preview_only ? 'Preview Import' : 'Import Products') }}
        </button>
      </div>
    </form>

    <!-- Import Results -->
    <div v-if="importResults" class="mt-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Import Results</h4>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ importResults.total_products }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Total</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ importResults.created }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Created</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ importResults.updated }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Updated</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ importResults.errors?.length || 0 }}</div>
          <div class="text-xs text-gray-600 dark:text-gray-400">Errors</div>
        </div>
      </div>

      <!-- Errors and Warnings -->
      <div v-if="importResults.errors?.length > 0" class="mb-4">
        <h5 class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">Errors:</h5>
        <ul class="text-sm text-red-600 dark:text-red-400 space-y-1 max-h-32 overflow-y-auto">
          <li v-for="(error, index) in importResults.errors" :key="index" class="flex items-start">
            <span class="mr-2">•</span>
            <span>{{ error }}</span>
          </li>
        </ul>
      </div>

      <div v-if="importResults.warnings?.length > 0">
        <h5 class="text-sm font-medium text-yellow-600 dark:text-yellow-400 mb-2">Warnings:</h5>
        <ul class="text-sm text-yellow-600 dark:text-yellow-400 space-y-1 max-h-32 overflow-y-auto">
          <li v-for="(warning, index) in importResults.warnings" :key="index" class="flex items-start">
            <span class="mr-2">•</span>
            <span>{{ warning }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="uploadErrors.general" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">Import Failed</h4>
      <p class="text-sm text-red-700 dark:text-red-300">{{ uploadErrors.general }}</p>
      <button 
        @click="uploadErrors.general = null"
        class="mt-2 text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200"
      >
        Dismiss
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '~/services/apiClient'
import { useCategories } from '~/composables/useCategories'

// Props and Emits
const props = defineProps({
  userRole: {
    type: String,
    default: 'seller'
  },
  apiEndpoint: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

// Composables
const { categories, fetchCategories } = useCategories()

// Reactive data
const selectedFile = ref(null)
const isDragOver = ref(false)
const uploading = ref(false)
const uploadErrors = ref({})
const importResults = ref(null)
const fileInput = ref(null)

const importConfig = ref({
  import_type: 'custom_xml',
  update_existing: true,
  auto_approve: false,
  create_categories: false,
  default_category_id: '',
  preview_only: false
})

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const handleFileDrop = (event) => {
  isDragOver.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    validateAndSetFile(file)
  }
}

const validateAndSetFile = (file) => {
  uploadErrors.value = {}
  
  // Validate file type
  if (!file.name.toLowerCase().endsWith('.xml')) {
    uploadErrors.value.file = 'Please select an XML file'
    return
  }
  
  // Validate file size (50MB)
  if (file.size > 50 * 1024 * 1024) {
    uploadErrors.value.file = 'File size must be less than 50MB'
    return
  }
  
  selectedFile.value = file
}

const removeFile = () => {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  importResults.value = null
  uploadErrors.value = {}
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const downloadTemplate = async (type) => {
  try {
    // Create a temporary link to download the template
    let templateContent = '';
    let filename = '';
    
    if (type === 'google_merchant') {
      templateContent = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Your Store</title>
    <link>https://yourstore.com</link>
    <description>Product feed for Google Merchant Center</description>
    <item>
      <g:id>SKU001</g:id>
      <title>Sample Product</title>
      <description>Product description</description>
      <g:link>https://yourstore.com/product/sku001</g:link>
      <g:image_link>https://yourstore.com/images/sku001.jpg</g:image_link>
      <g:condition>new</g:condition>
      <g:availability>in stock</g:availability>
      <g:price>99.99 USD</g:price>
      <g:brand>Sample Brand</g:brand>
      <g:gtin>1234567890123</g:gtin>
      <g:mpn>MPN001</g:mpn>
      <g:product_type>Electronics > Computers</g:product_type>
      <g:google_product_category>Electronics</g:google_product_category>
    </item>
  </channel>
</rss>`;
      filename = 'google_merchant_template.xml';
    } else {
      templateContent = `<?xml version="1.0" encoding="UTF-8"?>
<products>
  <product>
    <name>Sample Product</name>
    <description>Product description here</description>
    <sku>SAMPLE-SKU-001</sku>
    <barcode>1234567890123</barcode>
    <brand>Sample Brand</brand>
    <category>Electronics</category>
    <price>99.99</price>
    <image>https://example.com/image.jpg</image>
  </product>
</products>`;
      filename = 'custom_xml_template.xml';
    }
    
    // Create and download the file
    const blob = new Blob([templateContent], { type: 'application/xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
  } catch (error) {
    console.error('Failed to download template:', error)
    uploadErrors.value.general = 'Failed to generate template: ' + (error.message || 'Unknown error')
  }
}

const handleUpload = async () => {
  if (!selectedFile.value) {
    uploadErrors.value.file = 'Please select a file'
    return
  }
  
  uploading.value = true
  uploadErrors.value = {}
  importResults.value = null
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    
    // Add import configuration
    // Make sure to include the required import_type field
    formData.append('import_type', importConfig.value.import_type || 'custom_xml');
    
    // Add other configuration options
    Object.keys(importConfig.value).forEach(key => {
      if (key !== 'import_type' && importConfig.value[key] !== '' && importConfig.value[key] !== null) {
        formData.append(key, importConfig.value[key])
      }
    })
    
    // Instead of trying to construct the endpoint from props, use a hardcoded path
    // that we know matches the backend route exactly
    
    // The backend route is defined in api.php as:
    // Route::post('/products/import-xml', [AdminProductController::class, 'importXml']);
    // Under the admin prefix
    
    // For admin role, use the admin endpoint
    let endpoint;
    if (props.userRole === 'admin') {
      endpoint = 'v1/admin/products/import-xml';
    } else {
      // For seller role, use the seller endpoint
      endpoint = 'v1/seller/products/import-xml';
    }
    
    console.log('Using hardcoded endpoint for XML import:', endpoint);
    console.log('User role:', props.userRole);
    console.log('API base URL from apiClient:', apiClient.baseURL);
    
    // Log the full URL that will be used
    const fullUrl = `${apiClient.baseURL}/${endpoint}`;
    console.log('Full API URL that will be used:', fullUrl);
    
    console.log('Sending XML import request to endpoint:', endpoint);
    
    // Initialize CSRF token before making the request
    try {
      await apiClient.initializeCsrf();
      console.log('CSRF token initialized successfully');
    } catch (csrfError) {
      console.warn('Failed to initialize CSRF token:', csrfError);
    }
    
    // Use apiClient to make an authenticated API request
    const response = await apiClient.post(endpoint, formData)
    
    console.log('XML import response:', response);
    
    // apiClient already returns parsed JSON
    if (response.success) {
      importResults.value = response.data;
      emit('success', response.data);
      
      if (!importConfig.value.preview_only) {
        // Show success message for a few seconds before clearing
        setTimeout(() => {
          removeFile();
          importResults.value = null;
        }, 10000);
      }
    } else {
      const errorMessage = response.message || response.error || 'Upload failed';
      uploadErrors.value.general = errorMessage;
      console.error('XML import failed:', errorMessage);
      
      // If we have detailed error data, show it
      if (response.data) {
        importResults.value = response.data;
      }
    }
    
  } catch (error) {
    console.error('Upload error:', error)
    
    // Handle error from apiClient
    if (error.data) {
      // API returned an error response
      const errorMessage = error.data.message || error.message || 'Upload failed'
      uploadErrors.value.general = errorMessage
      console.error('API error details:', error.data)
      
      // Handle specific status codes
      if (error.status === 401 || error.status === 403) {
        uploadErrors.value.general = 'Authentication failed. Please log in again.'
      } else if (error.status === 404) {
        uploadErrors.value.general = 'API endpoint not found. Please check that the server is running and the API endpoint is correct.'
      } else if (error.status === 422) {
        uploadErrors.value.general = 'The uploaded file is invalid. Please check the file format and try again.'
      }
    } else {
      // Network or other error
      const errorMessage = error.message || 'Upload failed'
      uploadErrors.value.general = errorMessage
      
      // Show a more user-friendly error message for network errors
      if (errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
        uploadErrors.value.general = 'Connection failed. Please check your internet connection and try again.'
      }
    }
  } finally {
    uploading.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchCategories()
})
</script>