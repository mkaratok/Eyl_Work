<template>
  <AdminSidebar>
    <div class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Users</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Manage all platform users</p>
        </div>
        <button 
          @click="showCreateModal = true"
          class="btn-primary flex items-center mt-4 md:mt-0"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Add New User
        </button>
      </div>

      <!-- Users Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">User List</h2>
          <div class="flex space-x-3 mt-4 sm:mt-0">
            <div class="relative">
              <input 
                v-model="searchQuery"
                @input="handleSearch"
                type="text" 
                placeholder="Search users..." 
                class="input-field pl-10 pr-4 py-2 w-full sm:w-64"
              >
              <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <select 
              v-model="roleFilter"
              @change="handleRoleFilter"
              class="input-field px-4 py-2"
            >
              <option value="">All Roles</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin</option>
              <option value="seller">Seller</option>
              <option value="sub_seller">Sub Seller</option>
              <option value="user">Customer</option>
            </select>
          </div>
        </div>
        
        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-600 dark:text-gray-400">Loading users...</p>
        </div>
        
        <div v-else-if="error" class="text-center py-8">
          <p class="text-red-500">Error: {{ error }}</p>
          <button @click="fetchUsersData" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
            Retry
          </button>
        </div>
        
        <div v-else-if="paginatedUsers.length === 0" class="text-center py-8">
          <p class="text-gray-600 dark:text-gray-400">No users found.</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr 
                v-for="user in paginatedUsers" 
                :key="user.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="bg-gray-200 border-2 border-dashed rounded-full w-10 h-10 mr-3 flex items-center justify-center">
                      <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                      </svg>
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name || user.first_name + ' ' + user.last_name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ user.email }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm">
                  <span :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    getRoleClass(getUserRoleName(user))
                  ]">
                    {{ getRoleText(getUserRoleName(user)) }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm">
                  <span :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    getStatusClass(user.status)
                  ]">
                    {{ getStatusText(user.status) }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ formatDate(user.created_at) }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <button 
                    @click="editUser(user)"
                    class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                  >
                    Edit
                  </button>
                  <button 
                    @click="deleteUser(user)"
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
      
      <!-- User Statistics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Total Users</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ userStats.total }}</h3>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Active Users</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ userStats.active }}</h3>
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
              <p class="text-gray-500 dark:text-gray-400 text-sm">Sellers</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ userStats.sellers }}</h3>
            </div>
            <div class="bg-amber-50 dark:bg-amber-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 dark:text-gray-400 text-sm">Admins</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ userStats.admins }}</h3>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/30 p-3 rounded-lg">
              <svg class="w-6 h-6 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Create/Edit User Modal -->
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ showCreateModal ? 'Add New User' : 'Edit User' }}
          </h3>
          
          <form @submit.prevent="showCreateModal ? createUser() : updateUser()">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Full Name *
              </label>
              <input 
                v-model="userForm.name"
                type="text" 
                required
                class="input-field w-full"
                placeholder="Enter full name"
              >
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email *
              </label>
              <input 
                v-model="userForm.email"
                type="email" 
                required
                class="input-field w-full"
                placeholder="user@example.com"
              >
            </div>
            
            <div class="mb-4" v-if="showCreateModal">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password *
              </label>
              <input 
                v-model="userForm.password"
                type="password" 
                required
                class="input-field w-full"
                placeholder="Enter password"
              >
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Role *
              </label>
              <select v-model="userForm.role" required class="input-field w-full">
                <option value="">Select Role</option>
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="seller">Seller</option>
                <option value="sub_seller">Sub Seller</option>
                <option value="user">Customer</option>
              </select>
            </div>
            
            <div class="mb-6">
              <label class="flex items-center">
                <input 
                  v-model="userForm.is_active"
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
                {{ showCreateModal ? 'Create User' : 'Update User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminSidebar>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AdminSidebar from '@/components/AdminSidebar.vue'
import { useUsers } from '~/composables/useUsers'

definePageMeta({
  middleware: 'auth'
})

const { users, loading, error, fetchUsers, createUser: createUserAPI, updateUser: updateUserAPI, deleteUser: deleteUserAPI } = useUsers()

// UI State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const searchQuery = ref('')
const roleFilter = ref('')

// Pagination State
const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)

// Form data
const userForm = ref({
  id: null,
  name: '',
  email: '',
  password: '',
  role: '',
  is_active: true
})

// Computed properties
const filteredUsers = computed(() => {
  // Since we're now using backend filtering, we don't need to filter on the client side
  // Just return all users
  return users.value || []
})

// Update the total items when users data changes
watch(users, (newUsers) => {
  if (newUsers && newUsers.length > 0) {
    // If users is a paginated response, get the total from the pagination info
    // Otherwise, use the length of the array
    if (newUsers.data && typeof newUsers.total !== 'undefined') {
      totalItems.value = newUsers.total
    } else {
      totalItems.value = newUsers.length
    }
  } else {
    totalItems.value = 0
  }
})

// Pagination computed properties
const paginatedUsers = computed(() => {
  // If users is a paginated response, return the data array
  if (users.value && users.value.data) {
    return users.value.data
  }
  // Otherwise, return all users (fallback for non-paginated data)
  return users.value || []
})

const totalPages = computed(() => {
  // If users is a paginated response, get the total pages from it
  if (users.value && users.value.last_page) {
    return users.value.last_page
  }
  // Otherwise, calculate from total items
  return Math.ceil(totalItems.value / itemsPerPage.value) || 1
})

const paginationInfo = computed(() => {
  // If users is a paginated response, get the pagination info from it
  if (users.value && users.value.total !== undefined) {
    return {
      start: users.value.from || 1,
      end: users.value.to || users.value.total,
      total: users.value.total || 0
    }
  }
  
  // Otherwise, calculate from current state
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
  
  // If users is a paginated response, get the current page and total pages from it
  let currentPageVal, totalPagesVal
  if (users.value && users.value.current_page !== undefined) {
    currentPageVal = users.value.current_page
    totalPagesVal = users.value.last_page || 1
  } else {
    currentPageVal = currentPage.value
    totalPagesVal = totalPages.value
  }
  
  let startPage = Math.max(1, currentPageVal - 2)
  let endPage = Math.min(totalPagesVal, startPage + maxVisiblePages - 1)
  
  // Adjust start page if we're near the end
  if (endPage - startPage + 1 < maxVisiblePages) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1)
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }
  
  return pages
})

