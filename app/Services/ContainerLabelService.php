<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Paganini\Exceptions\UnsupportedOperationException;

class ContainerLabelService
{
    /**
     * 获取同一容器组中的所有容器及其标签
     *
     * 支持通过 Docker API 或 Kubernetes API 查询
     *
     * @return array
     * @throws UnsupportedOperationException
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
        Log::error('Neither Docker nor Kubernetes environment detected');
        return [];
    }

    /**
     * 检查是否为 Docker 环境
     *
     * @return bool
     */
    protected function isDockerEnvironment(): bool
    {
        // 优先检查 Unix socket
        $dockerHost = env('DOCKER_HOST');
        if ($dockerHost && str_starts_with($dockerHost, 'unix://')) {
            $socketPath = str_replace('unix://', '', $dockerHost);
            if (file_exists($socketPath)) {
                return true;
            }
        }
        // 默认检查标准 socket 路径
        if (file_exists('/var/run/docker.sock')) {
            return true;
        }

        return false;
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

        } catch (Exception $e) {
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
     * @throws UnsupportedOperationException
     */
    protected function getContainersFromDockerHttp(string $apiUrl): array
    {
        throw new UnsupportedOperationException('HTTP API method is not yet implemented.');
    }

    /**
     * 通过 Unix Socket 获取 Docker 容器
     *
     * @param string $socketPath
     * @return array
     * @throws Exception
     */
    protected function getContainersFromDockerSocket(string $socketPath): array
    {
        // 清理 socket 路径（移除 unix:// 前缀）
        if (str_starts_with($socketPath, 'unix://')) {
            $socketPath = str_replace('unix://', '', $socketPath);
        }

        // 处理符号链接：如果是符号链接，尝试解析实际路径
        if (is_link($socketPath)) {
            $realPath = readlink($socketPath);
            Log::debug('Docker socket is a symlink', [
                'symlink' => $socketPath,
                'target' => $realPath,
            ]);

            // 如果是绝对路径，使用它；否则相对于符号链接的目录
            if (!str_starts_with($realPath, '/')) {
                $realPath = dirname($socketPath) . '/' . $realPath;
            }

            // 检查目标文件是否存在
            if (file_exists($realPath)) {
                $socketPath = $realPath;
                Log::debug('Using resolved socket path', ['path' => $socketPath]);
            } else {
                Log::warning('Symlink target does not exist', [
                    'symlink' => $socketPath,
                    'target' => $realPath,
                ]);
                // 继续使用符号链接路径，curl 可能会处理
            }
        }

        // 验证 socket 路径
        if (!file_exists($socketPath)) {
            // 提供更多诊断信息
            $possiblePaths = [
                '/var/run/docker.sock',
                '/tmp/docker.sock',
                '/run/docker.sock',
            ];

            $foundPaths = [];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $foundPaths[] = $path;
                }
            }

            $message = "Docker socket not found: $socketPath";
            if (!empty($foundPaths)) {
                $message .= ". Found possible sockets at: " . implode(', ', $foundPaths);
            }
            $message .= ". Please ensure Docker socket is mounted into the container.";

            throw new Exception($message);
        }

        // 检查文件类型（应该是 socket）
        $fileType = filetype($socketPath);
        $filePerms = substr(sprintf('%o', fileperms($socketPath)), -4);
        $isLink = is_link($socketPath);

        // 获取文件所有者和组（如果 posix 扩展可用）
        $fileOwner = 'unknown';
        $fileGroup = 'unknown';
        $currentUid = function_exists('posix_geteuid') ? posix_geteuid() : null;
        $currentGid = function_exists('posix_getegid') ? posix_getegid() : null;

