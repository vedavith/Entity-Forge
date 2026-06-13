<?php

namespace EntityForge\Core;

class Container
{
    /** @var array<string, callable(Container): mixed> */
    private array $bindings   = [];
    /** @var array<string, callable(Container): mixed> */
    private array $singletons = [];
    /** @var array<string, mixed> */
    private array $instances  = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    public function instance(string $abstract, mixed $concrete): void
    {
        $this->instances[$abstract] = $concrete;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = ($this->singletons[$abstract])($this);
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        return $this->autowire($abstract);
    }

    private function autowire(string $class): mixed
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Cannot resolve '{$class}': not bound and not a class.");
        }

        $reflector   = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(
                    "Cannot auto-wire '{$class}': parameter '\${$param->getName()}' has no type hint or default."
                );
            }
        }

        return new $class(...$args);
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || isset($this->singletons[$abstract])
            || isset($this->bindings[$abstract]);
    }
}