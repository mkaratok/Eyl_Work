// Environment variables for API configuration
export const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8000/api';
export const APP_URL = process.env.APP_URL || 'http://localhost:3001';

// Log the API base URL for debugging
console.log('apiConfig: API_BASE_URL from env:', process.env.API_BASE_URL);
console.log('apiConfig: API_BASE_URL final:', API_BASE_URL);
console.log('apiConfig: APP_URL from env:', process.env.APP_URL);
console.log('apiConfig: APP_URL final:', APP_URL);

export default {
  API_BASE_URL,
  APP_URL
};