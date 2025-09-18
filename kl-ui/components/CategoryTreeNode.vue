<template>
  <div class="category-node mb-2">
    <!-- Category Item -->
    <div 
      class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      :style="{ paddingLeft: (level * 20 + 12) + 'px' }"
    >
      <div class="flex items-center">
        <!-- Indentation guides -->
        <div 
          v-for="i in level" 
          :key="i" 
          class="w-5 h-px bg-gray-300 dark:bg-gray-600 mr-2"
        ></div>
        
        <!-- Expand/Collapse button for categories with children -->
        <button 
          v-if="category.children && category.children.length > 0"
          @click="toggleExpanded"
          class="mr-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
        >
          <svg 
            :class="{ 'transform rotate-90': expanded }" 
            class="w-4 h-4 transition-transform" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24" 
            xmlns="http://www.w3.org/2000/svg"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
        
        <!-- Placeholder for leaf nodes -->
        <div v-else class="w-6 mr-2"></div>
        
        <!-- Category name -->
        <span class="font-medium">{{ category.name }}</span>
        
        <!-- Status indicators -->
        <div class="ml-3 flex space-x-1">
          <span 
            v-if="!category.is_active"
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400"
          >
            Pasif
          </span>
          <span 
            v-if="category.is_featured"
            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400"
          >
            Öne Çıkan
          </span>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex space-x-2">
        <button 
          @click="$emit('edit', category)"
          class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-400"
          title="Düzenle"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
        </button>
        <button 
          @click="$emit('delete', category)"
          class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
          title="Sil"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Children -->
    <div v-if="expanded && category.children && category.children.length > 0" class="mt-1">
      <CategoryTreeNode 
        v-for="child in category.children" 
        :key="child.id" 
        :category="child"
        :level="level + 1"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  category: {
    type: Object,
    required: true
  },
  level: {
    type: Number,
    default: 0
  }
})

defineEmits(['edit', 'delete'])

const expanded = ref(false)

const toggleExpanded = () => {
  expanded.value = !expanded.value
}
</script>

<style scoped>
.category-node {
  transition: all 0.2s ease;
}
</style>
