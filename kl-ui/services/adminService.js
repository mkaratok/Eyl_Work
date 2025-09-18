import apiClient from './apiClient';

class AdminService {
  async getDashboardStats(period = '30d') {
    try {
      const response = await apiClient.get(`/v1/admin/dashboard/stats?period=${period}`);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getRealtimeData() {
    try {
      const response = await apiClient.get('/v1/admin/dashboard/realtime');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getHealthStatus() {
    try {
      const response = await apiClient.get('/v1/admin/dashboard/health');
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getActivities(type = null, limit = 20, page = 1) {
    try {
      let url = '/v1/admin/dashboard/activities';
      const params = new URLSearchParams();
      
      if (type) params.append('type', type);
      if (limit) params.append('limit', limit);
      if (page) params.append('page', page);
      
      if (params.toString()) {
        url += `?${params.toString()}`;
      }
      
      const response = await apiClient.get(url);
      return response.data;
    } catch (error) {
      throw error;
    }
  }

  async getQuickActions() {
    try {
      const response = await apiClient.get('/v1/admin/dashboard/quick-actions');
      return response.data;
    } catch (error) {
      throw error;
    }
  }
}

export default new AdminService();