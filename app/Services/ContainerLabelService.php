<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Paganini\Exceptions\UnsupportedOperationException;

class ContainerLabelService
{
    /**
     * Get all containers and their labels in the same container group
     *
     * Supports querying via Docker API or Kubernetes API
     *
     * @return array
     * @throws UnsupportedOperationException
     */
    public function getContainersInGroup(): array
    {
        // Try Docker API first
        if ($this->isDockerEnvironment()) {
            return $this->getContainersFromDocker();
        }

        // Try Kubernetes API
        if ($this->isKubernetesEnvironment()) {
            return $this->getContainersFromKubernetes();
        }

        // If neither is supported, return empty array or throw exception
        Log::error('Neither Docker nor Kubernetes environment detected');
        return [];
    }

    /**
     * Check if running in Docker environment
     *
     * @return bool
     */
    protected function isDockerEnvironment(): bool
    {
        // Check Unix socket first
        $dockerHost = env('DOCKER_HOST');
        if ($dockerHost && str_starts_with($dockerHost, 'unix://')) {
            $socketPath = str_replace('unix://', '', $dockerHost);
            if (file_exists($socketPath)) {
                return true;
            }
        }
        // Check default standard socket path
        if (file_exists('/var/run/docker.sock')) {
            return true;
        }

        return false;
    }

    /**
     * Check if running in Kubernetes environment
     *
     * @return bool
     */
    protected function isKubernetesEnvironment(): bool
    {
        // Check if Kubernetes service account token exists
        $k8sTokenPath = '/var/run/secrets/kubernetes.io/serviceaccount/token';
        $k8sNamespacePath = '/var/run/secrets/kubernetes.io/serviceaccount/namespace';

        return file_exists($k8sTokenPath) && file_exists($k8sNamespacePath);
    }

    /**
     * Get container information from Docker API
     *
     * @return array
     */
    protected function getContainersFromDocker(): array
    {
        $dockerHost = env('DOCKER_HOST', '/var/run/docker.sock');
        $dockerApiUrl = env('DOCKER_API_URL');

        try {
            // If Docker API URL is provided, use HTTP API
            if ($dockerApiUrl) {
                return $this->getContainersFromDockerHttp($dockerApiUrl);
            }
            // Otherwise use Unix socket (requires docker-php library or curl)
            return $this->getContainersFromDockerSocket($dockerHost);

        } catch (Exception $e) {
            Log::error('Failed to get containers from Docker', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get Docker containers via HTTP API
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
     * Get Docker containers via Unix Socket
     *
     * @param string $socketPath
     * @return array
     * @throws Exception
     */
    protected function getContainersFromDockerSocket(string $socketPath): array
    {
        // Clean socket path (remove unix:// prefix)
        if (str_starts_with($socketPath, 'unix://')) {
            $socketPath = str_replace('unix://', '', $socketPath);
        }

        // Handle symlinks: if it's a symlink, try to resolve the actual path
        if (is_link($socketPath)) {
            $realPath = readlink($socketPath);
            Log::debug('Docker socket is a symlink', [
                'symlink' => $socketPath,
                'target' => $realPath,
            ]);

            // If it's an absolute path, use it; otherwise relative to the symlink's directory
            if (!str_starts_with($realPath, '/')) {
                $realPath = dirname($socketPath) . '/' . $realPath;
            }

            // Check if target file exists
            if (file_exists($realPath)) {
                $socketPath = $realPath;
                Log::debug('Using resolved socket path', ['path' => $socketPath]);
            } else {
                Log::warning('Symlink target does not exist', [
                    'symlink' => $socketPath,
                    'target' => $realPath,
                ]);
                // Continue using symlink path, curl might handle it
            }
        }

        // Validate socket path
        if (!file_exists($socketPath)) {
            // Provide more diagnostic information
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

        // Check file type (should be socket)
        $fileType = filetype($socketPath);
        $filePerms = substr(sprintf('%o', fileperms($socketPath)), -4);
        $isLink = is_link($socketPath);

        // Get file owner and group (if posix extension is available)
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

        // For symlinks, need to check the target file's type
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

        // Check if Unix socket is supported
        if (!defined('CURLOPT_UNIX_SOCKET_PATH')) {
            throw new Exception('CURLOPT_UNIX_SOCKET_PATH is not supported. Requires PHP 7.0.7+ and curl 7.40+');
        }

        // Check curl version information
        $curlVersion = curl_version();
        Log::debug('cURL version info', [
            'version' => $curlVersion['version'] ?? 'unknown',
            'ssl_version' => $curlVersion['ssl_version'] ?? 'unknown',
            'features' => $curlVersion['features'] ?? 0,
        ]);

        // Use curl to access Docker API via Unix socket
        $ch = curl_init();

        // Set Unix socket path
        $socketSet = curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
        if (!$socketSet) {
            curl_close($ch);
            throw new Exception("Failed to set CURLOPT_UNIX_SOCKET_PATH: $socketPath");
        }

        // Use Docker API v1.41 (or let Docker auto-select version)
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/json?all=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, false); // Allow error responses

        $response = curl_exec($ch);

        // Check if curl_exec succeeded
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

        // Log debug information
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
            // Get container detailed information
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

            // Only return containers from the same project group
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
     * Get current Docker Compose project name
     *
     * @return string|null
     * @throws Exception
     */
    protected function getCurrentDockerProject(): ?string
    {
        // Try to get from environment variable
        $project = env('DOCKER_CONTAINER_GROUP_NAME');
        if (!$project) {
            throw new Exception('Please set DOCKER_CONTAINER_GROUP_NAME environment variable to the current Docker Compose project name.');
        }
        return $project;
    }

    /**
     * Get container information from Kubernetes API
     *
     * @return array
     * @throws UnsupportedOperationException
     */
    protected function getContainersFromKubernetes(): array
    {
        throw new UnsupportedOperationException('Kubernetes environment is not yet supported.');
    }
}

