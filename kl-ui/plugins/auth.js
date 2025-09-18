import { defineNuxtPlugin } from '#imports';
import { useAuth } from '~/composables/useAuth';
import apiClient from '~/services/apiClient';
import authService from '~/services/authService';

export default defineNuxtPlugin(async (nuxtApp) => {
  console.log('Auth plugin: Initializing auth state and CSRF token');
  
  // Initialize CSRF token first
  if (process.client) {
    try {
      await apiClient.initializeCsrf();
      console.log('Auth plugin: CSRF token initialized');
    } catch (error) {
      console.warn('Auth plugin: CSRF initialization failed', error);
    }
    
    // Also initialize auth service CSRF
    try {
      await authService.initializeCsrf();
      console.log('Auth plugin: Auth service CSRF token initialized');
    } catch (error) {
      console.warn('Auth plugin: Auth service CSRF initialization failed', error);
    }
  }
  
  // Initialize auth state on app startup
  const { initializeAuth } = useAuth();
  initializeAuth();
  console.log('Auth plugin: Auth state initialized');
});