        if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
            $ownerId = fileowner($socketPath);
            $groupId = filegroup($socketPath);
            $ownerInfo = posix_getpwuid($ownerId);
            $groupInfo = posix_getgrgid($groupId);
            $fileOwner = ($ownerInfo['name'] ?? 'unknown') . " (uid: $ownerId)";
            $fileGroup = ($groupInfo['name'] ?? 'unknown') . " (gid: $groupId)";
        }

        Log::debug('docker socket file info', [
            'path' => $socketPath,
            'type' => $fileType,
            'is_link' => $isLink,
            'perms' => $filePerms,
            'owner' => $fileOwner,
            'group' => $fileGroup,
            'current_uid' => $currentUid,
            'current_gid' => $currentGid,
            'readable' => is_readable($socketPath),
            'writable' => is_writable($socketPath),
            'is_executable' => is_executable($socketPath),
        ]);

        // 对于符号链接，需要检查目标文件的类型
        $actualPath = $isLink ? (realpath($socketPath) ?: $socketPath) : $socketPath;
        if (file_exists($actualPath)) {
            $actualType = filetype($actualPath);
            if ($actualType !== 'socket' && $fileType !== 'socket') {
                throw new Exception("File exists but is not a socket: $socketPath (type: $fileType, actual: $actualType)");
            }
        } elseif ($fileType !== 'socket') {
            throw new Exception("File exists but is not a socket: $socketPath (type: $fileType)");
        }

        if (!is_readable($socketPath)) {
            $suggestion = "Try running container with: docker run -v /var/run/docker.sock:/var/run/docker.sock ...";
            throw new Exception("Docker socket not readable: $socketPath (perms: $filePerms, owner: $fileOwner, current uid: $currentUid). $suggestion");
        }

        // 检查是否支持 Unix socket
        if (!defined('CURLOPT_UNIX_SOCKET_PATH')) {
            throw new Exception('CURLOPT_UNIX_SOCKET_PATH is not supported. Requires PHP 7.0.7+ and curl 7.40+');
        }

        // 检查 curl 版本信息
        $curlVersion = curl_version();
        Log::debug('cURL version info', [
            'version' => $curlVersion['version'] ?? 'unknown',
            'ssl_version' => $curlVersion['ssl_version'] ?? 'unknown',
            'features' => $curlVersion['features'] ?? 0,
        ]);

        // 使用 curl 通过 Unix socket 访问 Docker API
        $ch = curl_init();

        // 设置 Unix socket 路径
        $socketSet = curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
        if (!$socketSet) {
            curl_close($ch);
            throw new Exception("Failed to set CURLOPT_UNIX_SOCKET_PATH: $socketPath");
        }

        // 使用 Docker API v1.41（或让 Docker 自动选择版本）
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/json?all=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, false); // 允许返回错误响应

        $response = curl_exec($ch);

        // 检查 curl_exec 是否成功
        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);
            Log::error('curl_exec failed for Docker socket', [
                'socket_path' => $socketPath,
                'curl_errno' => $errno,
                'curl_error' => $error,
                'http_code' => $httpCode,
                'effective_url' => $effectiveUrl,
                'curl_info' => $curlInfo,
                'php_version' => PHP_VERSION,
                'curl_version' => $curlVersion['version'] ?? 'unknown',
            ]);
            throw new Exception("curl_exec failed: [$errno] $error. Socket: $socketPath, HTTP Code: $httpCode");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // 记录调试信息
        Log::debug('docker socket request', [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrno,
            'response_length' => strlen($response ?? ''),
            'socket_path' => $socketPath,
            'effective_url' => $effectiveUrl,
        ]);

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Failed to fetch containers from Docker socket. HTTP Code: $httpCode, Error: $curlError, Errno: $curlErrno");
        }

        $containers = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse Docker API response: ' . json_last_error_msg());
        }

        $result = [];
        $currentProject = $this->getCurrentDockerProject();

        foreach ($containers as $container) {
            // 获取容器详细信息
            $containerId = $container['Id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/' . $containerId . '/json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $detailResponse = curl_exec($ch);
            if ($detailResponse === false) {
                $detailError = curl_error($ch);
                $detailErrno = curl_errno($ch);
                curl_close($ch);
                Log::warning('Failed to fetch container detail', [
                    'container_id' => $containerId,
                    'error' => $detailError,
                    'errno' => $detailErrno,
                ]);
                continue;
            }

            $detailHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($detailHttpCode !== 200 || !$detailResponse) {
                Log::warning('Non-200 response fetching container detail', [
                    'container_id' => $containerId,
                    'http_code' => $detailHttpCode,
                ]);
                continue;
            }
            $containerDetail = json_decode($detailResponse, true);
            if (!$containerDetail) {
                Log::warning('Failed to parse container detail JSON', [
                    'container_id' => $containerId,
                ]);
                continue;
            }

            $labels = $containerDetail['Config']['Labels'] ?? [];
            $containerProject = $labels['com.docker.compose.project'] ?? null;

            // 只返回同一项目组的容器
            if (!$currentProject || $currentProject !== $containerProject) {
                continue;
            }

            $serviceName = $labels['com.docker.compose.service'] ?? "";
            $appmap = $labels['appmap'] ?? "";
            if (empty($serviceName) || empty($appmap)) {
                Log::debug('skip container without service label or appmap label', [
                    'container_id' => $containerId,
                    'container_name' => $container['Names'][0] ?? '',
                ]);
                continue;
            }

            if ($serviceName === 'sidecar') {
                Log::debug('Skipping sidecar container', [
                    'container_id' => $containerId,
                    'container_name' => $container['Names'][0] ?? '',
                ]);
                continue;
            }

            $result[] = [
                'name' => $serviceName,
                'appmap' => $appmap,
            ];
        }

        return $result;
    }

    /**
     * 获取当前 Docker Compose 项目名称
     *
     * @return string|null
     * @throws Exception
     */
    protected function getCurrentDockerProject(): ?string
    {
        // 尝试从环境变量获取
        $project = env('DOCKER_CONTAINER_GROUP_NAME');
        if (!$project) {
            throw new Exception('Please set DOCKER_CONTAINER_GROUP_NAME environment variable to the current Docker Compose project name.');
        }
        return $project;
    }

    /**
     * 从 Kubernetes API 获取容器信息
     *
     * @return array
     * @throws UnsupportedOperationException
     */
    protected function getContainersFromKubernetes(): array
    {
        throw new UnsupportedOperationException('Kubernetes environment is not yet supported.');
    }
}

