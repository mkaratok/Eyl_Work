import { ref } from 'vue';
import { sellerService } from '~/services';

export const useSellerDashboard = () => {
  const dashboardData = ref(null);
  const products = ref(null);
  const productStats = ref(null);
  const pricePerformance = ref(null);
  const analytics = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchDashboardData = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await sellerService.getDashboardData();
      dashboardData.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch dashboard data';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchProducts = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await sellerService.getProducts(params);
      products.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch products';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchProductStats = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await sellerService.getProductStats();
      productStats.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch product stats';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchPricePerformance = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await sellerService.getPricePerformance();
      pricePerformance.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch price performance';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchAnalytics = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await sellerService.getAnalytics();
      analytics.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch analytics';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    dashboardData,
    products,
    productStats,
    pricePerformance,
    analytics,
    loading,
    error,
    fetchDashboardData,
    fetchProducts,
    fetchProductStats,
    fetchPricePerformance,
    fetchAnalytics
  };
};