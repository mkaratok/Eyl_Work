import apiClient from '../apiClient';

class AdminProductService {
  async getProducts(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/admin/products${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response;
    } catch (error) {
      throw error;
    }
  }

  async getProduct(id) {
    try {
      const response = await apiClient.get(`/v1/admin/products/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async createProduct(productData) {
    try {
      const response = await apiClient.post('/v1/admin/products', productData);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async updateProduct(id, productData) {
    try {
      const response = await apiClient.put(`/v1/admin/products/${id}`, productData);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async deleteProduct(id) {
    try {
      const response = await apiClient.delete(`/v1/admin/products/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getProductStats() {
    try {
      const response = await apiClient.get('/v1/admin/products/stats');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getPendingProducts(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/admin/products/pending${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async approveProduct(id, notes = null) {
    try {
      const response = await apiClient.post(`/v1/admin/products/${id}/approve`, { notes });
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async rejectProduct(id, notes) {
    try {
      const response = await apiClient.post(`/v1/admin/products/${id}/reject`, { notes });
      return response.data;
    } catch (error) {
      throw error;
    }
  }
}

export default new AdminProductService();
