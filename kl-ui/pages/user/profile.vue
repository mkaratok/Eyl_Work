<template>
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profile Settings</h1>
      <NuxtLink to="/user" class="text-primary hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
        ← Back to Dashboard
      </NuxtLink>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 max-w-2xl">
      <form @submit.prevent="updateProfile">
        <div class="mb-6">
          <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
          <input
            id="name"
            v-model="profileData.name"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            placeholder="Enter your full name"
          />
        </div>
        
        <div class="mb-6">
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
          <input
            id="email"
            v-model="profileData.email"
            type="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            placeholder="Enter your email address"
          />
        </div>
        
        <div class="mb-6">
          <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
          <input
            id="phone"
            v-model="profileData.phone"
            type="tel"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            placeholder="Enter your phone number"
          />
        </div>
        
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="bg-primary hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors disabled:opacity-50"
          >
            {{ loading ? 'Updating...' : 'Update Profile' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  middleware: 'auth'
})

const { user, loading, getCurrentUser } = useAuth()

const profileData = ref({
  name: '',
  email: '',
  phone: ''
})

const fetchUserData = async () => {
  try {
    await getCurrentUser()
    if (user.value) {
      profileData.value.name = user.value.name || ''
      profileData.value.email = user.value.email || ''
      profileData.value.phone = user.value.phone || ''
    }
  } catch (err) {
    console.error('Failed to fetch user data:', err)
  }
}

const updateProfile = async () => {
  // In a real app, this would make an API call to update the user's profile
  alert('Profile updated successfully!')
}

onMounted(() => {
  fetchUserData()
})
</script>