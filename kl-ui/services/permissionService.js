/**
 * Permission Service for handling user roles and permissions
 * This service provides utilities to check user permissions in the frontend
 */

class PermissionService {
  /**
   * Check if the user has a specific role
   * @param {Object} user - The user object
   * @param {string|Array} roles - Role name or array of role names
   * @returns {boolean} - Whether the user has the role(s)
   */
  hasRole(user, roles) {
    if (!user || !user.roles) return false;
    
    const userRoles = Array.isArray(user.roles) ? user.roles : [user.roles];
    const targetRoles = Array.isArray(roles) ? roles : [roles];
    
    return userRoles.some(role => {
      const roleName = typeof role === 'object' ? role.name : role;
      return targetRoles.includes(roleName);
    });
  }
  
  /**
   * Check if the user has a specific permission
   * @param {Object} user - The user object
   * @param {string|Array} permissions - Permission name or array of permission names
   * @returns {boolean} - Whether the user has the permission(s)
   */
  hasPermission(user, permissions) {
    if (!user || !user.permissions) return false;
    
    const userPermissions = Array.isArray(user.permissions) ? user.permissions : [user.permissions];
    const targetPermissions = Array.isArray(permissions) ? permissions : [permissions];
    
    return userPermissions.some(permission => {
      const permissionName = typeof permission === 'object' ? permission.name : permission;
      return targetPermissions.includes(permissionName);
    });
  }
  
  /**
   * Check if the user has any of the specified roles
   * @param {Object} user - The user object
   * @param {Array} roles - Array of role names
   * @returns {boolean} - Whether the user has any of the roles
   */
  hasAnyRole(user, roles) {
    return this.hasRole(user, roles);
  }
  
  /**
   * Check if the user has all of the specified roles
   * @param {Object} user - The user object
   * @param {Array} roles - Array of role names
   * @returns {boolean} - Whether the user has all of the roles
   */
  hasAllRoles(user, roles) {
    if (!user || !user.roles) return false;
    
    const userRoles = Array.isArray(user.roles) ? user.roles : [user.roles];
    const targetRoles = Array.isArray(roles) ? roles : [roles];
    
    return targetRoles.every(role => {
      const roleName = typeof role === 'object' ? role.name : role;
      return userRoles.some(userRole => {
        const userRoleName = typeof userRole === 'object' ? userRole.name : userRole;
        return userRoleName === roleName;
      });
    });
  }
  
  /**
   * Check if the user has any of the specified permissions
   * @param {Object} user - The user object
   * @param {Array} permissions - Array of permission names
   * @returns {boolean} - Whether the user has any of the permissions
   */
  hasAnyPermission(user, permissions) {
    return this.hasPermission(user, permissions);
  }
  
  /**
   * Check if the user has all of the specified permissions
   * @param {Object} user - The user object
   * @param {Array} permissions - Array of permission names
   * @returns {boolean} - Whether the user has all of the permissions
   */
  hasAllPermissions(user, permissions) {
    if (!user || !user.permissions) return false;
    
    const userPermissions = Array.isArray(user.permissions) ? user.permissions : [user.permissions];
    const targetPermissions = Array.isArray(permissions) ? permissions : [permissions];
    
    return targetPermissions.every(permission => {
      const permissionName = typeof permission === 'object' ? permission.name : permission;
      return userPermissions.some(userPermission => {
        const userPermissionName = typeof userPermission === 'object' ? userPermission.name : userPermission;
        return userPermissionName === permissionName;
      });
    });
  }
  
  /**
   * Get user role names
   * @param {Object} user - The user object
   * @returns {Array} - Array of role names
   */
  getRoleNames(user) {
    if (!user || !user.roles) return [];
    
    return Array.isArray(user.roles) 
      ? user.roles.map(role => typeof role === 'object' ? role.name : role)
      : [typeof user.roles === 'object' ? user.roles.name : user.roles];
  }
  
  /**
   * Get user permission names
   * @param {Object} user - The user object
   * @returns {Array} - Array of permission names
   */
  getPermissionNames(user) {
    if (!user || !user.permissions) return [];
    
    return Array.isArray(user.permissions) 
      ? user.permissions.map(permission => typeof permission === 'object' ? permission.name : permission)
      : [typeof user.permissions === 'object' ? user.permissions.name : user.permissions];
  }
}

export default new PermissionService();