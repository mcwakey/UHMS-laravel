# UHMS Deployment Guide

## Server Requirements
- PHP 8.2+ with extensions: BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML, MySQL/MariaDB driver
- MySQL 8.0+ / MariaDB 10.6+
- Composer 2.x
- Node.js 18+ (for asset compilation only)
- Apache 2.4+ or Nginx 1.21+
- SSL certificate (Let's Encrypt recommended)

## Initial Deployment

```bash
# 1. Clone repository
git clone <repo-url> /var/www/uhms
cd /var/www/uhms

# 2. Install dependencies
composer install --optimize-autoloader --no-dev

# 3. Environment setup
cp .env.production .env
php artisan key:generate
# Edit .env with your production values

# 4. Run migrations
php artisan migrate --force

# 5. Seed initial data (roles, permissions, admin user, settings)
php artisan db:seed --force

# 6. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache  # If using Blade Icons

# 7. Storage link
php artisan storage:link

# 8. Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Apache Virtual Host Configuration

```apache
<VirtualHost *:443>
    ServerName uhms.your-domain.com
    DocumentRoot /var/www/uhms/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/uhms.your-domain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/uhms.your-domain.com/privkey.pem

    <Directory /var/www/uhms/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/uhms-error.log
    CustomLog ${APACHE_LOG_DIR}/uhms-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName uhms.your-domain.com
    Redirect permanent / https://uhms.your-domain.com/
</VirtualHost>
```

## Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name uhms.your-domain.com;
    root /var/www/uhms/public;

    ssl_certificate /etc/letsencrypt/live/uhms.your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/uhms.your-domain.com/privkey.pem;

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
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name uhms.your-domain.com;
    return 301 https://$host$request_uri;
}
```

## Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/uhms-worker.conf`:

```ini
[program:uhms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/uhms/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/uhms/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start uhms-worker:*
```

## Scheduled Tasks (Cron)

Add to crontab (`crontab -e` as www-data):

```
* * * * * cd /var/www/uhms && php artisan schedule:run >> /dev/null 2>&1
```

## Backup Strategy

### Database Backup (daily via cron)

```bash
#!/bin/bash
# /opt/scripts/uhms-backup.sh
BACKUP_DIR="/var/backups/uhms"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Database dump
mysqldump -u uhms_user -p'PASSWORD' uhms | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Application files (uploads only — code is in git)
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" /var/www/uhms/storage/app/public

# Retain last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

Cron entry:
```
0 2 * * * /opt/scripts/uhms-backup.sh >> /var/log/uhms-backup.log 2>&1
```

## Update / Re-deployment

```bash
cd /var/www/uhms

# Pull latest code
git pull origin main

# Install updated dependencies
composer install --optimize-autoloader --no-dev

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
sudo supervisorctl restart uhms-worker:*
```

## Monitoring

- **Application Health**: `https://uhms.your-domain.com/up`
- **Laravel Logs**: `storage/logs/laravel.log` (daily rotation)
- **Queue Monitor**: `php artisan queue:monitor database --max=100`
- **Failed Jobs**: `php artisan queue:failed`
- **Activity Log**: Built-in via Spatie Activity Log (accessible from admin panel)
