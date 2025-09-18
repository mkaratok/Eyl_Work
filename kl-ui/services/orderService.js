import apiClient from './apiClient';

class OrderService {
  async getOrders(params = {}) {
    try {
      const query = new URLSearchParams(params).toString();
      const endpoint = `/v1/admin/orders${query ? `?${query}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getOrder(id) {
    try {
      const response = await apiClient.get(`/v1/admin/orders/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async searchOrders(query, params = {}) {
    try {
      const searchParams = { search: query, ...params };
      const queryString = new URLSearchParams(searchParams).toString();
      const endpoint = `/v1/admin/orders${queryString ? `?${queryString}` : ''}`;
      const response = await apiClient.get(endpoint);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  // Admin methods for CRUD operations
  async createOrder(orderData) {
    try {
      const response = await apiClient.post('/v1/admin/orders', orderData);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async updateOrder(id, orderData) {
    try {
      const response = await apiClient.put(`/v1/admin/orders/${id}`, orderData);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async deleteOrder(id) {
    try {
      const response = await apiClient.delete(`/v1/admin/orders/${id}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async updateOrderStatus(id, status) {
    try {
      const response = await apiClient.patch(`/v1/admin/orders/${id}/status`, { status });
      return response.data;
    } catch (error) {
      throw error;
    }
  }
}

export default new OrderService();