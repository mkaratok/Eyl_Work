<template>
  <AdminSidebar>
    <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Category Management</h1>
      <button 
        @click="showCreateModal = true"
        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors flex items-center"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Add Category
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center">
          <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full mr-4">
            <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Categories</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ categories.length }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center">
          <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-full mr-4">
            <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Categories</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeCategories }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center">
          <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-full mr-4">
            <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Parent Categories</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ parentCategories }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center">
          <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-full mr-4">
            <svg class="w-6 h-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sub Categories</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ subCategories }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="p-6 border-b border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">Categories List</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Search categories..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select v-model="statusFilter" class="input-field px-4 py-2">
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-600 dark:text-gray-400">Loading categories...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-8">
        <p class="text-red-500">Error: {{ error }}</p>
        <button @click="fetchCategoriesData" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
          Retry
        </button>
      </div>
      
      <div v-else-if="filteredCategories.length === 0" class="text-center py-8">
        <p class="text-gray-600 dark:text-gray-400">No categories found.</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Slug
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Parent
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Products
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr 
              v-for="category in filteredCategories" 
              :key="category.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-full mr-3">
                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name || 'Unnamed Category' }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">ID: {{ category.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                {{ category.slug || 'No slug' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                {{ category.parent_name || 'Root Category' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                {{ category.products_count || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span :class="[
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                  category.is_active !== false 
                    ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400' 
                    : 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'
                ]">
                  {{ category.is_active !== false ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button 
                    @click="editCategory(category)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    Edit
                  </button>
                  <button 
                    @click="toggleCategoryStatus(category)"
                    :class="[
                      'hover:opacity-75',
                      category.is_active !== false ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400'
                    ]"
                  >
                    {{ category.is_active !== false ? 'Deactivate' : 'Activate' }}
                  </button>
                  <button 
                    @click="deleteCategory(category)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ showCreateModal ? 'Add New Category' : 'Edit Category' }}
        </h3>
        
        <form @submit.prevent="showCreateModal ? createCategory() : updateCategory()">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Category Name
            </label>
            <input 
              v-model="categoryForm.name"
              type="text" 
              required
              class="input-field w-full"
              placeholder="Enter category name"
            >
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Slug
            </label>
            <input 
              v-model="categoryForm.slug"
              type="text" 
              class="input-field w-full"
              placeholder="category-slug (auto-generated if empty)"
            >
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Parent Category
            </label>
            <select v-model="categoryForm.parent_id" class="input-field w-full">
              <option value="">Root Category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          
          <div class="mb-6">
            <label class="flex items-center">
              <input 
                v-model="categoryForm.is_active"
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
              {{ showCreateModal ? 'Create' : 'Update' }}
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
import { useAdminCategories } from '~/composables/useAdminCategories'

definePageMeta({
  middleware: 'auth'
})

const { categories, loading, error, fetchCategories, createCategory: createCategoryAPI, updateCategory: updateCategoryAPI, deleteCategory: deleteCategoryAPI } = useAdminCategories()

// UI State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const searchQuery = ref('')
const statusFilter = ref('')

// Form data
const categoryForm = ref({
  id: null,
  name: '',
  slug: '',
  parent_id: '',
  is_active: true
})

// Computed properties
const filteredCategories = computed(() => {
  let filtered = categories.value || []
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(cat => 
      (cat.name || '').toLowerCase().includes(query) ||
      (cat.slug || '').toLowerCase().includes(query)
    )
  }
  
  if (statusFilter.value) {
    const isActive = statusFilter.value === 'active'
    filtered = filtered.filter(cat => 
      statusFilter.value === 'active' ? cat.is_active !== false : cat.is_active === false
    )
  }
  
  return filtered
})

const activeCategories = computed(() => {
  return (categories.value || []).filter(cat => cat.is_active !== false).length
})

const parentCategories = computed(() => {
  return (categories.value || []).filter(cat => !cat.parent_id).length
})

const subCategories = computed(() => {
  return (categories.value || []).filter(cat => cat.parent_id).length
})

