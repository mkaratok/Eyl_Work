<template>
  <div class="category-manager">
    <div class="mb-6">
      <h2 class="text-xl font-bold mb-4">Kategori Yönetimi</h2>
      
      <!-- Add Category Form -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
        <h3 class="text-lg font-medium mb-3">Yeni Kategori Ekle</h3>
        <form @submit.prevent="createCategory">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Kategori Adı *
              </label>
              <input 
                v-model="newCategory.name"
                type="text" 
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Kategori adı girin"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Üst Kategori
              </label>
              <select 
                v-model="newCategory.parent_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Kök Kategori</option>
                <option 
                  v-for="category in flatCategories" 
                  :key="category.id" 
                  :value="category.id"
                >
                  {{ category.full_name }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Sıralama
              </label>
              <input 
                v-model="newCategory.sort_order"
                type="number" 
                min="0"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
            </div>
            
            <div class="flex items-center mt-6">
              <input 
                v-model="newCategory.is_active"
                type="checkbox" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                id="isActive"
              >
              <label for="isActive" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Aktif
              </label>
            </div>
            
            <div class="flex items-center mt-6">
              <input 
                v-model="newCategory.is_featured"
                type="checkbox" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                id="isFeatured"
              >
              <label for="isFeatured" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Öne Çıkan
              </label>
            </div>
          </div>
          
          <div class="flex justify-end">
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md transition"
              :disabled="creatingCategory"
            >
              {{ creatingCategory ? 'Ekleniyor...' : 'Kategori Ekle' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Category Tree -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-medium">Kategori Hiyerarşisi</h3>
        <button 
          @click="loadCategories"
          class="text-sm text-blue-500 hover:text-blue-700"
          :disabled="loading"
        >
          {{ loading ? 'Yükleniyor...' : 'Yenile' }}
        </button>
      </div>
      
      <div class="p-4">
        <div v-if="loading" class="text-center py-4">
          Kategoriler yükleniyor...
        </div>
        
        <div v-else-if="categoryTree.length === 0" class="text-center py-4 text-gray-500">
          Henüz kategori eklenmemiş.
        </div>
        
        <div v-else>
          <CategoryTreeNode 
            v-for="category in categoryTree" 
            :key="category.id" 
            :category="category"
            :level="0"
            @edit="editCategory"
            @delete="deleteCategory"
          />
        </div>
      </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div v-if="editingCategory" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full p-6 max-h-screen overflow-y-auto">
        <h3 class="text-lg font-medium mb-4">Kategori Düzenle</h3>
        
        <form @submit.prevent="updateCategory">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Kategori Adı *
            </label>
            <input 
              v-model="editingCategory.name"
              type="text" 
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              Üst Kategori
            </label>
            <select 
              v-model="editingCategory.parent_id"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="">Kök Kategori</option>
              <option 
                v-for="category in flatCategories.filter(c => c.id !== editingCategory.id)" 
                :key="category.id" 
                :value="category.id"
              >
                {{ category.full_name }}
              </option>
            </select>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Sıralama
              </label>
              <input 
                v-model="editingCategory.sort_order"
                type="number" 
                min="0"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
            </div>
            
            <div class="flex items-center mt-6">
              <input 
                v-model="editingCategory.is_active"
                type="checkbox" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                id="editIsActive"
              >
              <label for="editIsActive" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Aktif
              </label>
            </div>
            
            <div class="flex items-center mt-6">
              <input 
                v-model="editingCategory.is_featured"
                type="checkbox" 
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                id="editIsFeatured"
              >
              <label for="editIsFeatured" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                Öne Çıkan
              </label>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button 
              type="button"
              @click="cancelEdit"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              İptal
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium"
              :disabled="updatingCategory"
            >
              {{ updatingCategory ? 'Güncelleniyor...' : 'Güncelle' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCategories } from '~/composables/useCategories'
import CategoryTreeNode from './CategoryTreeNode.vue'

// Composables
const { 
  categories: categoryTree, 
  flatCategories, 
  loading, 
  error, 
  fetchCategories 
} = useCategories()

// State
const newCategory = ref({
  name: '',
  parent_id: '',
  sort_order: 0,
  is_active: true,
  is_featured: false
})

const editingCategory = ref(null)
const creatingCategory = ref(false)
const updatingCategory = ref(false)

// Methods
const loadCategories = async () => {
  try {
    await fetchCategories()
  } catch (err) {
    console.error('Failed to load categories:', err)
    alert('Kategoriler yüklenirken bir hata oluştu: ' + err.message)
  }
}

const createCategory = async () => {
  creatingCategory.value = true
  try {
    // In a real implementation, you would call an API endpoint to create the category
    console.log('Creating category:', newCategory.value)
    
    // Reset form
    newCategory.value = {
      name: '',
      parent_id: '',
      sort_order: 0,
      is_active: true,
      is_featured: false
    }
    
    // Refresh categories
    await loadCategories()
    
    alert('Kategori başarıyla oluşturuldu!')
  } catch (err) {
    console.error('Failed to create category:', err)
    alert('Kategori oluşturulurken bir hata oluştu: ' + err.message)
  } finally {
    creatingCategory.value = false
  }
}

const editCategory = (category) => {
  editingCategory.value = { ...category }
}

const updateCategory = async () => {
  updatingCategory.value = true
  try {
    // In a real implementation, you would call an API endpoint to update the category
    console.log('Updating category:', editingCategory.value)
    
    // Close modal and refresh
    editingCategory.value = null
    await loadCategories()
    
    alert('Kategori başarıyla güncellendi!')
  } catch (err) {
    console.error('Failed to update category:', err)
    alert('Kategori güncellenirken bir hata oluştu: ' + err.message)
  } finally {
    updatingCategory.value = false
  }
}

const deleteCategory = async (category) => {
  if (!confirm(`"${category.name}" kategorisini silmek istediğinizden emin misiniz?`)) {
    return
  }
  
  try {
    // In a real implementation, you would call an API endpoint to delete the category
    console.log('Deleting category:', category.id)
    
    // Refresh categories
    await loadCategories()
    
    alert('Kategori başarıyla silindi!')
  } catch (err) {
    console.error('Failed to delete category:', err)
    alert('Kategori silinirken bir hata oluştu: ' + err.message)
  }
}

const cancelEdit = () => {
  editingCategory.value = null
}

// Load categories on mount
onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
.category-manager {
  max-width: 1200px;
  margin: 0 auto;
}
</style>
