<?php

declare(strict_types=1);

namespace JobVisa\App\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

/**
 * Lightweight dependency injection container.
 */
final class Container
{
    /** @var array<string, array{concrete: callable|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var list<string> */
    private array $buildStack = [];

    /**
     * Bind an abstract type to a concrete class or factory (transient).
     */
    public function bind(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => false,
        ];
    }

    /**
     * Bind an abstract type as a shared singleton.
     */
    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => true,
        ];
    }

    /**
     * Register an existing object instance.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = [
            'concrete' => $abstract,
            'shared' => true,
        ];
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || class_exists($abstract)
            || interface_exists($abstract);
    }

    /**
     * Resolve an entry from the container.
     */
    public function get(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;
        $concrete = $binding['concrete'] ?? $abstract;
        $shared = $binding['shared'] ?? false;

        $object = $this->resolveConcrete($abstract, $concrete);

        if ($shared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function resolveConcrete(string $abstract, callable|string $concrete): object
    {
        if (is_callable($concrete) && !is_string($concrete)) {
            $result = $concrete($this);

            if (!is_object($result)) {
                throw new RuntimeException('Container factory must return an object.');
            }

            return $result;
        }

        if (is_string($concrete) && $concrete !== $abstract && isset($this->bindings[$concrete])) {
            return $this->get($concrete);
        }

        if (!is_string($concrete)) {
            throw new RuntimeException('Unable to resolve container binding.');
        }

        return $this->build($concrete);
    }

    /**
     * @param  class-string  $class
     */
    private function build(string $class): object
    {
        if (in_array($class, $this->buildStack, true)) {
            $cycle = implode(' -> ', [...$this->buildStack, $class]);
            throw new RuntimeException('Circular dependency detected: ' . $cycle);
        }

        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $exception) {
            throw new RuntimeException('Unable to reflect class for dependency injection.', 0, $exception);
        }

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException('Target type is not instantiable: ' . $class);
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $this->buildStack[] = $class;

        try {
            $dependencies = [];

            foreach ($constructor->getParameters() as $parameter) {
                $dependencies[] = $this->resolveParameter($parameter);
            }

            return $reflector->newInstanceArgs($dependencies);
        } finally {
            array_pop($this->buildStack);
        }
    }

    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            try {
                return $this->get($typeName);
            } catch (RuntimeException $exception) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                if ($parameter->allowsNull()) {
                    return null;
                }

                throw new RuntimeException(
                    'Unable to resolve dependency $' . $parameter->getName() . ' of type ' . $typeName . '.',
                    0,
                    $exception
                );
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new RuntimeException(
            'Unable to resolve primitive dependency $' . $parameter->getName() . '.'
        );
    }
}