const userStats = computed(() => {
  // Try to get stats from the backend response first
  if (users.value && users.value.summary) {
    return {
      total: users.value.summary.total || 0,
      active: users.value.summary.active || 0,
      sellers: users.value.summary.by_role?.seller || 0,
      admins: (users.value.summary.by_role?.admin || 0) + (users.value.summary.by_role?.super_admin || 0)
    }
  }
  
  // Fallback to client-side calculation
  const stats = {
    total: users.value?.length || 0,
    active: 0,
    sellers: 0,
    admins: 0
  }
  
  users.value?.forEach(user => {
    if (user.status === 'active' || user.is_active) {
      stats.active++
    }
    
    // Get the user's role name
    const roleName = getUserRoleName(user)
    
    if (roleName === 'seller' || roleName === 'sub_seller') {
      stats.sellers++
    }
    if (roleName === 'admin' || roleName === 'super_admin') {
      stats.admins++
    }
  })
  
  return stats
})

// Methods
const fetchUsersData = async () => {
  try {
    await fetchUsers()
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

const handleRoleFilter = () => {
  // Send the role filter to the backend instead of client-side filtering
  fetchUsersWithFilters()
  // Reset to first page when filtering
  currentPage.value = 1
}

const handleSearch = () => {
  // Send the search query to the backend
  fetchUsersWithFilters()
  // Reset to first page when searching
  currentPage.value = 1
}

// New method to fetch users with filters
const fetchUsersWithFilters = async () => {
  try {
    const params = {}
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    if (roleFilter.value) {
      params.role = roleFilter.value
    }
    
    // Always include pagination parameters
    params.page = currentPage.value
    params.per_page = itemsPerPage.value
    
    await fetchUsers(params)
  } catch (err) {
    console.error('Failed to fetch users with filters:', err)
  }
}

// Pagination methods
const goToPage = async (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    await fetchUsersWithFilters()
  }
}

const previousPage = async () => {
  if (currentPage.value > 1) {
    currentPage.value--
    await fetchUsersWithFilters()
  }
}

const nextPage = async () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    await fetchUsersWithFilters()
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US')
}

const getRoleClass = (role) => {
  // Handle both string role and array of roles
  const roleName = getRoleName(role)
  switch (roleName) {
    case 'admin': 
    case 'super_admin': 
      return 'bg-purple-100 text-purple-800 dark:bg-purple-800/30 dark:text-purple-400'
    case 'seller': 
    case 'sub_seller': 
      return 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
    case 'customer': 
    case 'user': 
      return 'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-400'
    default: 
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-400'
  }
}

const getRoleText = (role) => {
  // Handle both string role and array of roles
  const roleName = getRoleName(role)
  switch (roleName) {
    case 'super_admin': return 'Super Admin'
    case 'admin': return 'Admin'
    case 'seller': return 'Seller'
    case 'sub_seller': return 'Sub Seller'
    case 'customer': 
    case 'user': return 'Customer'
    default: return 'Unknown'
  }
}

// Helper function to get the role name from either a string or object
const getRoleName = (role) => {
  if (typeof role === 'string') {
    return role
  }
  if (typeof role === 'object' && role !== null) {
    return role.name || 'unknown'
  }
  return 'unknown'
}

