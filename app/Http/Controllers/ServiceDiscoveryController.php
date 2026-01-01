<?php

namespace App\Http\Controllers;

use App\Services\ContainerLabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ServiceDiscoveryController extends Controller
{
    protected ContainerLabelService $containerLabelService;

    public function __construct(ContainerLabelService $containerLabelService)
    {
        $this->containerLabelService = $containerLabelService;
    }

    /**
     * 服务发现接口
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function discover(Request $request): JsonResponse
    {
        try {
            // 1. 查询同一个容器组中全部容器的标签
            $containers = $this->containerLabelService->getContainersInGroup();

            // 2. 取标签 appmap 的值，以及容器名称，构建 [容器名称：appmap值] 的字典
            $containerAppMap = [];
            foreach ($containers as $container) {
                $containerName = $container['name'];
                $appmap = $container['labels']['appmap'] ?? null;

                if ($appmap) {
                    $containerAppMap[$containerName] = $appmap;
                }
            }

            // 3. 解析 appmap 值：appmap值是字符串，由逗号（,）分隔出小单元；
            //    每一个单元中是冒号（:）分割的 key-value 对。
            //    其中 key 是服务名称，value是容器端口号；
            //    要求解析出 容器名称 - 服务名称 - 容器端口号 的组合
            $serviceMappings = [];
            foreach ($containerAppMap as $containerName => $appmapValue) {
                // 按逗号分隔小单元
                $units = explode(',', $appmapValue);

                foreach ($units as $unit) {
                    $unit = trim($unit);
                    if (empty($unit)) {
                        continue;
                    }

                    // 按冒号分割 key-value
                    $parts = explode(':', $unit);
                    if (count($parts) === 2) {
                        $serviceName = trim($parts[0]);
                        $port = trim($parts[1]);

                        if (!empty($serviceName) && !empty($port)) {
                            $serviceMappings[] = [
                                'container' => $containerName,
                                'service' => $serviceName,
                                'port' => $port,
                            ];
                        }
                    }
                }
            }

            // 4. 将步骤3中的组合，以 服务名称 为key，容器名称:容器端口号
            //    （如果有多个，按逗号分隔）为value，组成新的字典
            $serviceDictionary = [];
            foreach ($serviceMappings as $mapping) {
                $serviceName = $mapping['service'];
                $value = $mapping['container'] . ':' . $mapping['port'];

                if (!isset($serviceDictionary[$serviceName])) {
                    $serviceDictionary[$serviceName] = [];
                }

                $serviceDictionary[$serviceName][] = $value;
            }

            // 将数组转换为逗号分隔的字符串
            foreach ($serviceDictionary as $serviceName => $values) {
                $serviceDictionary[$serviceName] = implode(',', $values);
            }

            // 5. 遍历新的字典，取 key 构建出 redis 的string类型的键 = reg:serv:key，
            //    以 value 作为 redis 的值，写入 redis，有效时间 86400 秒
//            $redis = Redis::connection();
//            $writtenKeys = [];
//
//            foreach ($serviceDictionary as $serviceName => $value) {
//                $redisKey = 'reg:serv:' . $serviceName;
//                $redis->setex($redisKey, 86400, $value);
//                $writtenKeys[] = $redisKey;
//            }

            return response()->json(ApiResponse::ok(
                [
                    'containers_processed' => count($containerAppMap),
                    'services_registered' => count($serviceDictionary),
//                    'redis_keys_written' => $writtenKeys,
                ]
            ));

        } catch (\Exception $e) {
            Log::error('Service discovery failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Service discovery failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

