<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Hesabınıza giriş yapın
        </h2>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 0 001.414-1.414L11.414 10l1.293-1.293a1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Giriş başarısız oldu
              </h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ error }}</p>
                <!-- Display additional error details if available -->
                <p v-if="errorDetails">{{ errorDetails }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">E-posta adresi</label>
            <input
              id="email"
              name="email"
              type="email"
              autocomplete="email"
              required
              v-model="email"
              class="appearance-none rounded-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 dark:text-gray-100 dark:bg-gray-700 dark:border-gray-600 rounded-t-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
              placeholder="E-posta adresi"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Şifre</label>
            <input
              id="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              v-model="password"
              class="appearance-none rounded-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 dark:text-gray-100 dark:bg-gray-700 dark:border-gray-600 rounded-b-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
              placeholder="Şifre"
            />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              name="remember-me"
              type="checkbox"
              v-model="rememberMe"
              class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
            />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
              Beni hatırla
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-primary hover:text-blue-500">
              Şifrenizi mi unuttunuz?
            </a>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            @click.prevent="handleLogin"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg
                v-else
                class="h-5 w-5 text-primary-500 group-hover:text-primary-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </span>
            {{ loading ? 'Giriş yapılıyor...' : 'Giriş Yap' }}
          </button>
        </div>
      </form>
      
      <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Hesabınız yok mu?
          <NuxtLink to="/register" class="font-medium text-primary hover:text-blue-500">
            Hesap oluştur
          </NuxtLink>
        </p>
      </div>
      

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '~/composables/useAuth'
// Import the updated auth service that handles admin login
import authService from '~/services/authService'
import apiClient from '~/services/apiClient'

definePageMeta({
  middleware: 'guest'
})

const router = useRouter()
const loading = ref(false)
const error = ref(null)
const errorDetails = ref('')

const email = ref('')
const password = ref('')
const rememberMe = ref(false)

const handleLogin = async () => {
  console.log('=== LOGIN BUTTON CLICKED ===');
  console.log('Email value:', email.value);
  console.log('Password value:', password.value ? '[REDACTED]' : 'EMPTY');
  
  try {
    console.log('Attempting login with:', {
      email: email.value,
      password: password.value
    });
    
    // Validate inputs first
    if (!email.value || !password.value) {
      error.value = 'Email and password are required';
      return;
    }
    
    loading.value = true
    error.value = null
    errorDetails.value = ''
    
    // Initialize CSRF tokens before login
    try {
      await apiClient.initializeCsrf();
      console.log('CSRF token initialized before login');
    } catch (csrfError) {
      console.warn('CSRF initialization failed:', csrfError);
    }
    
    const loginData = {
      email: email.value,
      password: password.value
    };
    
    console.log('Sending login request:', loginData);
    const result = await authService.login(loginData);
    console.log('Login successful, result:', result);
    
    // Make sure we store the token properly
    const token = result.data?.token || result.token;
    if (token) {
      console.log('Setting auth token in localStorage');
      localStorage.setItem('auth_token', token);
    } else {
      console.warn('No token found in login response');
    }
    
    // Update the auth state in the composable
    const { initializeAuth } = useAuth();
    initializeAuth();
    
    // After login, redirect based on user role
    const user = result.data?.user || result.user;
    if (user) {
      // Extract role more robustly to handle API response structure
      let role = null;
      if (user.roles) {
        if (Array.isArray(user.roles)) {
          // Handle array of roles - API returns array of strings like ["admin"]
          const firstRole = user.roles[0];
          // If it's an object with a name property, use that, otherwise use the value directly
          role = (typeof firstRole === 'object' && firstRole !== null) ? firstRole.name : firstRole;
        } else {
          // Handle single role - could be string or object
          role = (typeof user.roles === 'object' && user.roles !== null) ? user.roles.name : user.roles;
        }
      } else if (user.role) {
        // Handle single role property - could be string or object
        role = (typeof user.role === 'object' && user.role !== null) ? user.role.name : user.role;
      }
      
      console.log('User role:', role);
      
      if (role === 'super_admin' || role === 'admin') {
        console.log('Redirecting to admin dashboard');
        await router.push('/admin');
      } else if (role === 'seller' || role === 'sub_seller') {
        console.log('Redirecting to seller dashboard');
        await router.push('/seller');
      } else {
        console.log('Redirecting to user dashboard');
        await router.push('/user');
      }
    } else {
      console.log('No user data found, redirecting to home page');
      await router.push('/');
    }
  } catch (err) {
    console.error('Login failed:', err);
    console.error('Error details:', {
      message: err.message,
      stack: err.stack,
      name: err.name,
      status: err.status,
      data: err.data
    });
    
    error.value = err.message || 'Login failed'
    
    // Set detailed error information for display
    errorDetails.value = `Error type: ${err.name}, Status: ${err.status || 'N/A'}`;
    
    // Add more debugging information
    if (err.response) {
      errorDetails.value += `, Response status: ${err.response.status}`;
    }
    if (err.data) {
      errorDetails.value += `, Data: ${JSON.stringify(err.data)}`;
    }
    
    // If it's a network error, provide specific guidance
    if (err.name === 'TypeError' && (err.message.includes('fetch') || err.message.includes('Failed to fetch'))) {
      errorDetails.value += ' - Network error: Check if backend server is running on http://localhost:8000';
    }
    
    // If status is not a number, it's likely a frontend error
    if (typeof err.status === 'string' || err.status === undefined) {
      errorDetails.value += ' - This appears to be a frontend/network error, not a backend error';
    }
    
    // Check if it's a network error
    if (err.name === 'TypeError' && err.message.includes('fetch')) {
      errorDetails.value += ' - This appears to be a network error. Check if the backend server is running on the correct port.';
    }
    
    // If we get a 404 error, provide more specific information
    if (err.status === 404) {
      errorDetails.value += ' - The login endpoint was not found. This could indicate a routing issue.';
    }
    
    // If we get a 403 error, provide more specific information
    if (err.status === 403) {
      errorDetails.value += ' - Admin access required. This means the user was authenticated but does not have admin privileges.';
      if (err.data && err.data.message) {
        errorDetails.value += ` Message: ${err.data.message}`;
      }
    }
  } finally {
    loading.value = false
  }
}
</script>