// Helper function to get the first role name from user data
const getUserRoleName = (user) => {
  if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
    const role = user.roles[0]
    return typeof role === 'object' ? role.name : role
  }
  return user.role || 'unknown'
}

const getStatusClass = (status) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-400'
    case 'inactive': return 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-400'
    case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-400'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-400'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'active': return 'Active'
    case 'inactive': return 'Inactive'
    case 'pending': return 'Pending'
    default: return 'Unknown'
  }
}

const editUser = (user) => {
  userForm.value = { 
    ...user,
    // Extract role name from roles array for the form
    role: getUserRoleName(user)
  }
  showEditModal.value = true
}

const createUser = async () => {
  try {
    const userData = {
      name: userForm.value.name,
      email: userForm.value.email,
      password: userForm.value.password,
      role: userForm.value.role,
      is_active: userForm.value.is_active
    }
    
    console.log('Creating user:', userData)
    
    try {
      // Try to create user via API
      const newUser = await createUserAPI(userData)
      console.log('User created successfully:', newUser)
    } catch (apiError) {
      console.warn('API creation failed:', apiError.message)
      throw apiError
    }
    
    closeModal()
    
    // Refresh the user list to show the new user
    await fetchUsersWithFilters()
    
  } catch (err) {
    console.error('Failed to create user:', err)
    error.value = err.message || 'Failed to create user'
  }
}

const updateUser = async () => {
  try {
    const userData = {
      name: userForm.value.name,
      email: userForm.value.email,
      role: userForm.value.role,
      is_active: userForm.value.is_active
    }
    
    console.log('Updating user:', userForm.value.id, userData)
    
    try {
      // Try to update user via API
      await updateUserAPI(userForm.value.id, userData)
      console.log('User updated successfully')
    } catch (apiError) {
      console.warn('API update failed:', apiError.message)
      throw apiError
    }
    
    closeModal()
    
    // Refresh the user list to show the updated user
    await fetchUsersWithFilters()
    
  } catch (err) {
    console.error('Failed to update user:', err)
    error.value = err.message || 'Failed to update user'
  }
}

const deleteUser = async (user) => {
  if (confirm(`Are you sure you want to delete user ${user.name || user.email}?`)) {
    try {
      console.log('Deleting user:', user.id)
      
      try {
        // Try to delete user via API
        await deleteUserAPI(user.id)
        console.log('User deleted successfully via API')
      } catch (apiError) {
        console.warn('API deletion failed:', apiError.message)
        throw apiError
      }
      
      // Refresh the user list to remove the deleted user
      await fetchUsersWithFilters()
      
    } catch (err) {
      console.error('Failed to delete user:', err)
      error.value = err.message || 'Failed to delete user'
    }
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  userForm.value = {
    id: null,
    name: '',
    email: '',
    password: '',
    role: '',
    is_active: true
  }
}

// Initialize sample data for testing
const initializeSampleData = () => {
  const sampleUsers = [
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      roles: [{ name: 'admin' }],
      status: 'active',
      is_active: true,
      created_at: '2023-06-12T10:00:00Z'
    },
    {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      roles: [{ name: 'seller' }],
      status: 'active',
      is_active: true,
      created_at: '2023-06-11T10:00:00Z'
    },
    {
      id: 3,
      name: 'Robert Johnson',
      email: 'robert@example.com',
      roles: [{ name: 'seller' }],
      status: 'active',
      is_active: true,
      created_at: '2023-06-10T10:00:00Z'
    },
    {
      id: 4,
      name: 'Emily Davis',
      email: 'emily@example.com',
      roles: [{ name: 'user' }],
      status: 'active',
      is_active: true,
      created_at: '2023-06-09T10:00:00Z'
    },
    {
      id: 5,
      name: 'Michael Brown',
      email: 'michael@example.com',
      roles: [{ name: 'user' }],
      status: 'pending',
      is_active: false,
      created_at: '2023-06-08T10:00:00Z'
    }
  ]
  
  // Generate more sample users
  for (let i = 6; i <= 25; i++) {
    const roles = ['user', 'seller', 'admin', 'super_admin']
    const statuses = ['active', 'pending']
    const role = roles[Math.floor(Math.random() * roles.length)]
    const status = statuses[Math.floor(Math.random() * statuses.length)]
    
    sampleUsers.push({
      id: i,
      name: `User ${i}`,
      email: `user${i}@example.com`,
      roles: [{ name: role }],
      status,
      is_active: status === 'active',
      created_at: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString()
    })
  }
  
  users.value = sampleUsers
  totalItems.value = sampleUsers.length
}

// Initialize data
onMounted(async () => {
  try {
    // Fetch users with default parameters
    await fetchUsersWithFilters()
  } catch (err) {
    console.error('Failed to initialize data:', err)
  }
})
</script>

<style scoped>
.btn-primary {
  @apply bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors;
}

.input-field {
  @apply border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}
</style>