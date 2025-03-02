<?php
/**
 * Class DialogProContainerTest
 *
 * @package DialogPro
 */

class DialogProContainerTest extends WP_UnitTestCase {
    private $container;

    /**
     * Test service class
     */
    class TestService {
        public $dependency;
        
        public function __construct($dependency = null) {
            $this->dependency = $dependency;
        }
    }

    /**
     * Test dependency class
     */
    class TestDependency {
        public $value = 'test';
    }

    public function setUp(): void {
        parent::setUp();
        $this->container = new DialogProContainer();
    }

    /**
     * Test service registration
     */
    public function test_register_service() {
        $this->container->register('test', function() {
            return new self::TestService();
        });

        $this->assertTrue($this->container->has('test'));
    }

    /**
     * Test service retrieval
     */
    public function test_get_service() {
        $this->container->register('test', function() {
            return new self::TestService();
        });

        $service = $this->container->get('test');
        
        $this->assertInstanceOf(self::TestService::class, $service);
    }

    /**
     * Test service singleton behavior
     */
    public function test_service_singleton() {
        $this->container->register('test', function() {
            return new self::TestService();
        });

        $service1 = $this->container->get('test');
        $service2 = $this->container->get('test');
        
        $this->assertSame($service1, $service2);
    }

    /**
     * Test service dependency injection
     */
    public function test_service_dependency_injection() {
        // Register dependency
        $this->container->register('dependency', function() {
            return new self::TestDependency();
        });

        // Register service with dependency
        $this->container->register('service', function($container) {
            $dependency = $container->get('dependency');
            return new self::TestService($dependency);
        });

        $service = $this->container->get('service');
        
        $this->assertInstanceOf(self::TestDependency::class, $service->dependency);
        $this->assertEquals('test', $service->dependency->value);
    }

    /**
     * Test service not found exception
     */
    public function test_service_not_found() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service not found: nonexistent');
        
        $this->container->get('nonexistent');
    }

    /**
     * Test service removal
     */
    public function test_remove_service() {
        $this->container->register('test', function() {
            return new self::TestService();
        });

        $this->assertTrue($this->container->has('test'));
        
        $this->container->remove('test');
        
        $this->assertFalse($this->container->has('test'));
    }

    /**
     * Test getting all registered services
     */
    public function test_get_services() {
        $this->container->register('service1', function() {
            return new self::TestService();
        });
        
        $this->container->register('service2', function() {
            return new self::TestService();
        });

        $services = $this->container->getServices();
        
        $this->assertCount(2, $services);
        $this->assertContains('service1', $services);
        $this->assertContains('service2', $services);
    }

    /**
     * Test circular dependency detection
     */
    public function test_circular_dependency_detection() {
        $this->container->register('service1', function($container) {
            return new self::TestService($container->get('service2'));
        });

        $this->container->register('service2', function($container) {
            return new self::TestService($container->get('service1'));
        });

        $this->expectException(Exception::class);
        $this->container->get('service1');
    }

    /**
     * Test factory function execution
     */
    public function test_factory_execution() {
        $executed = false;
        
        $this->container->register('test', function() use (&$executed) {
            $executed = true;
            return new self::TestService();
        });

        // Factory should not execute until service is requested
        $this->assertFalse($executed);
        
        $this->container->get('test');
        
        $this->assertTrue($executed);
    }

    /**
     * Test multiple dependency chain
     */
    public function test_multiple_dependency_chain() {
        $this->container->register('dep1', function() {
            return new self::TestDependency();
        });

        $this->container->register('dep2', function($container) {
            return new self::TestService($container->get('dep1'));
        });

        $this->container->register('service', function($container) {
            return new self::TestService($container->get('dep2'));
        });

        $service = $this->container->get('service');
        
        $this->assertInstanceOf(self::TestService::class, $service);
        $this->assertInstanceOf(self::TestService::class, $service->dependency);
        $this->assertInstanceOf(self::TestDependency::class, $service->dependency->dependency);
    }

    /**
     * Test service registration with invalid factory
     */
    public function test_register_invalid_factory() {
        $this->expectException(TypeError::class);
        $this->container->register('test', 'not_a_callable');
    }

    /**
     * Test removing non-existent service
     */
    public function test_remove_nonexistent_service() {
        // Should not throw an exception
        $this->container->remove('nonexistent');
        $this->assertFalse($this->container->has('nonexistent'));
    }

    /**
     * Test service registration overwrite
     */
    public function test_service_registration_overwrite() {
        $this->container->register('test', function() {
            return new self::TestService();
        });

        $this->container->register('test', function() {
            return new self::TestDependency();
        });

        $service = $this->container->get('test');
        
        $this->assertInstanceOf(self::TestDependency::class, $service);
    }
}