<?php
/**
 * Service Container Class
 * 
 * Manages dependency injection and service instantiation
 * @package DialogPro
 */

class DialogProContainer {
    private $services = [];
    private $factories = [];
    private $instances = [];

    /**
     * Register a service
     */
    public function register(string $id, callable $factory): void {
        $this->factories[$id] = $factory;
    }

    /**
     * Get a service
     * 
     * @throws Exception If service not found
     */
    public function get(string $id) {
        // Return existing instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Check if factory exists
        if (!isset($this->factories[$id])) {
            throw new Exception("Service not found: $id");
        }

        // Create new instance
        $instance = $this->factories[$id]($this);
        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * Check if service exists
     */
    public function has(string $id): bool {
        return isset($this->factories[$id]);
    }

    /**
     * Remove a service
     */
    public function remove(string $id): void {
        unset($this->factories[$id], $this->instances[$id]);
    }

    /**
     * Get all registered services
     */
    public function getServices(): array {
        return array_keys($this->factories);
    }
}