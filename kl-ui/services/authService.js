// Use apiClient for all API requests
import apiClient from './apiClient';

class AuthService {
  constructor() {
    this.csrfToken = null;
    console.log('AuthService: Initialized');
  }

  async initializeCsrf() {
    try {
      console.log('AuthService: Initializing CSRF token');
      return await apiClient.initializeCsrf();
    } catch (error) {
      console.warn('AuthService: CSRF initialization failed', error);
      return false;
    }
  }
  
  async userLogin(credentials) {
    try {
      console.log('AuthService: Sending user login request to API', credentials);
      
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Use user login endpoint with correct v1 prefix
      const response = await apiClient.post('/v1/auth/login', credentials);
      console.log('AuthService: Login successful, received data', response);
      
      // Store user data and token if they exist in the response
      if (response && response.data) {
        // Handle the response structure from AuthController
        if (response.data.user) {
          console.log('AuthService: Setting user data', response.data.user);
          this.setUser(response.data.user);
        }
        // Note: AuthController doesn't return a token for session auth
      }
      
      return response;
    } catch (error) {
      console.error('AuthService: Login error', error);
      throw error;
    }
  }

  async adminLogin(credentials) {
    try {
      console.log('AuthService: Sending admin login request to API', credentials);
      
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Use admin login endpoint with correct v1 prefix
      const response = await apiClient.post('/v1/admin/login', credentials);
      console.log('AuthService: Admin login successful, received data', response);
      
      // Extract user data and token from AdminAuthController response structure
      if (response && response.data) {
        if (response.data.user) {
          console.log('AuthService: Setting admin user data', response.data.user);
          this.setUser(response.data.user);
        }
        
        // Store token if available (AdminAuthController returns a token)
        if (response.data.token) {
          console.log('AuthService: Setting admin token', response.data.token.substring(0, 20) + '...');
          apiClient.setAuthToken(response.data.token);
        }
      }
      
      return response;
    } catch (error) {
      console.error('AuthService: Admin login error', error);
      throw error;
    }
  }

  async register(userData) {
    try {
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Use register endpoint with correct v1 prefix
      const response = await apiClient.post('/v1/auth/register', userData);
      console.log('AuthService: Registration response', response);
      
      // For registration, we don't automatically log in the user
      // Just return the response data
      return response;
    } catch (error) {
      console.error('AuthService: Registration error', error);
      throw error;
    }
  }

  async logout() {
    try {
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Try to call general logout endpoint first
      try {
        await apiClient.post('/v1/auth/logout');
        console.log('AuthService: Logout successful via general endpoint');
      } catch (generalLogoutError) {
        console.warn('General logout failed, trying admin logout:', generalLogoutError);
        // Try admin logout as fallback
        await apiClient.post('/v1/admin/logout');
        console.log('AuthService: Logout successful via admin endpoint');
      }
    } catch (error) {
      // Even if the logout endpoint fails, we still want to clear local data
      console.warn('Logout endpoint failed:', error);
    } finally {
      // Clear user data and token
      this.clearUser();
      apiClient.removeAuthToken();
    }
  }

  async getMe() {
    try {
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Try to get current user info from general endpoint first
      let response;
      try {
        response = await apiClient.get('/v1/auth/me');
        console.log('AuthService: Got current user data from general endpoint', response);
      } catch (generalMeError) {
        console.warn('General me endpoint failed, trying admin endpoint:', generalMeError);
        // Try admin endpoint as fallback
        response = await apiClient.get('/v1/admin/me');
        console.log('AuthService: Got current user data from admin endpoint', response);
      }
      
      if (response && response.data) {
        this.setUser(response.data);
        return response.data;
      }
      throw new Error('Invalid response structure');
    } catch (error) {
      console.error('AuthService: Failed to get current user', error);
      // If getting user info fails, clear auth data
      this.logout();
      throw error;
    }
  }

  setUser(user) {
    if (typeof window !== 'undefined') {
      console.log('AuthService: Setting user in localStorage', user);
      localStorage.setItem('user', JSON.stringify(user));
    }
  }

  getUser() {
    if (typeof window !== 'undefined') {
      const user = localStorage.getItem('user');
      console.log('AuthService: Getting user from localStorage', user);
      return user ? JSON.parse(user) : null;
    }
    return null;
  }

  clearUser() {
    if (typeof window !== 'undefined') {
      console.log('AuthService: Clearing user from localStorage');
      localStorage.removeItem('user');
    }
  }

  setToken(token) {
    if (token) {
      console.log('AuthService: Setting token via apiClient');
      apiClient.setAuthToken(token);
    }
  }

  getToken() {
    return apiClient.getAuthToken();
  }

  clearToken() {
    console.log('AuthService: Clearing token via apiClient');
    apiClient.removeAuthToken();
  }

  isAuthenticated() {
    if (typeof window !== 'undefined') {
      const user = this.getUser();
      const token = this.getToken();
      console.log('AuthService: Checking if user is authenticated');
      
      // For session auth, we just need user data
      // For token auth, we need both user data and token
      if (user) {
        console.log('AuthService: User data exists, user is authenticated');
        return true;
      }
      
      console.log('AuthService: Missing user data, user is not authenticated');
      return false;
    }
    return false;
  }
  
  getUserRole() {
    const user = this.getUser();
    if (!user) return null;
    
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
    
    return role;
  }
  
  // Default login method - tries user login first, falls back to admin if needed
  async login(credentials) {
    try {
      // Try user login first
      return await this.userLogin(credentials);
    } catch (error) {
      // If user login fails, try admin login
      console.log('User login failed, trying admin login:', error.message);
      return await this.adminLogin(credentials);
    }
  }

  redirectToDashboard() {
    const role = this.getUserRole();
    
    // Handle different user roles
    if (role === 'super_admin' || role === 'admin') {
      window.location.href = '/admin';
    } else if (role === 'seller' || role === 'sub_seller') {
      window.location.href = '/seller';
    } else {
      window.location.href = '/user';
    }
  }
}

export default new AuthService();