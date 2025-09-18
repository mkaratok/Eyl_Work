import apiClient from '../apiClient';

class AdminCategoryService {
  async getCategories(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/admin/categories${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      
      // Handle different response formats
      if (response && response.data) {
        // If it's a paginated response, return the data array
        if (Array.isArray(response.data)) {
          return response.data;
        } else if (response.data.data && Array.isArray(response.data.data)) {
          return response.data.data;
        }
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('AdminCategoryService error:', error);
      throw error;
    }
  }

  async getCategory(id) {
    try {
      const response = await apiClient.get(`/v1/admin/categories/${id}`);
      
      // Handle different response formats
      if (response && response.data) {
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('AdminCategoryService error:', error);
      throw error;
    }
  }

  async createCategory(categoryData) {
    try {
      const response = await apiClient.post('/v1/admin/categories', categoryData);
      
      // Extract category data from the response structure
      if (response.success && response.data) {
        return response.data;
      }
      
      return response.data || response;
    } catch (error) {
      throw error;
    }
  }

  async updateCategory(id, categoryData) {
    try {
      const response = await apiClient.put(`/v1/admin/categories/${id}`, categoryData);
      
      // Extract category data from the response structure
      if (response.success && response.data) {
        return response.data;
      }
      
      return response.data || response;
    } catch (error) {
      throw error;
    }
  }

  async deleteCategory(id) {
    try {
      const response = await apiClient.delete(`/v1/admin/categories/${id}`);
      
      // Return success status or response data
      if (response.success !== undefined) {
        return response;
      }
      
      return response.data || response;
    } catch (error) {
      throw error;
    }
  }

  async searchCategories(query, params = {}) {
    try {
      const searchParams = { search: query, ...params };
      const queryString = new URLSearchParams(searchParams).toString();
      const endpoint = `/v1/admin/categories/search${queryString ? `?${queryString}` : ''}`;
      const response = await apiClient.get(endpoint);
      
      // Handle different response formats
      if (response && response.data) {
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('AdminCategoryService error:', error);
      throw error;
    }
  }
}

export default new AdminCategoryService();
