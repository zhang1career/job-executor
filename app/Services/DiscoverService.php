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
        // 1. Query all container labels in the same container group
        $containers = $this->containerLabelService->getContainersInGroup();

        // 2. Extract appmap label values and container names, build dictionary [container_name: appmap_value]
        $containerAppMap = $this->filterAppMap($containers);

        // 3. Parse appmap values
        $serviceMap = $this->parseServiceMap($containerAppMap);

        // 4. Combine results from step 3 into a new dictionary
        $serviceDict = $this->buildServiceDict($serviceMap);

        // 5. Write to Redis
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
     * Parse to get combinations of: container name - service name - container port
     *
     * appmap is a string, with comma (,) separating units;
     * each unit contains colon (:) separated key-value pairs. Key is service name, value is container port
     *
     * @param array $containerAppMap
     * @return array
     */
    private function parseServiceMap(array $containerAppMap): array
    {
        $serviceMappings = [];
        foreach ($containerAppMap as $containerName => $appMapStr) {
            // Split by comma into units
            $appMaps = explode(',', $appMapStr);

            foreach ($appMaps as $_appMap) {
                $_appMap = trim($_appMap);
                if (empty($_appMap)) {
                    continue;
                }

                // Split by colon into key-value
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
     * Use service name as key, container_name:container_port as value (if multiple, separated by comma)
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
        // Convert array to comma-separated string
        foreach ($serviceDictionary as $serviceName => $values) {
            $serviceDictionary[$serviceName] = implode(',', $values);
        }
        return $serviceDictionary;
    }

    /**
     * Write to Redis with 1 day expiration
     *
     * Build Redis key from service name: key = reg:serv:service_name,
     * use value as Redis value
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
