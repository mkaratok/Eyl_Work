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
      
      // Use user login endpoint
      const response = await apiClient.post('/auth/login', credentials);
      console.log('AuthService: Login successful, received data', response);
      
      // Store user data and token if they exist in the response
      if (response && response.data && response.data.user) {
        console.log('AuthService: Setting user data', response.data.user);
        this.setUser(response.data.user);
        // Store the token if it exists
        if (response.data.token) {
          apiClient.setAuthToken(response.data.token);
        }
      } else if (response && response.user) {
        // Fallback for different response structure
        console.log('AuthService: Setting user data (fallback)', response.user);
        this.setUser(response.user);
        // Store the token if it exists
        if (response.token) {
          apiClient.setAuthToken(response.token);
        }
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
      
      // Extract user data and token from various possible response structures
      let userData = null;
      let token = null;
      
      if (response && response.data && response.data.user) {
        userData = response.data.user;
        token = response.data.token;
      } else if (response && response.user) {
        userData = response.user;
        token = response.token;
      } else if (response && response.success && response.data) {
        userData = response.data;
        token = response.token;
      }
      
      // Store user data if available
      if (userData) {
        console.log('AuthService: Setting admin user data', userData);
        this.setUser(userData);
      } else {
        console.warn('AuthService: No user data found in response');
      }
      
      // Store token if available
      if (token) {
        console.log('AuthService: Setting admin token', token.substring(0, 20) + '...');
        apiClient.setAuthToken(token);
      } else {
        console.warn('AuthService: No token found in response');
      }
      
      return response;
    } catch (error) {
      console.error('AuthService: Admin login error', error);
      
      // More detailed error handling
      if (error.message) {
        throw new Error(error.message);
      } else if (error.status) {
        switch (error.status) {
          case 401:
            throw new Error('Invalid email or password');
          case 403:
            throw new Error('Account disabled or insufficient permissions');
          case 404:
            throw new Error('Login endpoint not found - This could indicate a routing issue or the backend server is not running');
          case 500:
            throw new Error('Server error occurred during login');
          default:
            throw new Error(`Login failed with status ${error.status}`);
        }
      } else {
        throw new Error('Network error or server unavailable');
      }
    }
  }

  async register(userData) {
    try {
      // First, get the CSRF cookie
      await this.initializeCsrf();
      
      // Use register endpoint
      const response = await apiClient.post('/auth/register', userData);
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
      
      // Try to call admin logout endpoint
      await apiClient.post('/admin/logout');
      console.log('AuthService: Logout successful');
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
      
      // Get current user info
      const response = await apiClient.get('/admin/me');
      console.log('AuthService: Got current user data', response);
      
      if (response && response.data) {
        this.setUser(response.data);
        return response.data;
      } else if (response) {
        this.setUser(response);
        return response;
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
      
      // We need both user data and token for proper authentication
      if (user && token) {
        console.log('AuthService: User data and token exist, user is authenticated');
        return true;
      }
      
      console.log('AuthService: Missing user data or token, user is not authenticated');
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