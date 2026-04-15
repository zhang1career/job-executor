<?php

namespace App\Services\ContainerLabel;

use Exception;
use Illuminate\Support\Facades\Log;
use Paganini\Exceptions\UnsupportedOperationException;

trait DockerContainerLabels
{
    /**
     * Check if running in Docker environment
     */
    protected function isDockerEnvironment(): bool
    {
        $dockerHost = env('DOCKER_HOST');
        if ($dockerHost && str_starts_with($dockerHost, 'unix://')) {
            $socketPath = str_replace('unix://', '', $dockerHost);
            if (file_exists($socketPath)) {
                return true;
            }
        }
        if (file_exists('/var/run/docker.sock')) {
            return true;
        }

        return false;
    }

    /**
     * Get container information from Docker API
     */
    protected function getContainersFromDocker(): array
    {
        $dockerHost = env('DOCKER_HOST', '/var/run/docker.sock');
        $dockerApiUrl = env('DOCKER_API_URL');

        try {
            if ($dockerApiUrl) {
                return $this->getContainersFromDockerHttp($dockerApiUrl);
            }

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
     * @throws UnsupportedOperationException
     */
    protected function getContainersFromDockerHttp(string $apiUrl): array
    {
        throw new UnsupportedOperationException('HTTP API method is not yet implemented.');
    }

    /**
     * Get Docker containers via Unix Socket
     *
     * @throws Exception
     */
    protected function getContainersFromDockerSocket(string $socketPath): array
    {
        if (str_starts_with($socketPath, 'unix://')) {
            $socketPath = str_replace('unix://', '', $socketPath);
        }

        if (is_link($socketPath)) {
            $realPath = readlink($socketPath);
            Log::debug('Docker socket is a symlink', [
                'symlink' => $socketPath,
                'target' => $realPath,
            ]);

            if (! str_starts_with($realPath, '/')) {
                $realPath = dirname($socketPath).'/'.$realPath;
            }

            if (file_exists($realPath)) {
                $socketPath = $realPath;
                Log::debug('Using resolved socket path', ['path' => $socketPath]);
            } else {
                Log::warning('Symlink target does not exist', [
                    'symlink' => $socketPath,
                    'target' => $realPath,
                ]);
            }
        }

        if (! file_exists($socketPath)) {
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
            if (! empty($foundPaths)) {
                $message .= '. Found possible sockets at: '.implode(', ', $foundPaths);
            }
            $message .= '. Please ensure Docker socket is mounted into the container.';

            throw new Exception($message);
        }

        $fileType = filetype($socketPath);
        $filePerms = substr(sprintf('%o', fileperms($socketPath)), -4);
        $isLink = is_link($socketPath);

        $fileOwner = 'unknown';
        $fileGroup = 'unknown';
        $currentUid = function_exists('posix_geteuid') ? posix_geteuid() : null;
        $currentGid = function_exists('posix_getegid') ? posix_getegid() : null;

        if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
            $ownerId = fileowner($socketPath);
            $groupId = filegroup($socketPath);
            $ownerInfo = posix_getpwuid($ownerId);
            $groupInfo = posix_getgrgid($groupId);
            $fileOwner = ($ownerInfo['name'] ?? 'unknown')." (uid: $ownerId)";
            $fileGroup = ($groupInfo['name'] ?? 'unknown')." (gid: $groupId)";
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

        $actualPath = $isLink ? (realpath($socketPath) ?: $socketPath) : $socketPath;
        if (file_exists($actualPath)) {
            $actualType = filetype($actualPath);
            if ($actualType !== 'socket' && $fileType !== 'socket') {
                throw new Exception("File exists but is not a socket: $socketPath (type: $fileType, actual: $actualType)");
            }
        } elseif ($fileType !== 'socket') {
            throw new Exception("File exists but is not a socket: $socketPath (type: $fileType)");
        }

        if (! is_readable($socketPath)) {
            $suggestion = 'Try running container with: docker run -v /var/run/docker.sock:/var/run/docker.sock ...';
            throw new Exception("Docker socket not readable: $socketPath (perms: $filePerms, owner: $fileOwner, current uid: $currentUid). $suggestion");
        }

        if (! defined('CURLOPT_UNIX_SOCKET_PATH')) {
            throw new Exception('CURLOPT_UNIX_SOCKET_PATH is not supported. Requires PHP 7.0.7+ and curl 7.40+');
        }

        $curlVersion = curl_version();
        Log::debug('cURL version info', [
            'version' => $curlVersion['version'] ?? 'unknown',
            'ssl_version' => $curlVersion['ssl_version'] ?? 'unknown',
            'features' => $curlVersion['features'] ?? 0,
        ]);

        $ch = curl_init();

        $socketSet = curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
        if (! $socketSet) {
            curl_close($ch);
            throw new Exception("Failed to set CURLOPT_UNIX_SOCKET_PATH: $socketPath");
        }

        curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/json?all=1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);

        $response = curl_exec($ch);

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

        Log::debug('docker socket request', [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrno,
            'response_length' => strlen($response ?? ''),
            'socket_path' => $socketPath,
            'effective_url' => $effectiveUrl,
        ]);

        if ($httpCode !== 200 || ! $response) {
            throw new Exception("Failed to fetch containers from Docker socket. HTTP Code: $httpCode, Error: $curlError, Errno: $curlErrno");
        }

        $containers = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse Docker API response: '.json_last_error_msg());
        }

        $result = [];
        $currentProject = $this->getCurrentDockerProject();

        foreach ($containers as $container) {
            $containerId = $container['Id'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
            curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/'.$containerId.'/json');
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
            if ($detailHttpCode !== 200 || ! $detailResponse) {
                Log::warning('Non-200 response fetching container detail', [
                    'container_id' => $containerId,
                    'http_code' => $detailHttpCode,
                ]);

                continue;
            }
            $containerDetail = json_decode($detailResponse, true);
            if (! $containerDetail) {
                Log::warning('Failed to parse container detail JSON', [
                    'container_id' => $containerId,
                ]);

                continue;
            }

            $labels = $containerDetail['Config']['Labels'] ?? [];
            $containerProject = $labels['com.docker.compose.project'] ?? null;

            if (! $currentProject || $currentProject !== $containerProject) {
                continue;
            }

            $serviceName = $labels['com.docker.compose.service'] ?? '';
            $appmap = $labels['appmap'] ?? '';
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
     * @throws Exception
     */
    protected function getCurrentDockerProject(): ?string
    {
        $project = env('DOCKER_CONTAINER_GROUP_NAME');
        if (! $project) {
            throw new Exception('Please set DOCKER_CONTAINER_GROUP_NAME environment variable to the current Docker Compose project name.');
        }

        return $project;
    }
}
