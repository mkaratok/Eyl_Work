// Use the environment variable directly or runtime config in Nuxt
let API_BASE_URL = 'http://localhost:8000/api';
let APP_URL = 'http://localhost:3001';

// Try to get from Nuxt runtime config if available
if (typeof window !== 'undefined' && window.$nuxt && window.$nuxt.$config) {
  API_BASE_URL = window.$nuxt.$config.public.API_BASE_URL || API_BASE_URL;
  APP_URL = window.$nuxt.$config.public.APP_URL || APP_URL;
} else if (typeof process !== 'undefined' && process.env) {
  API_BASE_URL = process.env.API_BASE_URL || API_BASE_URL;
  APP_URL = process.env.APP_URL || APP_URL;
}

console.log('ApiClient: API_BASE_URL', API_BASE_URL);
console.log('ApiClient: APP_URL', APP_URL);

class ApiClient {
  constructor() {
    this.baseURL = API_BASE_URL;
    this.appURL = APP_URL;
    this.csrfInitialized = false;
    this.csrfToken = null;
    console.log('ApiClient: Initialized with baseURL', this.baseURL);
  }

  async initializeCsrf() {
    try {
      console.log('ApiClient: Initializing CSRF token');
      // Use the correct URL for CSRF cookie initialization
      // The baseURL is http://localhost:8000/api, so we need to go up one level to get to /sanctum
      const baseUrlWithoutApi = this.baseURL.replace('/api', '');
      const csrfUrl = `${baseUrlWithoutApi}/sanctum/csrf-cookie`;
      console.log('ApiClient: CSRF URL', csrfUrl);
      const response = await fetch(csrfUrl, {
        method: 'GET',
        credentials: 'include', // Critical for session handling
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        }
      });
      console.log('ApiClient: CSRF initialization response', response.status);
      
      this.csrfInitialized = response.ok;
      return response.ok;
    } catch (error) {
      console.warn('ApiClient: CSRF initialization failed', error);
      this.csrfInitialized = false;
      return false;
    }
  }

  async request(endpoint, options = {}) {
    // Make sure endpoint starts with a slash
    if (!endpoint.startsWith('/')) {
      endpoint = '/' + endpoint;
    }
    
    // Construct the full URL
    let url = this.baseURL;
    // Ensure there's exactly one slash between baseURL and endpoint
    if (!url.endsWith('/')) {
      url += '/';
    }
    if (endpoint.startsWith('/')) {
      endpoint = endpoint.substring(1);
    }
    url += endpoint;
    
    console.log(`ApiClient: Final constructed URL: ${url}`);
    
    const config = {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers
      },
      credentials: 'include', // This is critical for session authentication
      ...options
    };

    // If we're sending FormData, let the browser set the Content-Type automatically
    // This is important for file uploads
    if (options.body instanceof FormData) {
      delete config.headers['Content-Type']; // Let browser set this with boundary
    }

    try {
      console.log(`ApiClient: Sending ${config.method || 'GET'} request to ${url}`);
      
      // Add Bearer token if available
      const token = this.getAuthToken();
      if (token) {
        console.log('ApiClient: Adding Bearer token to request');
        config.headers['Authorization'] = `Bearer ${token}`;
      } else {
        // If no token is available, try to generate one
        console.log('ApiClient: No token available, will try to use session authentication');
        
        // Make sure we have CSRF token initialized for session auth
        if (!this.csrfInitialized) {
          try {
            await this.initializeCsrf();
          } catch (csrfError) {
            console.warn('ApiClient: Failed to initialize CSRF token', csrfError);
          }
        }
      }
      
      const response = await fetch(url, config);
      console.log(`ApiClient: Received response from ${url}`, response.status, response.statusText);
      
      if (!response.ok) {
        let errorData;
        try {
          errorData = await response.json();
        } catch (parseError) {
          try {
            const text = await response.text();
            errorData = { message: text || `HTTP error! status: ${response.status}` };
          } catch (textError) {
            errorData = { message: `HTTP error! status: ${response.status}` };
          }
        }
        
        console.error(`ApiClient: HTTP error ${response.status} from ${url}`, errorData);
        
        const error = new Error(errorData.message || `HTTP error! status: ${response.status}`);
        error.status = response.status;
        error.data = errorData;
        throw error;
      }
      
      // Handle empty responses
      const contentLength = response.headers.get('content-length');
      if (contentLength === '0' || response.status === 204) {
        return {};
      }
      
      const jsonData = await response.json();
      console.log('ApiClient: Parsed JSON response', jsonData);
      return jsonData;
    } catch (error) {
      console.error(`ApiClient: Request failed to ${url}`, error);
      throw error;
    }
  }

  async get(endpoint, options = {}) {
    return this.request(endpoint, { method: 'GET', ...options });
  }

  async post(endpoint, data, options = {}) {
    console.log('ApiClient: Sending POST request with data:', data);
    // If data is FormData, don't stringify it
    const body = data instanceof FormData ? data : JSON.stringify(data);
    return this.request(endpoint, {
      method: 'POST',
      body: body,
      ...options
    });
  }

  async put(endpoint, data, options = {}) {
    // If data is FormData, don't stringify it
    const body = data instanceof FormData ? data : JSON.stringify(data);
    return this.request(endpoint, {
      method: 'PUT',
      body: body,
      ...options
    });
  }

  async patch(endpoint, data, options = {}) {
    // If data is FormData, don't stringify it
    const body = data instanceof FormData ? data : JSON.stringify(data);
    return this.request(endpoint, {
      method: 'PATCH',
      body: body,
      ...options
    });
  }

  async delete(endpoint, options = {}) {
    return this.request(endpoint, { method: 'DELETE', ...options });
  }

  getAuthToken() {
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token');
      if (token) {
        console.log('ApiClient: Found auth token in localStorage');
        return token;
      } else {
        console.log('ApiClient: No auth token found in localStorage');
      }
    }
    return null;
  }
  
  setAuthToken(token) {
    if (typeof window !== 'undefined' && token) {
      console.log('ApiClient: Setting auth token in localStorage');
      localStorage.setItem('auth_token', token);
      return true;
    }
    return false;
  }
  
  removeAuthToken() {
    if (typeof window !== 'undefined') {
      console.log('ApiClient: Removing auth token from localStorage');
      localStorage.removeItem('auth_token');
      return true;
    }
    return false;
  }
}

export default new ApiClient();