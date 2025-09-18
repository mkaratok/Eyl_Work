import { useAuth } from '~/composables/useAuth'

export default defineNuxtRouteMiddleware((to, from) => {
  const { isAuthenticated } = useAuth()
  
  // Pages that don't require authentication
  const publicPages = ['/', '/login', '/register', '/about', '/products', '/categories', '/deals']
  const authRequired = !publicPages.includes(to.path)
  
  console.log('Auth middleware: Checking authentication for route', to.path);
  console.log('Auth middleware: Is authentication required?', authRequired);
  console.log('Auth middleware: Is user authenticated?', isAuthenticated.value);
  
  if (authRequired && !isAuthenticated.value) {
    console.log('Auth middleware: Redirecting to login page');
    return navigateTo('/login')
  }
  
  console.log('Auth middleware: Allowing access to route');
})