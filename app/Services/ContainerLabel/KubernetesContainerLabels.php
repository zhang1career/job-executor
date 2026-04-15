<?php

namespace App\Services\ContainerLabel;

use Paganini\Exceptions\UnsupportedOperationException;

trait KubernetesContainerLabels
{
    /**
     * Check if running in Kubernetes environment
     */
    protected function isKubernetesEnvironment(): bool
    {
        $k8sTokenPath = '/var/run/secrets/kubernetes.io/serviceaccount/token';
        $k8sNamespacePath = '/var/run/secrets/kubernetes.io/serviceaccount/namespace';

        return file_exists($k8sTokenPath) && file_exists($k8sNamespacePath);
    }

    /**
     * Get container information from Kubernetes API
     *
     * @throws UnsupportedOperationException
     */
    protected function getContainersFromKubernetes(): array
    {
        throw new UnsupportedOperationException('Kubernetes environment is not yet supported.');
    }
}
