import apiClient from './apiClient';

class CategoryService {
  async getCategories() {
    try {
      // Use the correct endpoint that we know works
      const response = await apiClient.get('/v1/public/categories');
      
      // Handle different response formats
      if (response && response.data) {
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('CategoryService error:', error);
      throw error;
    }
  }

  async getCategory(id) {
    try {
      const response = await apiClient.get(`/v1/public/categories/${id}`);
      
      // Handle different response formats
      if (response && response.data) {
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('CategoryService error:', error);
      throw error;
    }
  }

  // Admin methods for CRUD operations
  async createCategory(categoryData) {
    try {
      const response = await apiClient.post('/v1/admin/categories', categoryData);
      
      // Extract category data from the response structure
      // Admin endpoints return: { success: true, data: categoryData, message: "..." }
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

  async updateCategoryStatus(id, status) {
    try {
      const response = await apiClient.patch(`/v1/admin/categories/${id}/status`, { status });
      
      // Extract category data from the response structure
      if (response.success && response.data) {
        return response.data;
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
      const endpoint = `/v1/public/categories${queryString ? `?${queryString}` : ''}`;
      const response = await apiClient.get(endpoint);
      
      // Handle different response formats
      if (response && response.data) {
        return response.data;
      }
      return response;
    } catch (error) {
      console.error('CategoryService error:', error);
      throw error;
    }
  }
}

export default new CategoryService();