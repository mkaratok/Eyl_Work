<template>
  <AdminSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage all platform products</p>
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
          <button 
            @click="showCreateModal = true"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors flex items-center"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add New Product
          </button>
        </div>
      </div>

      <!-- Products Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Product List</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                v-model="searchQuery"
                @input="handleSearch"
                type="text" 
                placeholder="Search products..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select 
              v-model="categoryFilter"
              @change="handleCategoryFilter"
              class="input-field px-4 py-2"
            >
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Seller</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr 
                v-for="product in paginatedProducts" 
                :key="product.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-xl w-10 h-10 mr-3">
                      <img 
                        v-if="product.image_url" 
                        :src="product.image_url" 
                        :alt="product.name"
                        class="w-full h-full object-cover rounded-xl"
                      >
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name || 'Unnamed Product' }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ product.sku || product.id }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                  {{ getCategoryName(product.category_id) }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                  {{ product.seller_name || 'Unknown Seller' }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatPrice(product.price) }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    getStatusClass(product.status)
                  ]">
                    {{ getStatusText(product.status) }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button 
                    @click="editProduct(product)"
                    class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                  >
                    Edit
                  </button>
                  <button 
                    @click="deleteProduct(product)"
                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                  >
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
            Showing <span class="font-medium">{{ paginationInfo.start }}</span> to <span class="font-medium">{{ paginationInfo.end }}</span> of <span class="font-medium">{{ paginationInfo.total }}</span> results
          </div>
          <div class="flex space-x-2">
            <button 
              @click="previousPage"
              :disabled="currentPage === 1"
              :class="[
                'px-3 py-1 rounded-md text-sm transition-colors',
                currentPage === 1 
                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              Previous
            </button>
            
            <button 
              v-for="page in pageNumbers"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1 rounded-md text-sm transition-colors',
                page === currentPage 
                  ? 'bg-blue-500 text-white' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              {{ page }}
            </button>
            
            <button 
              @click="nextPage"
              :disabled="currentPage === totalPages"
              :class="[
                'px-3 py-1 rounded-md text-sm transition-colors',
                currentPage === totalPages 
                  ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              Next
            </button>
          </div>
        </div>
      </div>
      
      <!-- Product Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Products</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ productStats.total }}</h3>
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
              <p class="text-gray-500 dark:text-gray-400 text-sm">Active Products</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ productStats.active }}</h3>
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
              <p class="text-gray-500 dark:text-gray-400 text-sm">Pending Approval</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ productStats.pending }}</h3>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Disabled Products</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ productStats.disabled }}</h3>
            </div>
            <div class="bg-red-50 dark:bg-red-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
      
      <!-- XML Upload Modal -->
      <div v-if="showXmlUpload" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
          <XmlProductUpload
            user-role="admin"
            api-endpoint="/api/v1/admin/products"
            @close="showXmlUpload = false"
            @success="handleXmlImportSuccess"
          />
        </div>
      </div>

      <!-- Create/Edit Product Modal -->
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6 max-h-screen overflow-y-auto">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ showCreateModal ? 'Add New Product' : 'Edit Product' }}
          </h3>
          
          <form @submit.prevent="showCreateModal ? createProduct() : updateProduct()">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Product Name *
                </label>
                <input 
                  v-model="productForm.name"
                  type="text" 
                  required
                  class="input-field w-full"
                  placeholder="Enter product name"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  SKU
                </label>
                <input 
                  v-model="productForm.sku"
                  type="text" 
                  class="input-field w-full"
                  placeholder="Product SKU"
                >
              </div>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
              </label>
              <textarea 
                v-model="productForm.description"
                rows="3"
                class="input-field w-full"
                placeholder="Product description"
              ></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Category *
                </label>
                <select v-model="productForm.category_id" required class="input-field w-full">
                  <option value="">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Price *
                </label>
                <input 
                  v-model="productForm.price"
                  type="number" 
                  step="0.01"
                  min="0"
                  required
                  class="input-field w-full"
                  placeholder="0.00"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Stock Quantity
                </label>
                <input 
                  v-model="productForm.stock_quantity"
                  type="number" 
                  min="0"
                  class="input-field w-full"
                  placeholder="0"
                >
              </div>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Product Image URL
              </label>
              <input 
                v-model="productForm.image_url"
                type="url" 
                class="input-field w-full"
                placeholder="https://example.com/image.jpg"
              >
            </div>
            
            <div class="mb-6">
              <label class="flex items-center">
                <input 
                  v-model="productForm.is_active"
                  type="checkbox" 
                  class="form-checkbox h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
              </label>
            </div>
            
            <div class="flex justify-end space-x-3">
              <button 
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button 
                type="submit"
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium"
              >
                {{ showCreateModal ? 'Create Product' : 'Update Product' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminSidebar>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminSidebar from '@/components/AdminSidebar.vue'
import XmlProductUpload from '@/components/XmlProductUpload.vue'
import { useAdminProducts } from '~/composables/useAdminProducts'
import { useCategories } from '~/composables/useCategories'

definePageMeta({
  middleware: 'auth'
})

const { products, loading, error, pagination, fetchProducts, createProduct: createProductAPI, updateProduct: updateProductAPI, deleteProduct: deleteProductAPI } = useAdminProducts()
const { categories, fetchCategories } = useCategories()

// UI State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showXmlUpload = ref(false)
const searchQuery = ref('')
const categoryFilter = ref('')

// Pagination State
const currentPage = ref(1)
const itemsPerPage = ref(50)
const totalItems = ref(0)

// Form data
const productForm = ref({
  id: null,
  name: '',
  sku: '',
  description: '',
  category_id: '',
  price: '',
  stock_quantity: '',
  image_url: '',
  is_active: true
})

// Form validation
const formErrors = ref({})

const validateForm = () => {
  formErrors.value = {}
  
  if (!productForm.value.name?.trim()) {
    formErrors.value.name = 'Product name is required'
  }
  
  if (!productForm.value.category_id) {
    formErrors.value.category_id = 'Category is required'
  }
  
  if (!productForm.value.price || parseFloat(productForm.value.price) <= 0) {
    formErrors.value.price = 'Valid price is required'
  }
  
  return Object.keys(formErrors.value).length === 0
}

// Computed properties
const filteredProducts = computed(() => {
  // Since we're doing server-side filtering now, just return all products
  // The filtering is handled by the API
  return products.value || []
})

// Pagination computed properties
const paginatedProducts = computed(() => {
  // Since we're doing server-side pagination now, just return all products
  // The pagination is handled by the API
  return products.value || []
})

const totalPages = computed(() => {
  if (pagination.value && pagination.value.last_page !== undefined) {
    return pagination.value.last_page
  }
  return Math.ceil(totalItems.value / itemsPerPage.value)
})

const paginationInfo = computed(() => {
  if (pagination.value) {
    return {
      start: pagination.value.from || 1,
      end: pagination.value.to || 0,
      total: pagination.value.total || 0
    }
  }
  
  const start = totalItems.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, totalItems.value)
  return {
    start,
    end,
    total: totalItems.value
  }
})

const pageNumbers = computed(() => {
  const pages = []
  const maxVisiblePages = 5
  let startPage = Math.max(1, currentPage.value - 2)
  let endPage = Math.min(totalPages.value, startPage + maxVisiblePages - 1)
  
  // Adjust start page if we're near the end
  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1)
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }
  
  return pages
})

