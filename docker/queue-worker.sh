#!/bin/bash

# 队列工作进程启动脚本
# 用于在容器启动时执行必要的初始化操作

set -e

echo "Starting Laravel Queue Worker..."

# 等待数据库就绪
echo "Waiting for database..."
until php artisan db:monitor 2>/dev/null || php -r "try { new PDO('mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Database ready'; } catch (Exception \$e) { exit(1); }"; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# 缓存配置（生产环境）
if [ "${APP_ENV}" = "production" ]; then
    echo "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# 运行队列工作进程
echo "Starting queue worker..."
exec php artisan queue:work \
    --queue="${QUEUE_NAME:-default}" \
    --tries="${QUEUE_TRIES:-3}" \
    --timeout="${QUEUE_TIMEOUT:-60}" \
    --max-time="${QUEUE_MAX_TIME:-3600}" \
    --sleep="${QUEUE_SLEEP:-3}" \
    --max-jobs="${QUEUE_MAX_JOBS:-1000}" \
    --verbose

