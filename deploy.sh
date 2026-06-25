#!/bin/bash
cd /home/clothonic/htdocs/laravel.netpilotdigital.com
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link
echo "✅ Deploy complete!"
