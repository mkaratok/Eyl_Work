<template>
  <div class="flex flex-col min-h-screen" :class="darkMode ? 'dark bg-gray-900' : 'bg-gray-50'">
    <AppNavigation />
    
    <main class="flex-grow p-4 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300">
      <slot />
    </main>
    
    <footer class="bg-gray-800 text-white p-4 text-center dark:bg-gray-900">
      <div class="container mx-auto">
        <p>© 2025 Kaçlira.com</p>
        <p class="mt-2 text-sm text-gray-400">
          All rights reserved
        </p>
      </div>
    </footer>
    
    <ScreenReaderAnnouncer ref="announcer" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const darkMode = ref(false)
const announcer = ref(null)

const toggleDarkMode = () => {
  darkMode.value = !darkMode.value
  localStorage.setItem('darkMode', darkMode.value)
  
  if (darkMode.value) {
    document.documentElement.classList.add('dark')
    // Announce to screen readers
    if (announcer.value) {
      announcer.value.announce('Dark mode enabled')
    }
  } else {
    document.documentElement.classList.remove('dark')
    // Announce to screen readers
    if (announcer.value) {
      announcer.value.announce('Light mode enabled')
    }
  }
}

// Check for saved dark mode preference
onMounted(() => {
  const savedDarkMode = localStorage.getItem('darkMode')
  if (savedDarkMode !== null) {
    darkMode.value = savedDarkMode === 'true'
  } else {
    // Check system preference
    darkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  
  if (darkMode.value) {
    document.documentElement.classList.add('dark')
  }
})
</script>