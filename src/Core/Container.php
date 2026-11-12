<?php

declare(strict_types=1);

namespace DataForge\Core;

/**
 * Contenedor de dependencias minimo con resolucion por reflexion.
 *
 * Las dependencias que no se pueden construir de forma automatica se registran
 * de forma explicita con bind() o singleton().
 */
final class Container
{
    /** @var array<string, \Closure(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Registra una fabrica que se ejecuta cada vez que se resuelve la dependencia.
     */
    public function bind(string $id, \Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    /**
     * Registra una fabrica que se ejecuta una sola vez.
     */
    public function singleton(string $id, \Closure $factory): void
    {
        $this->bind($id, function (self $container) use ($factory, $id): mixed {
            $instance = $factory($container);

            $this->instances[$id] = $instance;

            return $instance;
        });
    }

    public function instance(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($this);
        }

        if (!class_exists($id)) {
            throw new \RuntimeException(sprintf('No se puede resolver la dependencia "%s".', $id));
        }

        $instance             = $this->build($id);
        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * Devuelve la instancia garantizando el tipo esperado.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function typed(string $id): object
    {
        $instance = $this->get($id);
        if (!is_object($instance) || !is_a($instance, $id)) {
            throw new \RuntimeException(sprintf('La dependencia "%s" no es un objeto del tipo esperado.', $id));
        }

        return $instance;
    }

    /**
     * @param class-string $class
     */
    private function build(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException(sprintf('La clase "%s" no se puede instanciar.', $class));
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new \RuntimeException(sprintf(
                'No se puede resolver el parametro "$%s" de "%s".',
                $parameter->getName(),
                $class,
            ));
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
