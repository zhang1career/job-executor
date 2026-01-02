<?php

namespace Tests\Unit\Services;

use App\Exceptions\UnsupportedOperationException;
use App\Services\ContainerLabelService;
use App\Services\DiscoverService;
use Illuminate\Support\Facades\Redis;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class DiscoverServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_filterAppMap_filters_containers_with_appmap(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('filterAppMap');
        $method->setAccessible(true);

        $containers = [
            ['name' => 'container1', 'appmap' => 'service1:8080'],
            ['name' => 'container2', 'appmap' => 'service2:9090'],
            ['name' => 'container3'], // No appmap
            ['name' => 'container4', 'appmap' => 'service3:3000'],
        ];

        $result = $method->invoke($service, $containers);

        $expected = [
            'container1' => 'service1:8080',
            'container2' => 'service2:9090',
            'container4' => 'service3:3000',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_filterAppMap_handles_empty_array(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('filterAppMap');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertEquals([], $result);
    }

    public function test_parseServiceMap_parses_single_service(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseServiceMap');
        $method->setAccessible(true);

        $containerAppMap = [
            'container1' => 'service1:8080',
        ];

        $result = $method->invoke($service, $containerAppMap);

        $expected = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_parseServiceMap_parses_multiple_services(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseServiceMap');
        $method->setAccessible(true);

        $containerAppMap = [
            'container1' => 'service1:8080,service2:9090',
            'container2' => 'service3:3000',
        ];

        $result = $method->invoke($service, $containerAppMap);

        $expected = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
            [
                'container' => 'container1',
                'service' => 'service2',
                'port' => '9090',
            ],
            [
                'container' => 'container2',
                'service' => 'service3',
                'port' => '3000',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_parseServiceMap_handles_whitespace(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseServiceMap');
        $method->setAccessible(true);

        $containerAppMap = [
            'container1' => ' service1 : 8080 , service2 : 9090 ',
        ];

        $result = $method->invoke($service, $containerAppMap);

        $expected = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
            [
                'container' => 'container1',
                'service' => 'service2',
                'port' => '9090',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_parseServiceMap_skips_invalid_entries(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseServiceMap');
        $method->setAccessible(true);

        $containerAppMap = [
            'container1' => 'service1:8080,invalid,service2:9090,:3000,service3:',
        ];

        $result = $method->invoke($service, $containerAppMap);

        // Should only parse valid entries
        $expected = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
            [
                'container' => 'container1',
                'service' => 'service2',
                'port' => '9090',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_parseServiceMap_handles_empty_strings(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('parseServiceMap');
        $method->setAccessible(true);

        $containerAppMap = [
            'container1' => ',service1:8080,,',
        ];

        $result = $method->invoke($service, $containerAppMap);

        $expected = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_buildServiceDict_builds_single_service(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildServiceDict');
        $method->setAccessible(true);

        $serviceMappings = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
        ];

        $result = $method->invoke($service, $serviceMappings);

        $expected = [
            'service1' => 'container1:8080',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_buildServiceDict_combines_multiple_containers_for_same_service(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildServiceDict');
        $method->setAccessible(true);

        $serviceMappings = [
            [
                'container' => 'container1',
                'service' => 'service1',
                'port' => '8080',
            ],
            [
                'container' => 'container2',
                'service' => 'service1',
                'port' => '9090',
            ],
            [
                'container' => 'container3',
                'service' => 'service2',
                'port' => '3000',
            ],
        ];

        $result = $method->invoke($service, $serviceMappings);

        $expected = [
            'service1' => 'container1:8080,container2:9090',
            'service2' => 'container3:3000',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_buildServiceDict_handles_empty_array(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('buildServiceDict');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertEquals([], $result);
    }

    public function test_writeToRedis_writes_keys_with_ttl(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $redisMock = Mockery::mock();
        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $redisMock->shouldReceive('setex')
            ->once()
            ->with('reg:serv:service1', 86400, 'container1:8080')
            ->andReturn(true);

        $redisMock->shouldReceive('setex')
            ->once()
            ->with('reg:serv:service2', 86400, 'container2:9090')
            ->andReturn(true);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('writeToRedis');
        $method->setAccessible(true);

        $serviceDict = [
            'service1' => 'container1:8080',
            'service2' => 'container2:9090',
        ];

        $result = $method->invoke($service, $serviceDict);

        $expected = [
            'reg:serv:service1',
            'reg:serv:service2',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_writeToRedis_handles_empty_dict(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $service = new DiscoverService($containerLabelService);

        $redisMock = Mockery::mock();
        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('writeToRedis');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertEquals([], $result);
    }

    public function test_discover_integration(): void
    {
        $containers = [
            ['name' => 'container1', 'appmap' => 'service1:8080,service2:9090'],
            ['name' => 'container2', 'appmap' => 'service1:3000'],
        ];

        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $containerLabelService->shouldReceive('getContainersInGroup')
            ->once()
            ->andReturn($containers);

        $redisMock = Mockery::mock();
        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $redisMock->shouldReceive('setex')
            ->with('reg:serv:service1', 86400, 'container1:8080,container2:3000')
            ->once()
            ->andReturn(true);

        $redisMock->shouldReceive('setex')
            ->with('reg:serv:service2', 86400, 'container1:9090')
            ->once()
            ->andReturn(true);

        $service = new DiscoverService($containerLabelService);

        $result = $service->discover();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('containers_processed', $result);
        $this->assertArrayHasKey('services_registered', $result);
        $this->assertArrayHasKey('redis_keys_written', $result);
        $this->assertEquals(2, $result['containers_processed']);
        $this->assertEquals(2, $result['services_registered']);
        $this->assertCount(2, $result['redis_keys_written']);
    }

    public function test_discover_handles_empty_containers(): void
    {
        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $containerLabelService->shouldReceive('getContainersInGroup')
            ->once()
            ->andReturn([]);

        $redisMock = Mockery::mock();
        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $service = new DiscoverService($containerLabelService);

        $result = $service->discover();

        $this->assertEquals(0, $result['containers_processed']);
        $this->assertEquals(0, $result['services_registered']);
        $this->assertCount(0, $result['redis_keys_written']);
    }

    public function test_discover_handles_containers_without_appmap(): void
    {
        $containers = [
            ['name' => 'container1'], // No appmap
            ['name' => 'container2', 'appmap' => 'service1:8080'],
        ];

        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $containerLabelService->shouldReceive('getContainersInGroup')
            ->once()
            ->andReturn($containers);

        $redisMock = Mockery::mock();
        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($redisMock);

        $redisMock->shouldReceive('setex')
            ->with('reg:serv:service1', 86400, 'container2:8080')
            ->once()
            ->andReturn(true);

        $service = new DiscoverService($containerLabelService);

        $result = $service->discover();

        $this->assertEquals(1, $result['containers_processed']);
        $this->assertEquals(1, $result['services_registered']);
    }

    public function test_discover_propagates_exception_from_container_label_service(): void
    {
        $this->expectException(UnsupportedOperationException::class);

        $containerLabelService = Mockery::mock(ContainerLabelService::class);
        $containerLabelService->shouldReceive('getContainersInGroup')
            ->once()
            ->andThrow(new UnsupportedOperationException('Test exception'));

        $service = new DiscoverService($containerLabelService);
        $service->discover();
    }
}

