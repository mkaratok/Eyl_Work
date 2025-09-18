import { ref } from 'vue';
import { orderService } from '~/services';

export const useOrders = () => {
  const orders = ref([]);
  const order = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchOrders = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.getOrders(params);
      orders.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch orders';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchOrder = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.getOrder(id);
      order.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch order';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const searchOrders = async (query, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.searchOrders(query, params);
      orders.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to search orders';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createOrder = async (orderData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.createOrder(orderData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to create order';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateOrder = async (id, orderData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.updateOrder(id, orderData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update order';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteOrder = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.deleteOrder(id);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to delete order';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateOrderStatus = async (id, status) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await orderService.updateOrderStatus(id, status);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update order status';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    orders,
    order,
    loading,
    error,
    fetchOrders,
    fetchOrder,
    searchOrders,
    createOrder,
    updateOrder,
    deleteOrder,
    updateOrderStatus
  };
};