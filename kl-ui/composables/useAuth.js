import { ref } from 'vue';
import { useRouter } from 'vue-router';
// Import the updated auth service that handles admin authentication
import authService from '~/services/authService';
import apiClient from '~/services/apiClient';

const user = ref(null);
const isAuthenticated = ref(false);

export const useAuth = () => {
  const router = useRouter();
  const loading = ref(false);
  const error = ref(null);

  // Initialize auth state
  const initializeAuth = () => {
    console.log('useAuth: Initializing auth state');
    user.value = authService.getUser();
    isAuthenticated.value = authService.isAuthenticated();
    console.log('useAuth: Initialized user:', user.value);
    console.log('useAuth: Is authenticated:', isAuthenticated.value);
  };

  // Login function
  const login = async (credentials) => {
    loading.value = true;
    error.value = null;
    
    try {
      console.log('Auth composable: Attempting login with credentials', credentials);
      
      // Initialize CSRF before login
      try {
        await apiClient.initializeCsrf();
        console.log('Auth composable: CSRF initialized before login');
      } catch (csrfError) {
        console.warn('Auth composable: CSRF initialization failed:', csrfError);
      }
      
      const response = await authService.login(credentials);
      console.log('Auth composable: Login response', response);
      
      // Extract user data from response - handle both response structures
      const userData = (response.data && response.data.user) ? response.data.user : response.user;
      user.value = userData;
      isAuthenticated.value = true;
      console.log('Auth composable: Updated user state', user.value);
      console.log('Auth composable: Updated auth state', isAuthenticated.value);
      return response;
    } catch (err) {
      console.error('Auth composable: Login error', err);
      // More detailed error handling
      if (err.data && err.data.message) {
        error.value = err.data.message;
      } else if (err.message) {
        error.value = err.message;
      } else {
        error.value = 'Login failed';
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Register function
  const register = async (userData) => {
    loading.value = true;
    error.value = null;
    
    try {
      // Call the register method from authService
      const response = await authService.register(userData);
      console.log('Auth composable: Registration response', response);
      return response;
    } catch (err) {
      console.error('Auth composable: Registration error', err);
      // More detailed error handling
      if (err.data && err.data.errors) {
        // Handle validation errors
        const errorMessages = Object.values(err.data.errors).flat();
        error.value = errorMessages.join(', ') || 'Registration failed';
      } else if (err.data && err.data.message) {
        error.value = err.data.message;
      } else if (err.message) {
        error.value = err.message;
      } else {
        error.value = 'Registration failed';
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Logout function
  const logout = async () => {
    loading.value = true;
    
    try {
      // Initialize CSRF before logout
      try {
        await apiClient.initializeCsrf();
        console.log('Auth composable: CSRF initialized before logout');
      } catch (csrfError) {
        console.warn('Auth composable: CSRF initialization failed:', csrfError);
      }
      
      // Call the logout endpoint and clear user data
      await authService.logout();
      user.value = null;
      isAuthenticated.value = false;
      // Redirect to login page after logout
      router.push('/login');
    } catch (err) {
      error.value = err.message || 'Logout failed';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Get current user
  const getCurrentUser = async () => {
    if (!isAuthenticated.value) return null;
    
    loading.value = true;
    error.value = null;
    
    try {
      // Initialize CSRF before getting user
      try {
        await apiClient.initializeCsrf();
        console.log('Auth composable: CSRF initialized before getting user');
      } catch (csrfError) {
        console.warn('Auth composable: CSRF initialization failed:', csrfError);
      }
      
      const userData = authService.getUser();
      user.value = userData;
      return userData;
    } catch (err) {
      error.value = err.message || 'Failed to fetch user data';
      // If we get an auth error, log the user out
      if (err.message.includes('Unauthorized')) {
        await logout();
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    user: user,
    isAuthenticated: isAuthenticated,
    loading,
    error,
    initializeAuth,
    login,
    register,
    logout,
    getCurrentUser
  };
};