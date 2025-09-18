# API Test Pages

This directory contains two test pages for verifying the API connection between the frontend and backend:

## 1. `/api-test` - Basic API Test
- Simple test page that checks the API health endpoint
- Shows API configuration details
- Displays connection status

## 2. `/test-api` - Advanced API Test
- Comprehensive testing interface
- Multiple endpoint testing capabilities
- Detailed test results display
- Real-time backend status monitoring

## Usage
Both pages automatically run tests when loaded, but you can also manually trigger tests using the buttons provided.

## Available Endpoints for Testing
- `/health` - Basic API health check
- `/api-info` - Detailed API information
- `/categories` - Categories endpoint (requires backend data)

## What These Pages Verify
- ✅ Frontend can connect to backend API
- ✅ API endpoints are responding correctly
- ✅ CORS configuration is working
- ✅ Network connectivity between frontend and backend