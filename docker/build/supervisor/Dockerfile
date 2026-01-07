# 基于你的主 Dockerfile，添加 Supervisor
# 假设你的主 Dockerfile 已经安装了 PHP 和相关扩展

FROM php:8.2-fpm

# 安装 Supervisor
RUN apt-get update && apt-get install -y \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# 复制 Supervisor 配置
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 创建日志目录
RUN mkdir -p /var/log/supervisor

# 设置工作目录
WORKDIR /var/www/html

# 其他配置（根据你的实际 Dockerfile 调整）
# ...

# 启动 Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

