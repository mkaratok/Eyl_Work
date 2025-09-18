# Kaçlıra.com Frontend Deployment Guide

## Prerequisites

- Node.js (>= 18.x)
- npm (>= 8.x) or yarn (>= 1.22.x)

## Production Build

To create a production build:

```bash
npm run build
```

This command will generate a `.output` directory with the production build.

## Deployment Options

### Option 1: Node.js Server Deployment

1. Copy the entire project directory to your server.
2. Install dependencies:

```bash
npm install --production
```

3. Build the application:

```bash
npm run build
```

4. Start the server:

```bash
npm start
```

The application will be available on port 3000 by default.

### Option 2: Static Deployment

If you prefer to deploy as a static site:

```bash
npm run generate
```

This will generate a `dist` directory with static files that can be deployed to any static hosting service.

### Option 3: Docker Deployment

Build the Docker image:

```bash
docker build -t kaclira-frontend .
```

Run the container:

```bash
docker run -p 3000:3000 kaclira-frontend
```

## Deploy to Vercel

1. Push your code to a Git repository.
2. Log in to your Vercel account and import the repository.
3. Vercel will automatically detect the Nuxt 3 project and configure the deployment settings.
4. Click "Deploy" to deploy your application.

## Environment Variables

Set the following environment variables in your deployment environment:

- `API_BASE_URL`: The base URL of your Laravel API.
- `APP_URL`: The URL where the frontend will be accessible.

Example for production:

```bash
API_BASE_URL=https://api.kaclira.com
APP_URL=https://kaclira.com
```

## Server Configuration

### Nginx Configuration

If you're serving the application with Nginx, use the following configuration:

```nginx
server {
    listen 80;
    server_name kaclira.com;
    
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Apache Configuration

If you're using Apache, ensure mod_proxy is enabled and use:

```apache
<VirtualHost *:80>
    ServerName kaclira.com
    
    ProxyPreserveHost On
    ProxyPass / http://localhost:3000/
    ProxyPassReverse / http://localhost:3000/
</VirtualHost>
```

## Production Security Considerations

1. Always use HTTPS in production
2. Set proper Content Security Policy headers
3. Regularly update dependencies
4. Use strong, unique API keys
5. Monitor client-side errors
6. Implement proper rate limiting on the backend API

## Performance Optimization

1. Enable gzip compression on your web server
2. Use a CDN for static assets
3. Implement proper caching headers
4. Minimize and compress assets
5. Use lazy loading for images and components
6. Optimize API calls to reduce unnecessary requests

## Monitoring and Maintenance

1. Set up error tracking (e.g., Sentry)
2. Implement performance monitoring
3. Regularly check and update dependencies
4. Monitor server resources and application performance
5. Set up alerts for critical errors
6. Regularly backup application data (if applicable)

## Troubleshooting

1. If the application fails to start, check the Node.js version compatibility
2. Verify all environment variables are properly set
3. Check that the API is accessible from the frontend server
4. Ensure proper file permissions on the server
5. Check browser console for client-side errors
6. Review server logs for backend errors

For additional help, refer to the [Nuxt 3 deployment documentation](https://nuxt.com/docs/getting-started/deployment).