import { ref } from 'vue';
import { categoryService } from '~/services';

export const useCategories = () => {
  const categories = ref([]);
  const category = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchCategories = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.getCategories(params);
      categories.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch categories';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchCategory = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.getCategory(id);
      category.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch category';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createCategory = async (categoryData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.createCategory(categoryData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to create category';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateCategory = async (id, categoryData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.updateCategory(id, categoryData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update category';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteCategory = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.deleteCategory(id);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to delete category';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const searchCategories = async (query, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await categoryService.searchCategories(query, params);
      categories.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to search categories';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    categories,
    category,
    loading,
    error,
    fetchCategories,
    fetchCategory,
    createCategory,
    updateCategory,
    deleteCategory,
    searchCategories
  };
};