import apiClient from './apiClient';

class ProductService {
  async getProducts(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/public/products${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getProduct(id) {
    try {
      const response = await apiClient.get(`/v1/public/products/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async searchProducts(query, params = {}) {
    try {
      const searchParams = { search: query, ...params };
      const queryString = new URLSearchParams(searchParams).toString();
      const endpoint = `/v1/public/products${queryString ? `?${queryString}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  // Admin methods for CRUD operations
  async createProduct(productData) {
    try {
      console.log('Sending product data to API:', productData);
      const response = await apiClient.post('/v1/admin/products', productData);
      console.log('Received response from API:', response);
      
      // Extract product data from the response structure
      // Admin endpoints return: { success: true, data: productData, message: "..." }
      if (response.success && response.data) {
        return response.data;
      }
      
      // If response format is different, return the whole response
      return response;
    } catch (error) {
      console.error('Error creating product:', error);
      throw error;
    }
  }

  async updateProduct(id, productData) {
    try {
      const response = await apiClient.put(`/v1/admin/products/${id}`, productData);
      
      // Extract product data from the response structure
      if (response.success && response.data) {
        return response.data;
      }
      
      return response;
    } catch (error) {
      throw error;
    }
  }

  async deleteProduct(id) {
    try {
      const response = await apiClient.delete(`/v1/admin/products/${id}`);
      
      // Return success status or response data
      if (response.success !== undefined) {
        return response;
      }
      
      return response;
    } catch (error) {
      throw error;
    }
  }
}

export default new ProductService();