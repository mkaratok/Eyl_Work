<template>
  <div class="bg-white p-4 rounded-lg shadow-md mb-6">
    <h3 class="text-lg font-semibold mb-3">Filters</h3>
    
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
      <div class="flex items-center space-x-2">
        <input
          v-model="minPrice"
          type="number"
          placeholder="Min"
          class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <span>-</span>
        <input
          v-model="maxPrice"
          type="number"
          placeholder="Max"
          class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>
    </div>
    
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
      <select
        v-model="selectedCategory"
        class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-primary"
      >
        <option value="">All Categories</option>
        <option value="electronics">Electronics</option>
        <option value="clothing">Clothing</option>
        <option value="home">Home & Kitchen</option>
        <option value="books">Books</option>
      </select>
    </div>
    
    <div class="flex space-x-2">
      <button
        @click="applyFilters"
        class="flex-1 bg-primary text-white py-2 rounded hover:bg-blue-700 transition"
      >
        Apply Filters
      </button>
      <button
        @click="resetFilters"
        class="flex-1 bg-gray-200 text-gray-800 py-2 rounded hover:bg-gray-300 transition"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const minPrice = ref('')
const maxPrice = ref('')
const selectedCategory = ref('')

const emit = defineEmits(['filter'])

const applyFilters = () => {
  emit('filter', {
    minPrice: minPrice.value,
    maxPrice: maxPrice.value,
    category: selectedCategory.value
  })
}

const resetFilters = () => {
  minPrice.value = ''
  maxPrice.value = ''
  selectedCategory.value = ''
  emit('filter', {
    minPrice: '',
    maxPrice: '',
    category: ''
  })
}
</script>