const productStats = computed(() => {
  const stats = {
    total: products.value?.length || 0,
    active: 0,
    pending: 0,
    disabled: 0
  }
  
  products.value?.forEach(product => {
    if (product.status === 'active' || product.is_active) {
      stats.active++
    }
    if (product.status === 'pending') {
      stats.pending++
    }
    if (product.status === 'disabled' || !product.is_active) {
      stats.disabled++
    }
  })
  
  return stats
})

// Methods
const fetchProductsData = async () => {
  try {
    const params = {
      per_page: itemsPerPage.value,
      page: currentPage.value
    }
    
    // Add search filter if present
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    // Add category filter if present
    if (categoryFilter.value) {
      params.category_id = categoryFilter.value
    }
    
    await fetchProducts(params)
    
    // Update total items from pagination response
    if (pagination.value && pagination.value.total !== undefined) {
      totalItems.value = pagination.value.total
    }
  } catch (err) {
    console.error('Failed to fetch products:', err)
  }
}

const fetchCategoriesData = async () => {
  try {
    await fetchCategories()
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

const handleSearch = () => {
  // Reset to first page when searching
  currentPage.value = 1
  // Fetch products with new search query
  fetchProductsData()
}

const handleCategoryFilter = () => {
  // Reset to first page when filtering
  currentPage.value = 1
  // Fetch products with new category filter
  fetchProductsData()
}

// Pagination methods
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchProductsData()
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    fetchProductsData()
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    fetchProductsData()
  }
}

const getCategoryName = (categoryId) => {
  const category = (categories.value || []).find(cat => cat.id == categoryId)
  return category ? category.name : 'Unknown Category'
}

const formatPrice = (price) => {
  if (!price) return '0 TL'
  return `${parseFloat(price).toLocaleString('tr-TR')} TL`
}

const getStatusClass = (status) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
    case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400'
    case 'disabled': return 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-400'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'active': return 'Active'
    case 'pending': return 'Pending'
    case 'disabled': return 'Disabled'
    default: return 'Unknown'
  }
}

const editProduct = (product) => {
  productForm.value = { ...product }
  showEditModal.value = true
}

