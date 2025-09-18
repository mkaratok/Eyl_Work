import { ref } from 'vue';
import { userService } from '~/services';

export const useUsers = () => {
  const users = ref([]);
  const user = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchUsers = async (params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.getUsers(params);
      users.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch users';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchUser = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.getUser(id);
      user.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to fetch user';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createUser = async (userData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.createUser(userData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to create user';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateUser = async (id, userData) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.updateUser(id, userData);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update user';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteUser = async (id) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.deleteUser(id);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to delete user';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateUserStatus = async (id, status) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.updateUserStatus(id, status);
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to update user status';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const searchUsers = async (query, params = {}) => {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await userService.searchUsers(query, params);
      users.value = data;
      return data;
    } catch (err) {
      error.value = err.message || 'Failed to search users';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    users,
    user,
    loading,
    error,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    deleteUser,
    updateUserStatus,
    searchUsers
  };
};