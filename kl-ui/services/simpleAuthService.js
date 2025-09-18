// Environment variables for API configuration
const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8000/api';
const APP_URL = process.env.APP_URL || 'http://localhost:3001';

console.log('SimpleAuthService: Initialized with API_BASE_URL', API_BASE_URL);
console.log('SimpleAuthService: Initialized with APP_URL', APP_URL);

// Simplified authentication service that directly uses fetch
class SimpleAuthService {
  constructor() {
    this.baseURL = API_BASE_URL;
    this.appURL = APP_URL;
    console.log('SimpleAuthService: Initialized with baseURL', this.baseURL);
  }
  
  async login(credentials) {
    try {
      console.log('SimpleAuthService: Sending login request to API', credentials);
      
      // First, get the CSRF cookie
      console.log('SimpleAuthService: Getting CSRF cookie');
      try {
        const csrfResponse = await fetch('http://localhost:8000/sanctum/csrf-cookie', {
          method: 'GET',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          }
        });
        console.log('SimpleAuthService: CSRF cookie response', csrfResponse.status);
      } catch (csrfError) {
        console.warn('SimpleAuthService: CSRF cookie request failed, continuing anyway', csrfError);
      }
      
      // Then make the login request to the general auth endpoint
      const url = `${this.baseURL}/v1/auth/login`;
      console.log('SimpleAuthService: Full URL', url);
      
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include',
        body: JSON.stringify(credentials)
      });
      
      console.log('SimpleAuthService: Received login response from API', response.status, response.statusText);
      console.log('SimpleAuthService: Response OK?', response.ok);
      
      // Log response headers for debugging
      console.log('SimpleAuthService: Response headers', [...response.headers.entries()]);
      
      // Always try to parse the response as JSON first
      let data;
      try {
        const responseText = await response.text();
        console.log('SimpleAuthService: Raw response text', responseText);
        data = JSON.parse(responseText);
        console.log('SimpleAuthService: Parsed JSON response', data);
      } catch (parseError) {
        console.error('SimpleAuthService: Failed to parse response as JSON', parseError);
        const error = new Error('Invalid JSON response from server');
        error.status = response.status;
        error.data = { parseError: parseError.message };
        throw error;
      }
      
      // Check if the HTTP response was successful
      if (!response.ok) {
        console.error('SimpleAuthService: HTTP error', response.status, data);
        const error = new Error(data.message || `HTTP error! status: ${response.status}`);
        error.status = response.status;
        error.data = data;
        throw error;
      }
      
      // Check if we have a success response
      if (data && data.success === true && data.data && data.data.user) {
        // Store user data
        console.log('SimpleAuthService: Setting user data', data.data.user);
        this.setUser(data.data.user);
        return data;
      } else if (data && data.user) {
        // Alternative format - some APIs return user directly
        console.log('SimpleAuthService: Setting user data (alternative format)', data.user);
        this.setUser(data.user);
        return { success: true, data: data };
      } else {
        console.error('SimpleAuthService: Unexpected response format', data);
        const error = new Error('Invalid response format from server');
        error.status = response.status;
        error.data = data;
        throw error;
      }
    } catch (error) {
      console.error('SimpleAuthService: Login error', error);
      // Ensure the error has proper structure
      if (!error.status) {
        error.status = 'NETWORK_ERROR';
      }
      throw error;
    }
  }

  setUser(user) {
    if (typeof window !== 'undefined') {
      console.log('SimpleAuthService: Setting user in localStorage', user);
      localStorage.setItem('user', JSON.stringify(user));
    }
  }

  getUser() {
    if (typeof window !== 'undefined') {
      const user = localStorage.getItem('user');
      console.log('SimpleAuthService: Getting user from localStorage', user);
      return user ? JSON.parse(user) : null;
    }
    return null;
  }

  clearUser() {
    if (typeof window !== 'undefined') {
      console.log('SimpleAuthService: Clearing user from localStorage');
      localStorage.removeItem('user');
    }
  }

  isAuthenticated() {
    if (typeof window !== 'undefined') {
      const user = this.getUser();
      console.log('SimpleAuthService: Checking if user is authenticated', user);
      return !!user;
    }
    return false;
  }
}

export default new SimpleAuthService();