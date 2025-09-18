import { ref } from 'vue';
import { adminService } from '~/services';

export const useAdminDashboard = () => {
  const stats = ref(null);
  const realtimeData = ref(null);
  const healthStatus = ref(null);
  const activities = ref(null);
  const quickActions = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchDashboardStats = async (period = '30d') => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminService.getDashboardStats(period);
      stats.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch dashboard stats';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchRealtimeData = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminService.getRealtimeData();
      realtimeData.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch real-time data';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchHealthStatus = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminService.getHealthStatus();
      healthStatus.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch health status';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchActivities = async (type = null, limit = 20, page = 1) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminService.getActivities(type, limit, page);
      activities.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch activities';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchQuickActions = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminService.getQuickActions();
      quickActions.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch quick actions';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    stats,
    realtimeData,
    healthStatus,
    activities,
    quickActions,
    loading,
    error,
    fetchDashboardStats,
    fetchRealtimeData,
    fetchHealthStatus,
    fetchActivities,
    fetchQuickActions
  };
};