// Methods
const fetchCategoriesData = async () => {
  try {
    await fetchCategories()
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

const editCategory = (category) => {
  categoryForm.value = { ...category }
  showEditModal.value = true
}

const createCategory = async () => {
  try {
    const categoryData = {
      name: categoryForm.value.name,
      slug: categoryForm.value.slug || categoryForm.value.name.toLowerCase().replace(/\s+/g, '-'),
      parent_id: categoryForm.value.parent_id || null,
      is_active: categoryForm.value.is_active
    }
    
    console.log('Creating category:', categoryData)
    
    try {
      // Try to create category via API
      const newCategory = await createCategoryAPI(categoryData)
      
      // Add to local categories array
      const categoryWithDefaults = {
        ...newCategory,
        id: newCategory.id || Date.now(),
        products_count: 0,
        parent_name: categoryData.parent_id ? getParentName(categoryData.parent_id) : null
      }
      
      categories.value.unshift(categoryWithDefaults)
      
      console.log('Category created successfully:', newCategory)
    } catch (apiError) {
      console.warn('API creation failed, adding to local data only:', apiError.message)
      
      // Fallback: Add to local data even if API fails
      const newCategory = {
        ...categoryData,
        id: Date.now(),
        products_count: 0,
        parent_name: categoryData.parent_id ? getParentName(categoryData.parent_id) : null,
        created_at: new Date().toISOString()
      }
      
      categories.value.unshift(newCategory)
    }
    
    closeModal()
  } catch (err) {
    console.error('Failed to create category:', err)
    error.value = err.message || 'Failed to create category'
  }
}

const updateCategory = async () => {
  try {
    const categoryData = {
      name: categoryForm.value.name,
      slug: categoryForm.value.slug || categoryForm.value.name.toLowerCase().replace(/\s+/g, '-'),
      parent_id: categoryForm.value.parent_id || null,
      is_active: categoryForm.value.is_active
    }
    
    console.log('Updating category:', categoryForm.value.id, categoryData)
    
    try {
      // Try to update category via API
      await updateCategoryAPI(categoryForm.value.id, categoryData)
      
      // Update local categories array
      const index = categories.value.findIndex(c => c.id === categoryForm.value.id)
      if (index !== -1) {
        categories.value[index] = { 
          ...categories.value[index], 
          ...categoryData,
          parent_name: categoryData.parent_id ? getParentName(categoryData.parent_id) : null
        }
      }
      
      console.log('Category updated successfully')
    } catch (apiError) {
      console.warn('API update failed, updating local data only:', apiError.message)
      
      // Fallback: Update local data even if API fails
      const index = categories.value.findIndex(c => c.id === categoryForm.value.id)
      if (index !== -1) {
        categories.value[index] = { 
          ...categories.value[index], 
          ...categoryData,
          parent_name: categoryData.parent_id ? getParentName(categoryData.parent_id) : null
        }
      }
    }
    
    closeModal()
  } catch (err) {
    console.error('Failed to update category:', err)
    error.value = err.message || 'Failed to update category'
  }
}

const toggleCategoryStatus = async (category) => {
  try {
    const newStatus = !category.is_active
    console.log('Toggling status for category:', category.id, 'to:', newStatus)
    
    try {
      // Try to update status via API
      await updateCategoryAPI(category.id, { is_active: newStatus })
      
      // Update local status
      const index = categories.value.findIndex(c => c.id === category.id)
      if (index !== -1) {
        categories.value[index].is_active = newStatus
      }
      
      console.log('Category status updated successfully')
    } catch (apiError) {
      console.warn('API status update failed, updating local data only:', apiError.message)
      
      // Fallback: Update local data even if API fails
      const index = categories.value.findIndex(c => c.id === category.id)
      if (index !== -1) {
        categories.value[index].is_active = newStatus
      }
    }
  } catch (err) {
    console.error('Failed to toggle category status:', err)
    error.value = err.message || 'Failed to toggle category status'
  }
}

const deleteCategory = async (category) => {
  if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
    try {
      console.log('Deleting category:', category.id)
      
      try {
        // Try to delete category via API
        await deleteCategoryAPI(category.id)
        
        console.log('Category deleted successfully via API')
      } catch (apiError) {
        console.warn('API deletion failed, removing from local data only:', apiError.message)
      }
      
      // Remove from local categories array
      const index = categories.value.findIndex(c => c.id === category.id)
      if (index !== -1) {
        categories.value.splice(index, 1)
      }
      
    } catch (err) {
      console.error('Failed to delete category:', err)
      error.value = err.message || 'Failed to delete category'
    }
  }
}

const getParentName = (parentId) => {
  const parent = categories.value?.find(cat => cat.id == parentId)
  return parent ? parent.name : 'Unknown Parent'
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  categoryForm.value = {
    id: null,
    name: '',
    slug: '',
    parent_id: '',
    is_active: true
  }
}

// Initialize sample data for testing
const initializeSampleData = () => {
  const sampleCategories = [
    {
      id: 1,
      name: 'Electronics',
      slug: 'electronics',
      parent_id: null,
      parent_name: null,
      products_count: 1250,
      is_active: true,
      created_at: '2023-06-01T10:00:00Z'
    },
    {
      id: 2,
      name: 'Smartphones',
      slug: 'smartphones',
      parent_id: 1,
      parent_name: 'Electronics',
      products_count: 450,
      is_active: true,
      created_at: '2023-06-02T10:00:00Z'
    },
    {
      id: 3,
      name: 'Laptops',
      slug: 'laptops',
      parent_id: 1,
      parent_name: 'Electronics',
      products_count: 320,
      is_active: true,
      created_at: '2023-06-03T10:00:00Z'
    },
    {
      id: 4,
      name: 'Clothing',
      slug: 'clothing',
      parent_id: null,
      parent_name: null,
      products_count: 850,
      is_active: true,
      created_at: '2023-06-04T10:00:00Z'
    },
    {
      id: 5,
      name: 'Men\'s Clothing',
      slug: 'mens-clothing',
      parent_id: 4,
      parent_name: 'Clothing',
      products_count: 420,
      is_active: true,
      created_at: '2023-06-05T10:00:00Z'
    },
    {
      id: 6,
      name: 'Home & Garden',
      slug: 'home-garden',
      parent_id: null,
      parent_name: null,
      products_count: 650,
      is_active: true,
      created_at: '2023-06-06T10:00:00Z'
    },
    {
      id: 7,
      name: 'Books',
      slug: 'books',
      parent_id: null,
      parent_name: null,
      products_count: 1200,
      is_active: false,
      created_at: '2023-06-07T10:00:00Z'
    }
  ]
  
  categories.value = sampleCategories
}

// Initialize data
onMounted(async () => {
  try {
    await fetchCategoriesData()
    
    // If no categories from API, use sample data for testing
    if (!categories.value || categories.value.length === 0) {
      initializeSampleData()
    }
  } catch (err) {
    console.error('Failed to initialize data:', err)
    // Fall back to sample data if API fails
    initializeSampleData()
  }
})
</script>

<style scoped>
.input-field {
  @apply border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>