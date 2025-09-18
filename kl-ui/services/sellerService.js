import apiClient from './apiClient';

class SellerService {
  async getDashboardData() {
    try {
      const response = await apiClient.get('/seller/dashboard-direct');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getProducts(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/seller/products${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getProductStats() {
    try {
      const response = await apiClient.get('/seller/products/stats');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getPricePerformance() {
    try {
      const response = await apiClient.get('/seller/price-performance');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getAnalytics() {
    try {
      const response = await apiClient.get('/seller/analytics');
      return response.data;
    } catch (error) {
      throw error;
    }
  }
}

export default new SellerService();