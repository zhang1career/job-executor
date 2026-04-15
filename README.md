# Quick Start

This is a PHP job executor for XXL-JOB.

It supports:
* service discovery
* API gateway SSO


## XXL-JOB Configuration

### 1. Create a job executor

#### Option 1 — via the xxl-job-admin UI

In the Admin UI:
- Go to Executor Management → Add
- AppName: `xxl-job-executor-php`
- Name: `PHP Executor`
- Registration: `Manual`
- Address: `http://nginx:8199/api/xxl-job`

#### Option 2 — from the job-executor command line

Run:

```shell
php artisan cmd:xxljob register
```

### 2. Create a task

Example: create a service-discovery task that uses the PHP executor.

In the Admin UI:
- Go to Task Management → Add
- Executor: `PHP Executor`
- JobHandler: `discoverService`



### 3. Listen for jobs
Login the machine (e.g. docker container), and run the job executor:

```shell
php artisan queue:work
```


# Features

## Service Discovery

To enable service discovery, add a label to each service in your `docker-compose.yml`.
Use the `appmap=` prefix followed by `SERVICE_NAME:MACHINE_PORT`.
Replace `SERVICE_NAME` and `MACHINE_PORT` with your service's name and port.

Example:

```yaml
labels:
  - "appmap=${SERVICE_NAME}:${MACHINE_PORT}"
```

When the `discoverService` job runs, it scans the services defined in `docker-compose.yml`,
reads the `appmap` labels to extract service names and ports, and registers them in Redis.


## API Gateway SSO

The API gateway SSO keeps tokens refreshed so services can call APIs without interruption.

Enable the `login*` (e.g. `loginRuleBroker`) jobs in the xxl-job-admin UI to start the SSO login process.

Enalbe the `refresh*` (e.g. `refreshRuleBroker`) jobs to keep the tokens refreshed.

# Acknowledgments

Thanks to [yupeng2015](https://github.com/yupeng2015). This project's XXL-JOB implementation is
based on and inspired by the `xxljob-exe-laravel` repository: https://github.com/yupeng2015/xxljob-exe-laravel
