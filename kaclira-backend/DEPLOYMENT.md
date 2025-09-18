# Kaçlıra Backend API Deployment Guide

## Server Requirements

- PHP >= 8.2
- Composer
- MySQL or PostgreSQL database
- Redis (for caching and queues)
- Node.js (for asset compilation)

## Deployment Steps

### 1. Clone the Repository

```bash
git clone <repository-url>
cd kaclira-backend
```

### 2. Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Install Node Dependencies

```bash
npm install --production
```

### 4. Environment Configuration

Create a `.env` file based on `.env.example` and configure the following key variables:

```bash
# Application
APP_NAME="Kaclira API"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.kaclira.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kaclira_db
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

# Cache and Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (configure as needed)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@kaclira.com"
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum Configuration for Production
SANCTUM_STATEFUL_DOMAINS="kaclira.com,www.kaclira.com"
```

Generate the application key:

```bash
php artisan key:generate
```

### 5. Database Setup

Run migrations and seed the database:

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Storage Link

Create a symbolic link for storage:

```bash
php artisan storage:link
```

### 7. Caching

Clear and rebuild caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Web Server Configuration

Configure your web server (Apache/Nginx) to point to the `public` directory.

Example Nginx configuration:

```nginx
server {
    listen 80;
    server_name api.kaclira.com;
    root /path/to/kaclira-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 9. Queue Worker

Set up a queue worker to process background jobs:

```bash
php artisan queue:work --daemon
```

For production, it's recommended to use a process manager like Supervisor to keep the queue worker running.

### 10. Schedule Runner

Set up the Laravel scheduler to run every minute:

```bash
* * * * * cd /path/to/kaclira-backend && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Asset Compilation (if needed)

If you need to compile assets:

```bash
npm run build
```

## Production Security Considerations

1. Set `APP_DEBUG=false` in your `.env` file
2. Use strong, unique passwords for all services
3. Regularly update dependencies
4. Restrict database permissions to only what's needed
5. Use HTTPS in production
6. Regularly backup your database
7. Monitor logs for suspicious activity

## Scaling Considerations

1. Use a load balancer for multiple application servers
2. Use Redis for session storage in load-balanced environments
3. Use a CDN for serving static assets
4. Consider database read replicas for read-heavy operations
5. Implement caching strategies for frequently accessed data

## Maintenance

Regular maintenance tasks:

1. Update dependencies: `composer update`
2. Clear caches when needed: `php artisan cache:clear`
3. Monitor queue workers and restart if needed
4. Check logs regularly for errors
5. Backup database regularly

## Troubleshooting

1. If you get a 500 error, check the Laravel logs in `storage/logs/laravel.log`
2. Ensure file permissions are correct (storage and bootstrap/cache directories should be writable)
3. Verify database connection settings
4. Check that all required PHP extensions are installed