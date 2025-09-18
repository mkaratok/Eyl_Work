import apiClient from './apiClient';

class UserService {
  constructor() {
    // Initialize with API client
    console.log('UserService: Initializing with API client');
  }

  // Format API response to a consistent structure
  formatUserResponse(response) {
    // Handle different API response structures
    if (response.data && response.data.users) {
      return response.data.users;
    } else if (response.data) {
      return response.data;
    }
    return response;
  }

  async getUsers(params = {}) {
    try {
      console.log('UserService: Fetching users from API with params:', params);
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/admin/users${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      
      console.log('UserService: Received users from API');
      return this.formatUserResponse(response);
    } catch (error) {
      console.error('UserService: Failed to fetch users from API:', error);
      throw error;
    }
  }

  async getUser(id) {
    try {
      console.log(`UserService: Fetching user ${id} from API`);
      const response = await apiClient.get(`/v1/admin/users/${id}`);
      
      console.log('UserService: Received user from API');
      if (response.data && response.data.user) {
        return response.data.user;
      }
      return response.data || response;
    } catch (error) {
      console.error(`UserService: Failed to fetch user ${id} from API:`, error);
      throw error;
    }
  }

  async createUser(userData) {
    try {
      console.log('UserService: Creating user via API:', userData);
      const response = await apiClient.post('/v1/admin/users', userData);
      
      console.log('UserService: User created via API');
      return response.data || response;
    } catch (error) {
      console.error('UserService: Failed to create user via API:', error);
      throw error;
    }
  }

  async updateUser(id, userData) {
    try {
      console.log(`UserService: Updating user ${id} via API:`, userData);
      const response = await apiClient.put(`/v1/admin/users/${id}`, userData);
      
      console.log(`UserService: User ${id} updated via API`);
      return response.data || response;
    } catch (error) {
      console.error(`UserService: Failed to update user ${id} via API:`, error);
      throw error;
    }
  }

  async deleteUser(id) {
    try {
      console.log(`UserService: Deleting user ${id} via API`);
      const response = await apiClient.delete(`/v1/admin/users/${id}`);
      
      console.log(`UserService: User ${id} deleted via API`);
      return response.data || response;
    } catch (error) {
      console.error(`UserService: Failed to delete user ${id} via API:`, error);
      throw error;
    }
  }

  async updateUserStatus(id, status) {
    try {
      console.log(`UserService: Updating user ${id} status to ${status} via API`);
      const response = await apiClient.patch(`/v1/admin/users/${id}/status`, { status });
      
      console.log(`UserService: User ${id} status updated via API`);
      return response.data || response;
    } catch (error) {
      console.error(`UserService: Failed to update user ${id} status via API:`, error);
      throw error;
    }
  }

  async searchUsers(query, params = {}) {
    try {
      console.log('UserService: Searching users via API:', query, params);
      const searchParams = { search: query, ...params };
      const queryString = new URLSearchParams(searchParams).toString();
      const endpoint = `/v1/admin/users${queryString ? `?${queryString}` : ''}`;
      
      const response = await apiClient.get(endpoint);
      
      console.log('UserService: Search results received from API');
      return this.formatUserResponse(response);
    } catch (error) {
      console.error('UserService: Failed to search users via API:', error);
      throw error;
    }
  }
}

export default new UserService();
