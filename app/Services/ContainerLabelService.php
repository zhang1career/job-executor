<?php

namespace App\Services;

use App\Services\ContainerLabel\AliyunEcsContainerLabels;
use App\Services\ContainerLabel\DockerContainerLabels;
use App\Services\ContainerLabel\KubernetesContainerLabels;
use Illuminate\Support\Facades\Log;
use Paganini\Exceptions\UnsupportedOperationException;

class ContainerLabelService
{
    use AliyunEcsContainerLabels;
    use DockerContainerLabels;
    use KubernetesContainerLabels;

    /**
     * Get peers in the same group: Docker Compose services, Kubernetes (when supported), or Aliyun ECS in the configured VPC CIDR.
     *
     * Each item has name and appmap keys for DiscoverService.
     *
     * @return array<int, array{name: string, appmap: string}>
     *
     * @throws UnsupportedOperationException
     */
    public function getContainersInGroup(): array
    {
        if ($this->isDockerEnvironment()) {
            return $this->getContainersFromDocker();
        }

        if ($this->isKubernetesEnvironment()) {
            return $this->getContainersFromKubernetes();
        }

        if ($this->isAliyunEnvironment()) {
            return $this->getAliyunEcsMachinesInVpcCidr();
        }

        Log::error('Neither Docker, Kubernetes, nor Aliyun (ALIYUN_VPC_CIDR + credentials) environment detected');

        return [];
    }
}
