

# XXL-JOB Configuration

## Create a job executor

### Option 1: from xxl-job-admin UI

```
执行器管理
  > 新增
    > AppName：xxl-job-executor-php
    > 名称：PHP执行器
    > 注册方式：手动录入
    > 机器地址：http://nginx:8199/api/xxl-job
```

### Option 2: from job-executor command line

```shell
php artisan cmd:xxljob register
```


## Create a task

For example, a service discovering task that uses the PHP executor created above.

```
任务管理
  > 新增
    > 执行器：PHP执行器
    > JobHandler：serviceDiscover
```

## Acknowledgments

Thanks to [yupeng2015](https://github.com/yupeng2015). The XXL-JOB implementation in this project mainly references their code repository: [xxljob-exe-laravel](https://github.com/yupeng2015/xxljob-exe-laravel).