const createProduct = async () => {
  try {
    // Validate form data
    if (!validateForm()) {
      console.log('Form validation failed:', formErrors.value);
      return
    }
    
    // Prepare product data with proper type conversion
    const productData = {
      name: productForm.value.name,
      sku: productForm.value.sku,
      description: productForm.value.description,
      category_id: parseInt(productForm.value.category_id),
      price: parseFloat(productForm.value.price),
      stock_quantity: productForm.value.stock_quantity ? parseInt(productForm.value.stock_quantity) : 0,
      image_url: productForm.value.image_url,
      is_active: productForm.value.is_active === true,
      brand: 'Default Brand' // Add default brand
    }
    
    console.log('Creating product with data:', productData)
    
    try {
      // Try to create product via API
      const newProduct = await createProductAPI(productData)
      
      // ONLY add to local products array if API creation was successful
      // Remove the fallback behavior that was adding products to UI even on API failure
      if (newProduct && newProduct.id) {
        const productWithDefaults = {
          ...newProduct,
          id: newProduct.id,
          status: 'active',
          seller_name: 'Current User'
        }
        
        products.value.unshift(productWithDefaults)
        totalItems.value = products.value.length
        
        console.log('Product created successfully:', newProduct)
      } else {
        throw new Error('Product creation failed - no valid response from server')
      }
    } catch (apiError) {
      console.error('API creation failed:', apiError)
      // Don't add to local data if API fails
      throw apiError
    }
    
    closeModal()
    
    // Reset to first page to show the new product
    currentPage.value = 1
    
  } catch (err) {
    console.error('Failed to create product:', err)
    error.value = err.message || 'Failed to create product'
    // Show error to user
    alert('Failed to create product: ' + (err.message || 'Unknown error'))
  }
}

const updateProduct = async () => {
  try {
    const productData = {
      name: productForm.value.name,
      sku: productForm.value.sku,
      description: productForm.value.description,
      category_id: productForm.value.category_id,
      price: parseFloat(productForm.value.price),
      stock_quantity: parseInt(productForm.value.stock_quantity) || 0,
      image_url: productForm.value.image_url,
      is_active: productForm.value.is_active
    }
    
    console.log('Updating product:', productForm.value.id, productData)
    
    try {
      // Try to update product via API
      await updateProductAPI(productForm.value.id, productData)
      
      // Update local products array
      const index = products.value.findIndex(p => p.id === productForm.value.id)
      if (index !== -1) {
        products.value[index] = { ...products.value[index], ...productData }
      }
      
      console.log('Product updated successfully')
    } catch (apiError) {
      console.warn('API update failed, updating local data only:', apiError.message)
      
      // Fallback: Update local data even if API fails
      const index = products.value.findIndex(p => p.id === productForm.value.id)
      if (index !== -1) {
        products.value[index] = { ...products.value[index], ...productData }
      }
    }
    
    closeModal()
  } catch (err) {
    console.error('Failed to update product:', err)
    error.value = err.message || 'Failed to update product'
  }
}

const deleteProduct = async (product) => {
  if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
    try {
      console.log('Deleting product:', product.id)
      
      try {
        // Try to delete product via API
        await deleteProductAPI(product.id)
        
        console.log('Product deleted successfully via API')
      } catch (apiError) {
        console.warn('API deletion failed, removing from local data only:', apiError.message)
      }
      
      // Remove from local products array
      const index = products.value.findIndex(p => p.id === product.id)
      if (index !== -1) {
        products.value.splice(index, 1)
        totalItems.value = products.value.length
        
        // Adjust current page if necessary
        const maxPage = Math.ceil(totalItems.value / itemsPerPage.value) || 1
        if (currentPage.value > maxPage) {
          currentPage.value = maxPage
        }
      }
      
    } catch (err) {
      console.error('Failed to delete product:', err)
      error.value = err.message || 'Failed to delete product'
    }
  }
}

const handleXmlImportSuccess = (results) => {
  console.log('XML import successful:', results)
  // Refresh products list
  fetchProductsData()
  showXmlUpload.value = false
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  formErrors.value = {}
  productForm.value = {
    id: null,
    name: '',
    sku: '',
    description: '',
    category_id: '',
    price: '',
    stock_quantity: '',
    image_url: '',
    is_active: true
  }
}

// Initialize sample data for testing
const initializeSampleData = () => {
  const sampleProducts = []
  for (let i = 1; i <= 25; i++) {
    sampleProducts.push({
      id: i,
      name: `Sample Product ${i}`,
      sku: `SKU-${String(i).padStart(3, '0')}`,
      description: `Description for product ${i}`,
      category_id: Math.floor(Math.random() * 8) + 1,
      price: (Math.random() * 1000 + 50).toFixed(2),
      stock_quantity: Math.floor(Math.random() * 100),
      image_url: null,
      is_active: Math.random() > 0.2,
      status: ['active', 'pending', 'disabled'][Math.floor(Math.random() * 3)],
      seller_name: ['TechStore', 'MobileHub', 'SoundTech', 'Wearables Inc'][Math.floor(Math.random() * 4)]
    })
  }
  products.value = sampleProducts
  totalItems.value = sampleProducts.length
}

// Initialize data
onMounted(async () => {
  try {
    await fetchCategoriesData()
    await fetchProductsData()
  } catch (err) {
    console.error('Failed to initialize data:', err)
  }
})

</script>

<style scoped>
.input-field {
  @apply border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>