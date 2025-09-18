<template>
  <nav class="bg-white shadow-md dark:bg-gray-800">
    <div class="container mx-auto px-4">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <NuxtLink to="/" class="flex-shrink-0 flex items-center">
            <span class="text-xl font-bold text-primary">Kaçlira.com</span>
          </NuxtLink>
        </div>
        
        <!-- Desktop Menu -->
    <div class="hidden md:flex items-center space-x-6">
                <NuxtLink 
            v-for="item in menuItems" 
            :key="item.name"
            :to="item.path"
            class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
            active-class="text-primary font-semibold bg-gray-50 dark:text-white dark:bg-gray-700"
            exact
          >
            {{ item.name }}
          </NuxtLink>
    </div>
        
        <!-- User Actions -->
        <div class="hidden md:flex items-center space-x-4">
          <!-- Search Icon -->
          <div class="relative">
            <button 
              @click="toggleSearch"
              class="p-2 text-gray-700 hover:text-primary dark:text-gray-300 dark:hover:text-white"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>
            
            <!-- Search Input (shown when search is active) -->
            <div v-if="searchActive" class="absolute right-0 top-12 w-48 z-50">
              <div class="relative">
                <input
                  ref="searchInput"
                  v-model="searchQuery"
                  @keyup.enter="handleSearch"
                  @blur="closeSearch"
                  type="text"
                  placeholder="Ürün ara..."
                  class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  autofocus
                />
              
              </div>
            </div>
          </div>
          
          <!-- User Menu -->
          <div class="relative" id="user-menu-container" v-if="isAuthenticated">
            <button 
              @click="toggleUserMenu"
              class="p-2 text-gray-700 hover:text-primary dark:text-gray-300 dark:hover:text-white"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </button>
            
            <!-- User Dropdown Menu -->
            <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 dark:bg-gray-700 z-50">
              <NuxtLink 
                :to="getDashboardLink()" 
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                @click="userMenuOpen = false"
              >
                Dashboard
              </NuxtLink>
              <NuxtLink 
                to="/user" 
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                @click="userMenuOpen = false"
              >
                Ayarlar
              </NuxtLink>
              <button 
                @click="handleLogout"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
              >
                Çıkış Yap
              </button>
            </div>
          </div>
          
          <!-- Login Link for non-authenticated users -->
          <NuxtLink 
            v-else
            to="/login" 
            class="p-2 text-gray-700 hover:text-primary dark:text-gray-300 dark:hover:text-white"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
          </NuxtLink>
        </div>
        
        <!-- Mobile menu button -->
        <div class="md:hidden flex items-center">
          <!-- Menu button removed as requested -->
        </div>
      </div>
    </div>
    
    
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '~/composables/useAuth'

const searchActive = ref(false)
const searchQuery = ref('')
const searchInput = ref(null)
const router = useRouter()
const userMenuOpen = ref(false)

// Get auth state from composable
const { isAuthenticated, logout, user } = useAuth()

const getDashboardLink = () => {
  if (!user.value || !user.value.roles) {
    return '/user'; // Default fallback
  }
  
  const role = Array.isArray(user.value.roles) ? user.value.roles[0] : user.value.roles;
  
  if (role === 'super_admin' || role === 'admin') {
    return '/admin';
  } else if (role === 'seller' || role === 'sub_seller') {
    return '/seller';
  } else {
    return '/user';
  }
}

const menuItems = [
  { name: 'Ana Sayfa', path: '/' },
  { name: 'Ürün Karşılaştırma', path: '/products' },
  { name: 'Kategoriler', path: '/categories' },
  { name: 'En İyi Fırsatlar', path: '/deals' },
  { name: 'Hakkında', path: '/about' }
]

const toggleSearch = () => {
  searchActive.value = !searchActive.value
  // Focus the input when it becomes active
  if (searchActive.value) {
    setTimeout(() => {
      if (searchInput.value) {
        searchInput.value.focus()
      }
    }, 10)
  }
}

const closeSearch = () => {
  // Close search after a short delay to allow for click events
  setTimeout(() => {
    searchActive.value = false
  }, 200)
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    // Navigate to products page with search query
    router.push({
      path: '/products',
      query: { search: searchQuery.value.trim() }
    })
    // Clear the search query and close search
    searchQuery.value = ''
    searchActive.value = false
  }
}

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value
}

const handleLogout = async () => {
  try {
    await logout()
    userMenuOpen.value = false
  } catch (err) {
    console.error('Logout failed:', err)
  }
}

// Close user menu when clicking outside
const handleClickOutside = (event) => {
  if (userMenuOpen.value && !event.target.closest('#user-menu-container')) {
    userMenuOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>