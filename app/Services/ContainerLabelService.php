<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ContainerLabelService
{
    /**
     * 获取同一容器组中的所有容器及其标签
     * 
     * 支持通过 Docker API 或 Kubernetes API 查询
     * 
     * @return array
     */
    public function getContainersInGroup(): array
    {
        // 优先尝试 Docker API
        if ($this->isDockerEnvironment()) {
            return $this->getContainersFromDocker();
        }
        
        // 尝试 Kubernetes API
        if ($this->isKubernetesEnvironment()) {
            return $this->getContainersFromKubernetes();
        }
        
        // 如果都不支持，返回空数组或抛出异常
        Log::warning('Neither Docker nor Kubernetes environment detected');
        return [];
    }
    
    /**
     * 检查是否为 Docker 环境
     * 
     * @return bool
     */
    protected function isDockerEnvironment(): bool
    {
        // 检查是否存在 Docker socket 或 Docker API 端点
        $dockerHost = env('DOCKER_HOST', '/var/run/docker.sock');
        $dockerApiUrl = env('DOCKER_API_URL', 'http://localhost:2375');
        
        // 检查 socket 文件是否存在
        if (file_exists($dockerHost)) {
            return true;
        }
        
        // 检查 API URL 是否可访问
        try {
            $response = Http::timeout(2)->get($dockerApiUrl . '/version');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 检查是否为 Kubernetes 环境
     * 
     * @return bool
     */
    protected function isKubernetesEnvironment(): bool
    {
        // 检查是否存在 Kubernetes 服务账号 token
        $k8sTokenPath = '/var/run/secrets/kubernetes.io/serviceaccount/token';
        $k8sNamespacePath = '/var/run/secrets/kubernetes.io/serviceaccount/namespace';
        
        return file_exists($k8sTokenPath) && file_exists($k8sNamespacePath);
    }
    
    /**
     * 从 Docker API 获取容器信息
     * 
     * @return array
     */
    protected function getContainersFromDocker(): array
    {
        $dockerHost = env('DOCKER_HOST', '/var/run/docker.sock');
        $dockerApiUrl = env('DOCKER_API_URL');
        
        try {
            // 如果提供了 Docker API URL，使用 HTTP API
            if ($dockerApiUrl) {
                return $this->getContainersFromDockerHttp($dockerApiUrl);
            }
            
            // 否则使用 Unix socket（需要安装 docker-php 库或使用 curl）
            return $this->getContainersFromDockerSocket($dockerHost);
            
        } catch (\Exception $e) {
            Log::error('Failed to get containers from Docker', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    /**
     * 通过 HTTP API 获取 Docker 容器
     * 
     * @param string $apiUrl
     * @return array
     */
    protected function getContainersFromDockerHttp(string $apiUrl): array
    {
        // 获取当前容器的项目名称（如果是 Docker Compose）
        $currentProject = $this->getCurrentDockerProject();
        
        // 构建过滤器
        $filters = [];
        if ($currentProject) {
            $filters['label'] = ['com.docker.compose.project=' . $currentProject];
        }
        
        $queryParams = ['all' => 'true'];
        if (!empty($filters)) {
            $queryParams['filters'] = json_encode($filters);
        }
        
        $response = Http::get($apiUrl . '/containers/json', $queryParams);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch containers from Docker API');
        }
        
        $containers = $response->json();
        $result = [];
        
        foreach ($containers as $container) {
            // 获取容器详细信息以获取标签
            $containerId = $container['Id'];
            $detailResponse = Http::get($apiUrl . '/containers/' . $containerId . '/json');
            
            if ($detailResponse->successful()) {
                $containerDetail = $detailResponse->json();
                $containerName = $container['Names'][0] ?? $container['Id'];
                // 移除前导斜杠
                $containerName = ltrim($containerName, '/');
                
                $result[] = [
                    'name' => $containerName,
                    'labels' => $containerDetail['Config']['Labels'] ?? [],
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 通过 Unix Socket 获取 Docker 容器
     * 
     * @param string $socketPath
     * @return array
     */
    protected function getContainersFromDockerSocket(string $socketPath): array
    {
        // 使用 curl 通过 Unix socket 访问 Docker API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/json?all=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            throw new \Exception('Failed to fetch containers from Docker socket');
        }
        
        $containers = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse Docker API response');
        }
        
        $result = [];
        $currentContainerName = env('HOSTNAME', gethostname());
        
        // 获取当前容器的项目名称（如果是 Docker Compose）
        $currentProject = $this->getCurrentDockerProject();
        
        foreach ($containers as $container) {
            // 获取容器详细信息
            $containerId = $container['Id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/' . $containerId . '/json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $detailResponse = curl_exec($ch);
            $detailHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($detailHttpCode === 200 && $detailResponse) {
                $containerDetail = json_decode($detailResponse, true);
                if ($containerDetail) {
                    $labels = $containerDetail['Config']['Labels'] ?? [];
                    $containerProject = $labels['com.docker.compose.project'] ?? null;
                    
                    // 只返回同一项目组的容器
                    if ($currentProject && $containerProject === $currentProject) {
                        $containerName = $container['Names'][0] ?? $container['Id'];
                        // 移除前导斜杠
                        $containerName = ltrim($containerName, '/');
                        
                        $result[] = [
                            'name' => $containerName,
                            'labels' => $labels,
                        ];
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 获取当前 Docker Compose 项目名称
     * 
     * @return string|null
     */
    protected function getCurrentDockerProject(): ?string
    {
        // 尝试从环境变量获取
        $project = env('COMPOSE_PROJECT_NAME');
        if ($project) {
            return $project;
        }
        
        // 尝试从容器名称推断（Docker Compose 通常使用项目名作为前缀）
        $hostname = env('HOSTNAME', gethostname());
        if ($hostname) {
            // Docker Compose 容器名称格式通常是: project_service_number
            // 尝试提取项目名称
            $parts = explode('_', $hostname);
            if (count($parts) >= 2) {
                // 返回第一部分作为项目名（可能需要进一步验证）
                return $parts[0];
            }
        }
        
        // 如果无法确定项目名称，返回 null，将返回所有容器
        // 在实际使用中，建议设置 COMPOSE_PROJECT_NAME 环境变量
        return null;
    }
    
    /**
     * 从 Kubernetes API 获取容器信息
     * 
     * @return array
     */
    protected function getContainersFromKubernetes(): array
    {
        $token = file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/token');
        $namespace = file_get_contents('/var/run/secrets/kubernetes.io/serviceaccount/namespace');
        $apiServer = env('KUBERNETES_SERVICE_HOST', 'kubernetes.default.svc');
        $apiPort = env('KUBERNETES_SERVICE_PORT', '443');
        
        $apiUrl = 'https://' . $apiServer . ':' . $apiPort;
        
        try {
            // 获取当前 Pod 的标签，确定选择器
            $currentPodName = env('HOSTNAME', gethostname());
            $podResponse = Http::withToken($token)
                ->withoutVerifying() // Kubernetes 使用自签名证书
                ->get($apiUrl . '/api/v1/namespaces/' . $namespace . '/pods/' . $currentPodName);
            
            if (!$podResponse->successful()) {
                throw new \Exception('Failed to get current pod information');
            }
            
            $currentPod = $podResponse->json();
            $labels = $currentPod['metadata']['labels'] ?? [];
            
            // 使用标签选择器获取同一组的所有 Pod
            $labelSelector = [];
            foreach ($labels as $key => $value) {
                if (!in_array($key, ['pod-template-hash', 'controller-revision-hash'])) {
                    $labelSelector[] = $key . '=' . $value;
                }
            }
            
            $selector = implode(',', $labelSelector);
            
            $podsResponse = Http::withToken($token)
                ->withoutVerifying()
                ->get($apiUrl . '/api/v1/namespaces/' . $namespace . '/pods', [
                    'labelSelector' => $selector,
                ]);
            
            if (!$podsResponse->successful()) {
                throw new \Exception('Failed to get pods from Kubernetes API');
            }
            
            $pods = $podsResponse->json()['items'] ?? [];
            $result = [];
            
            foreach ($pods as $pod) {
                $podName = $pod['metadata']['name'];
                $podLabels = $pod['metadata']['labels'] ?? [];
                
                $result[] = [
                    'name' => $podName,
                    'labels' => $podLabels,
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to get containers from Kubernetes', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

