import { useAuth } from '~/composables/useAuth'

export default defineNuxtRouteMiddleware((to, from) => {
  const { isAuthenticated, user } = useAuth()
  
  console.log('Guest middleware: Checking if user is authenticated', isAuthenticated.value);
  console.log('Guest middleware: User data', user.value);
  
  // If user is already authenticated and trying to access login/register pages,
  // redirect them to their appropriate dashboard
  if (isAuthenticated.value && (to.path === '/login' || to.path === '/register')) {
    console.log('Guest middleware: User is authenticated, redirecting from auth page');
    
    // Check user role and redirect accordingly
    const roles = user.value?.roles || (user.value?.role ? [user.value.role] : [])
    // Fix: roles[0] is already a string, not an object with a name property
    const role = roles.length > 0 ? (typeof roles[0] === 'object' ? roles[0].name : roles[0]) : null
    
    console.log('Guest middleware: User roles', roles);
    console.log('Guest middleware: Primary role', role);
    
    if (role === 'admin' || role === 'super_admin') {
      console.log('Guest middleware: Redirecting admin user to admin dashboard');
      return navigateTo('/admin')
    } else if (role === 'seller') {
      console.log('Guest middleware: Redirecting seller user to seller dashboard');
      return navigateTo('/seller')
    } else {
      console.log('Guest middleware: Redirecting regular user to user dashboard');
      return navigateTo('/user')
    }
  }
  
  console.log('Guest middleware: Allowing access to route');
})