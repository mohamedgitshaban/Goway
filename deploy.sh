#!/bin/bash

echo "🚀 Starting Laravel Deployment..."

# 1) Update code
git pull origin main

# 2) Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3) Run migrations
php artisan migrate --force

# 4) Clear & optimize caches
php artisan optimize:clear
php artisan optimize

# 5) Restart queue workers
php artisan queue:restart

# 6) Start queue worker in background
nohup php artisan queue:work --sleep=1 --tries=3 >> storage/logs/queue.log 2>&1 &

# 7) Start scheduler in background
# Laravel 11:
# nohup php artisan schedule:work >> storage/logs/schedule.log 2>&1 &

# Laravel 10 or older:
nohup bash -c 'while true; do php artisan schedule:run; sleep 60; done' >> storage/logs/schedule.log 2>&1 &

echo "🎉 Deployment Completed!"
