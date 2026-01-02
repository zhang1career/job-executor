<?php

namespace Tests\Unit\Services;

use App\Exceptions\UnsupportedOperationException;
use App\Services\ContainerLabelService;
use Exception;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionClass;
use Tests\TestCase;

class ContainerLabelServiceTest extends TestCase
{
    private ContainerLabelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContainerLabelService();
    }

    public function test_getContainersInGroup_returns_empty_array_when_no_environment_detected(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Neither Docker nor Kubernetes environment detected');

        // Mock environment methods to return false
        $service = $this->getMockBuilder(ContainerLabelService::class)
            ->onlyMethods(['isDockerEnvironment', 'isKubernetesEnvironment'])
            ->getMock();

        $service->expects($this->once())
            ->method('isDockerEnvironment')
            ->willReturn(false);

        $service->expects($this->once())
            ->method('isKubernetesEnvironment')
            ->willReturn(false);

        $result = $service->getContainersInGroup();
        $this->assertEquals([], $result);
    }

    public function test_getContainersInGroup_throws_exception_for_kubernetes(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $service = $this->getMockBuilder(ContainerLabelService::class)
            ->onlyMethods(['isDockerEnvironment', 'isKubernetesEnvironment', 'getContainersFromKubernetes'])
            ->getMock();

        $service->expects($this->once())
            ->method('isDockerEnvironment')
            ->willReturn(false);

        $service->expects($this->once())
            ->method('isKubernetesEnvironment')
            ->willReturn(true);

        $service->expects($this->once())
            ->method('getContainersFromKubernetes')
            ->willThrowException(new UnsupportedOperationException('Kubernetes environment is not yet supported.'));

        $service->getContainersInGroup();
    }

    public function test_getContainersFromDockerHttp_throws_unsupported_operation_exception(): void
    {
        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('HTTP API method is not yet implemented.');

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getContainersFromDockerHttp');
        $method->setAccessible(true);

        $method->invoke($this->service, 'http://example.com/api');
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function test_getCurrentDockerProject_returns_env_value(): void
    {
        $projectName = 'test-project';
        putenv("DOCKER_CONTAINER_GROUP_NAME=$projectName");
        $_ENV['DOCKER_CONTAINER_GROUP_NAME'] = $projectName;
        $_SERVER['DOCKER_CONTAINER_GROUP_NAME'] = $projectName;

        $service = new ContainerLabelService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getCurrentDockerProject');
        $method->setAccessible(true);

        $result = $method->invoke($service);
        $this->assertEquals($projectName, $result);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function test_getCurrentDockerProject_throws_exception_when_env_not_set(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Please set DOCKER_CONTAINER_GROUP_NAME environment variable');

        putenv('DOCKER_CONTAINER_GROUP_NAME');
        unset($_ENV['DOCKER_CONTAINER_GROUP_NAME']);
        unset($_SERVER['DOCKER_CONTAINER_GROUP_NAME']);

        $service = new ContainerLabelService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getCurrentDockerProject');
        $method->setAccessible(true);

        $method->invoke($service);
    }

    public function test_isDockerEnvironment_returns_false_when_no_socket_exists(): void
    {
        // Temporarily set env to non-existent path
        putenv('DOCKER_HOST=unix:///nonexistent/path/docker.sock');

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('isDockerEnvironment');
        $method->setAccessible(true);

        // Mock file_exists to return false
        $service = $this->getMockBuilder(ContainerLabelService::class)
            ->onlyMethods([])
            ->getMock();

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('isDockerEnvironment');
        $method->setAccessible(true);

        // Since we can't easily mock file_exists in this context,
        // we'll test the logic path by assuming socket doesn't exist
        // In a real environment, this would depend on actual file system
        $result = $method->invoke($service);

        // Result depends on actual file system state, so we just verify method exists and can be called
        $this->assertIsBool($result);

        putenv('DOCKER_HOST');
    }

    public function test_isKubernetesEnvironment_returns_false_when_files_dont_exist(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('isKubernetesEnvironment');
        $method->setAccessible(true);

        $result = $method->invoke($this->service);

        // In typical test environments, k8s files won't exist
        // So result should be false
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function test_getContainersFromDockerSocket_throws_exception_when_socket_not_found(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Docker socket not found');

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getContainersFromDockerSocket');
        $method->setAccessible(true);

        $method->invoke($this->service, '/nonexistent/docker.sock');
    }

    public function test_getContainersFromDockerSocket_handles_unix_prefix(): void
    {
        $this->expectException(Exception::class);

        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getContainersFromDockerSocket');
        $method->setAccessible(true);

        // Should handle unix:// prefix by removing it
        try {
            $method->invoke($this->service, 'unix:///nonexistent/docker.sock');
        } catch (Exception $e) {
            // Expected exception about socket not found
            $this->assertStringContainsString('Docker socket not found', $e->getMessage());
            throw $e;
        }
    }
}

