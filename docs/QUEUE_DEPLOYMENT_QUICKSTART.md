# 队列工作进程 Docker 部署快速指南

## 快速回答

**Q: 是否应该给 `php artisan queue:work` 单独开辟一个 Docker 容器？**

**A: 是的，强烈建议！** 这是生产环境的最佳实践。

## 为什么？

1. **职责分离**：Web 服务和队列处理有不同的资源需求
2. **独立扩展**：可以根据流量和任务量分别扩展
3. **故障隔离**：一个服务的问题不会影响另一个
4. **资源管理**：可以为不同服务设置不同的资源限制

## 三种部署方案

### 方案一：分离容器（推荐 ⭐）

**优点：** 简单、清晰、易于管理

```bash
# 使用 docker-compose.yml
docker-compose -f docker-compose.yml up -d
```

**架构：**
- `app` 容器：处理 HTTP 请求
- `queue-worker` 容器：处理队列任务
- 可以启动多个 `queue-worker` 实例

### 方案二：Supervisor 管理多进程

**优点：** 一个容器内运行多个工作进程，资源利用效率高

```bash
# 使用 docker-compose.yml
docker-compose -f docker-compose.yml up -d
```

**架构：**
- `app` 容器：处理 HTTP 请求
- `queue-worker` 容器：使用 Supervisor 管理多个 `queue:work` 进程

### 方案三：Kubernetes 部署（大型项目）

如果使用 Kubernetes，可以：

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: queue-worker
spec:
  replicas: 3  # 可以根据需要扩展
  template:
    spec:
      containers:
      - name: queue-worker
        image: your-image:tag
        command: ["php", "artisan", "queue:work"]
        resources:
          requests:
            memory: "256Mi"
            cpu: "0.5"
          limits:
            memory: "512Mi"
            cpu: "1"
```

## 关键配置参数

### `queue:work` 推荐参数

```bash
php artisan queue:work \
  --queue=default \          # 队列名称
  --tries=3 \                # 最大重试次数
  --timeout=60 \             # 任务超时时间（秒）
  --max-time=3600 \          # 工作进程最大运行时间（秒），防止内存泄漏
  --sleep=3 \                # 无任务时等待时间（秒）
  --max-jobs=1000 \          # 处理 N 个任务后重启，防止内存泄漏
  --verbose                  # 详细输出
```

### 环境变量配置

```env
# .env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90

# 队列工作进程配置
QUEUE_NAME=default
QUEUE_TRIES=3
QUEUE_TIMEOUT=60
QUEUE_MAX_TIME=3600
QUEUE_SLEEP=3
QUEUE_MAX_JOBS=1000
```

## 监控和健康检查

### 1. 监控队列长度

```bash
# 检查队列中待处理任务数
php artisan queue:monitor

# 或直接查询数据库
SELECT COUNT(*) FROM jobs WHERE reserved_at IS NULL;
```

### 2. 监控失败任务

```bash
# 查看失败任务
php artisan queue:failed

# 重试失败任务
php artisan queue:retry all
```

### 3. 容器健康检查

在 `docker-compose.yml` 中添加：

```yaml
queue-worker:
  healthcheck:
    test: ["CMD", "php", "artisan", "queue:monitor"]
    interval: 30s
    timeout: 10s
    retries: 3
    start_period: 40s
```

## 常见问题

### Q1: 队列工作进程突然停止？

**可能原因：**
- 内存不足（使用 `--max-jobs` 定期重启）
- 任务超时（检查 `--timeout` 设置）
- 数据库连接断开（检查连接池配置）

**解决方案：**
```bash
# 增加内存限制
docker-compose up -d --scale queue-worker=2

# 检查日志
docker logs job-executor-queue-worker
```

### Q2: 任务执行很慢？

**可能原因：**
- 工作进程数量不足
- 任务本身耗时较长

**解决方案：**
```bash
# 增加工作进程数量
docker-compose up -d --scale queue-worker=4

# 或使用 Supervisor 方案，增加进程数
QUEUE_WORKER_NUM=8 docker-compose -f docker-compose.yml up -d
```

### Q3: 如何处理不同类型的队列？

**解决方案：** 为不同队列创建不同的工作进程

```yaml
queue-worker-default:
  command: php artisan queue:work --queue=default

queue-worker-high:
  command: php artisan queue:work --queue=high --tries=5
```

## 生产环境检查清单

- [ ] 使用 `queue:work` 而不是 `queue:listen`
- [ ] 设置合理的 `--max-jobs` 防止内存泄漏
- [ ] 设置合理的 `--max-time` 定期重启
- [ ] 配置资源限制（CPU、内存）
- [ ] 设置健康检查
- [ ] 配置日志收集
- [ ] 监控队列长度和失败任务
- [ ] 配置自动重启策略
- [ ] 使用共享存储（如果需要文件操作）
- [ ] 配置数据库连接池

## 参考文档

详细说明请查看：[DOCKER_QUEUE_ARCHITECTURE.md](./DOCKER_QUEUE_ARCHITECTURE.md)

