import { ref } from 'vue';
import { productService } from '~/services';

export const useProducts = () => {
  const products = ref([]);
  const product = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchProducts = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await productService.getProducts(params);
      products.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch products';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchProduct = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await productService.getProduct(id);
      product.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch product';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const searchProducts = async (query, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await productService.searchProducts(query, params);
      products.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to search products';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createProduct = async (productData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await productService.createProduct(productData);
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
      const data = await productService.updateProduct(id, productData);
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
      const data = await productService.deleteProduct(id);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to delete product';
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
    fetchProducts,
    fetchProduct,
    searchProducts,
    createProduct,
    updateProduct,
    deleteProduct
  };
};