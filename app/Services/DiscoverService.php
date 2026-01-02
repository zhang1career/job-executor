<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Paganini\Exceptions\UnsupportedOperationException;

class DiscoverService
{
    private ContainerLabelService $containerLabelService;

    public function __construct(ContainerLabelService $containerLabelService)
    {
        $this->containerLabelService = $containerLabelService;
    }

    /**
     * @throws UnsupportedOperationException
     */
    public function discover(): array
    {
        // 1. 查询同一个容器组中全部容器的标签
        $containers = $this->containerLabelService->getContainersInGroup();

        // 2. 取标签 appmap 的值，以及容器名称，构建 [容器名称：appmap值] 的字典
        $containerAppMap = $this->filterAppMap($containers);

        // 3. 解析 appmap 值
        $serviceMap = $this->parseServiceMap($containerAppMap);

        // 4. 将步骤3中的组合，组成新的字典
        $serviceDict = $this->buildServiceDict($serviceMap);

        // 5. 写入 redis
        $writtenKeys = $this->writeToRedis($serviceDict);

        return [
            'containers_processed' => count($containerAppMap),
            'services_registered' => count($serviceDict),
            'redis_keys_written' => $writtenKeys,
        ];
    }

    /**
     * @param array $containers
     * @return array
     */
    private function filterAppMap(array $containers): array
    {
        $containerAppMap = [];
        foreach ($containers as $container) {
            $containerName = $container['name'];
            $appmap = $container['appmap'] ?? null;
            if (!$appmap) {
                continue;
            }
            $containerAppMap[$containerName] = $appmap;
        }
        return $containerAppMap;
    }

    /**
     * 解析得到：容器名称 - 服务名称 - 容器端口号的组合
     *
     * appmap是字符串，由逗号（,）分隔出小单元；
     * 每一个单元中是冒号（:）分割的 key-value 对。其中 key 是服务名称，value是容器端口号
     *
     * @param array $containerAppMap
     * @return array
     */
    private function parseServiceMap(array $containerAppMap): array
    {
        $serviceMappings = [];
        foreach ($containerAppMap as $containerName => $appMapStr) {
            // 按逗号分隔小单元
            $appMaps = explode(',', $appMapStr);

            foreach ($appMaps as $_appMap) {
                $_appMap = trim($_appMap);
                if (empty($_appMap)) {
                    continue;
                }

                // 按冒号分割 key-value
                $_appMapEntry = explode(':', $_appMap);
                if (count($_appMapEntry) < 2) {
                    continue;
                }
                $serviceName = trim($_appMapEntry[0]);
                $port = trim($_appMapEntry[1]);
                if (empty($serviceName) || empty($port)) {
                    continue;
                }
                $serviceMappings[] = [
                    'container' => $containerName,
                    'service' => $serviceName,
                    'port' => $port,
                ];
            }
        }
        return $serviceMappings;
    }

    /**
     * 以服务名称为 key，容器名称:容器端口为value（如果有多个，按逗号分隔）
     *
     * @param array $serviceMappings
     * @return array
     */
    private function buildServiceDict(array $serviceMappings): array
    {
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
        return $serviceDictionary;
    }

    /**
     * 写入 redis，有效期1天
     *
     * 以 key 构建出 redis 的键，key = reg:serv:key，
     * 以 value 作为 redis 的值
     *
     * @param array $serviceDict
     * @return array
     */
    private function writeToRedis(array $serviceDict): array
    {
        $redis = Redis::connection();
        $writtenKeys = [];

        foreach ($serviceDict as $serviceName => $value) {
            $redisKey = 'reg:serv:' . $serviceName;
            $redis->setex($redisKey, 86400, $value);
            $writtenKeys[] = $redisKey;
        }
        return $writtenKeys;
    }
}
