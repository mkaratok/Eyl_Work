import { computed } from 'vue';
import { useAuth } from '~/composables/useAuth';
import permissionService from '~/services/permissionService';

export const usePermissions = () => {
  const { user } = useAuth();
  
  // Computed property to get user roles
  const userRoles = computed(() => {
    return user.value ? permissionService.getRoleNames(user.value) : [];
  });
  
  // Computed property to get user permissions
  const userPermissions = computed(() => {
    return user.value ? permissionService.getPermissionNames(user.value) : [];
  });
  
  // Check if user has a specific role
  const hasRole = (roles) => {
    if (!user.value) return false;
    return permissionService.hasRole(user.value, roles);
  };
  
  // Check if user has a specific permission
  const hasPermission = (permissions) => {
    if (!user.value) return false;
    return permissionService.hasPermission(user.value, permissions);
  };
  
  // Check if user has any of the specified roles
  const hasAnyRole = (roles) => {
    if (!user.value) return false;
    return permissionService.hasAnyRole(user.value, roles);
  };
  
  // Check if user has all of the specified roles
  const hasAllRoles = (roles) => {
    if (!user.value) return false;
    return permissionService.hasAllRoles(user.value, roles);
  };
  
  // Check if user has any of the specified permissions
  const hasAnyPermission = (permissions) => {
    if (!user.value) return false;
    return permissionService.hasAnyPermission(user.value, permissions);
  };
  
  // Check if user has all of the specified permissions
  const hasAllPermissions = (permissions) => {
    if (!user.value) return false;
    return permissionService.hasAllPermissions(user.value, permissions);
  };
  
  // Check if user is an admin
  const isAdmin = computed(() => {
    return hasRole(['admin', 'super_admin']);
  });
  
  // Check if user is a super admin
  const isSuperAdmin = computed(() => {
    return hasRole('super_admin');
  });
  
  // Check if user is a seller
  const isSeller = computed(() => {
    return hasRole(['seller', 'sub_seller']);
  });
  
  // Check if user is a regular user
  const isRegularUser = computed(() => {
    return hasRole('user') && !hasRole(['admin', 'super_admin', 'seller', 'sub_seller']);
  });
  
  return {
    userRoles,
    userPermissions,
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAllRoles,
    hasAnyPermission,
    hasAllPermissions,
    isAdmin,
    isSuperAdmin,
    isSeller,
    isRegularUser
  };
};