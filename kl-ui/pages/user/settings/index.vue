<template>
  <UserSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Account Settings</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage your account preferences and security</p>
        </div>
      </div>

      <!-- Settings Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Navigation -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <nav class="space-y-1">
              <button 
                @click="activeTab = 'profile'"
                :class="[
                  'w-full text-left px-4 py-3 rounded-lg transition',
                  activeTab === 'profile' 
                    ? 'bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400' 
                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                <div class="font-medium">Profile Information</div>
                <div class="text-sm opacity-75">Update your personal details</div>
              </button>
              
              <button 
                @click="activeTab = 'security'"
                :class="[
                  'w-full text-left px-4 py-3 rounded-lg transition',
                  activeTab === 'security' 
                    ? 'bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400' 
                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                <div class="font-medium">Security</div>
                <div class="text-sm opacity-75">Change password and security settings</div>
              </button>
              
              <button 
                @click="activeTab = 'notifications'"
                :class="[
                  'w-full text-left px-4 py-3 rounded-lg transition',
                  activeTab === 'notifications' 
                    ? 'bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400' 
                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                <div class="font-medium">Notifications</div>
                <div class="text-sm opacity-75">Manage email and push notifications</div>
              </button>
              
              <button 
                @click="activeTab = 'privacy'"
                :class="[
                  'w-full text-left px-4 py-3 rounded-lg transition',
                  activeTab === 'privacy' 
                    ? 'bg-blue-50 text-blue-600 dark:bg-gray-700 dark:text-blue-400' 
                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                ]"
              >
                <div class="font-medium">Privacy</div>
                <div class="text-sm opacity-75">Control data usage and privacy</div>
              </button>
            </nav>
          </div>
        </div>

        <!-- Content -->
        <div class="lg:col-span-2">
          <!-- Profile Information -->
          <div v-if="activeTab === 'profile'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Profile Information</h2>
            
            <form @submit.prevent="updateProfile" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                  <input 
                    v-model="profileData.name" 
                    type="text" 
                    class="input-field"
                    required
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                  <input 
                    v-model="profileData.email" 
                    type="email" 
                    class="input-field"
                    required
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                  <input 
                    v-model="profileData.phone" 
                    type="tel" 
                    class="input-field"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                  <input 
                    v-model="profileData.dob" 
                    type="date" 
                    class="input-field"
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bio</label>
                <textarea 
                  v-model="profileData.bio" 
                  rows="4" 
                  class="input-field"
                  placeholder="Tell us about yourself..."
                ></textarea>
              </div>
              
              <div class="pt-4">
                <button type="submit" class="btn-primary">
                  Update Profile
                </button>
              </div>
            </form>
          </div>

          <!-- Security -->
          <div v-if="activeTab === 'security'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Security</h2>
            
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Change Password</h3>
                <form @submit.prevent="changePassword" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
                    <input 
                      v-model="passwordData.current" 
                      type="password" 
                      class="input-field"
                      required
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                    <input 
                      v-model="passwordData.new" 
                      type="password" 
                      class="input-field"
                      required
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
                    <input 
                      v-model="passwordData.confirm" 
                      type="password" 
                      class="input-field"
                      required
                    />
                  </div>
                  
                  <div class="pt-2">
                    <button type="submit" class="btn-primary">
                      Change Password
                    </button>
                  </div>
                </form>
              </div>
              
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Two-Factor Authentication</h3>
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-gray-700 dark:text-gray-300">Add an extra layer of security to your account</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">We'll send a code to your phone when you log in</p>
                  </div>
                  <button class="btn-primary">
                    Enable
                  </button>
                </div>
              </div>
              
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Active Sessions</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Windows 11 · Chrome</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Istanbul, Turkey · Current session</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400">
                      Active
                    </span>
                  </div>
                  
                  <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">macOS · Safari</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Ankara, Turkey · 2 days ago</p>
                    </div>
                    <button class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                      Sign out
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notifications -->
          <div v-if="activeTab === 'notifications'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Notifications</h2>
            
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Email Notifications</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Order Updates</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Receive notifications about your orders</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="order-notifications" class="sr-only" checked>
                      <label for="order-notifications" class="block h-6 w-10 rounded-full bg-blue-500 cursor-pointer"></label>
                      <label for="order-notifications" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Product Recommendations</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Get personalized product suggestions</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="product-notifications" class="sr-only">
                      <label for="product-notifications" class="block h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                      <label for="product-notifications" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Promotional Emails</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Receive special offers and discounts</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="promo-notifications" class="sr-only" checked>
                      <label for="promo-notifications" class="block h-6 w-10 rounded-full bg-blue-500 cursor-pointer"></label>
                      <label for="promo-notifications" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Push Notifications</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Order Updates</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Receive push notifications about your orders</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="order-push" class="sr-only" checked>
                      <label for="order-push" class="block h-6 w-10 rounded-full bg-blue-500 cursor-pointer"></label>
                      <label for="order-push" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Product Recommendations</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Get push notifications for personalized product suggestions</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="product-push" class="sr-only">
                      <label for="product-push" class="block h-6 w-10 rounded-full bg-gray-300 cursor-pointer"></label>
                      <label for="product-push" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Privacy -->
          <div v-if="activeTab === 'privacy'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Privacy</h2>
            
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Data Collection</h3>
                <div class="space-y-4">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Personalization</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Allow us to use your data to personalize your experience</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="personalization" class="sr-only" checked>
                      <label for="personalization" class="block h-6 w-10 rounded-full bg-blue-500 cursor-pointer"></label>
                      <label for="personalization" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">Analytics</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Help us improve our service by sharing usage data</p>
                    </div>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none">
                      <input type="checkbox" id="analytics" class="sr-only" checked>
                      <label for="analytics" class="block h-6 w-10 rounded-full bg-blue-500 cursor-pointer"></label>
                      <label for="analytics" class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition transform duration-300 ease-in-out"></label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Account Privacy</h3>
                <div class="space-y-4">
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">Profile Visibility</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Control who can see your profile information</p>
                    <select class="input-field w-full">
                      <option>Public - Anyone can see your profile</option>
                      <option>Private - Only you can see your profile</option>
                      <option>Friends - Only your friends can see your profile</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Delete Account</h3>
                <div class="flex items-start">
                  <div class="flex-1">
                    <p class="text-gray-700 dark:text-gray-300">Permanently delete your account and all associated data</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This action cannot be undone</p>
                  </div>
                  <button class="px-4 py-2 border border-red-500 text-red-500 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                    Delete Account
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </UserSidebar>
</template>

<script setup>
import UserSidebar from '@/components/UserSidebar.vue'
import { ref } from 'vue'

const activeTab = ref('profile')

const profileData = ref({
  name: 'John Doe',
  email: 'john.doe@example.com',
  phone: '+90 555 123 4567',
  dob: '1990-01-01',
  bio: 'I am a software developer passionate about creating great user experiences.'
})

const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
})

const updateProfile = () => {
  console.log('Profile updated:', profileData.value)
  // In a real app, you would send this data to your backend
}

const changePassword = () => {
  console.log('Password changed:', passwordData.value)
  // In a real app, you would send this data to your backend
  passwordData.value = {
    current: '',
    new: '',
    confirm: ''
  }
}
</script>