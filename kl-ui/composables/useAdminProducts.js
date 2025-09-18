import { ref } from 'vue';
import adminProductService from '~/services/admin/productService';

export const useAdminProducts = () => {
  const products = ref([]);
  const product = ref(null);
  const loading = ref(false);
  const error = ref(null);
  const pagination = ref({});

  const fetchProducts = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await adminProductService.getProducts(params);
      if (response.success) {
        products.value = response.data;
        pagination.value = response.meta || {};
      } else {
        products.value = [];
        pagination.value = {};
      }
      return response;
    } catch (err) {
      error.value = err.message || 'Failed to fetch products';
      products.value = [];
      pagination.value = {};
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchProduct = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminProductService.getProduct(id);
      product.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch product';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createProduct = async (productData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminProductService.createProduct(productData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to create product';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateProduct = async (id, productData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminProductService.updateProduct(id, productData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update product';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteProduct = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminProductService.deleteProduct(id);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to delete product';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const getProductStats = async () => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await adminProductService.getProductStats();
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch product stats';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    products,
    product,
    loading,
    error,
    pagination,
    fetchProducts,
    fetchProduct,
    createProduct,
    updateProduct,
    deleteProduct,
    getProductStats
  };
};
