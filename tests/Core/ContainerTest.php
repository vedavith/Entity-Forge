<?php

namespace Tests\Core;

use EntityForge\Core\Container;
use PHPUnit\Framework\TestCase;

class AutowireLeaf {}

class AutowireComposite
{
    public AutowireLeaf $leaf;
    public function __construct(AutowireLeaf $leaf)
    {
        $this->leaf = $leaf;
    }
}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_bind_returns_new_instance_each_call(): void
    {
        $this->container->bind(\stdClass::class, fn() => new \stdClass());

        $a = $this->container->make(\stdClass::class);
        $b = $this->container->make(\stdClass::class);

        $this->assertNotSame($a, $b);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(\stdClass::class, fn() => new \stdClass());

        $a = $this->container->make(\stdClass::class);
        $b = $this->container->make(\stdClass::class);

        $this->assertSame($a, $b);
    }

    public function test_instance_returns_registered_object(): void
    {
        $obj = new \stdClass();
        $obj->value = 42;

        $this->container->instance(\stdClass::class, $obj);

        $this->assertSame($obj, $this->container->make(\stdClass::class));
    }

    public function test_has_returns_true_for_bound_abstract(): void
    {
        $this->container->bind(\stdClass::class, fn() => new \stdClass());

        $this->assertTrue($this->container->has(\stdClass::class));
    }

    public function test_has_returns_false_for_unbound_abstract(): void
    {
        $this->assertFalse($this->container->has('UnknownClass'));
    }

    public function test_make_throws_for_unknown_and_non_existent_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot resolve/');

        $this->container->make('No\\Such\\Class');
    }

    public function test_autowire_resolves_class_with_no_constructor(): void
    {
        $result = $this->container->make(\stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function test_factory_receives_container_instance(): void
    {
        $this->container->bind('test', function (Container $c) {
            $obj       = new \stdClass();
            $obj->self = $c;
            return $obj;
        });

        $result = $this->container->make('test');

        $this->assertSame($this->container, $result->self);
    }

    public function test_autowire_resolves_typed_class_dependency(): void
    {
        $result = $this->container->make(AutowireComposite::class);

        $this->assertInstanceOf(AutowireComposite::class, $result);
        $this->assertInstanceOf(AutowireLeaf::class, $result->leaf);
    }

    public function test_autowire_throws_when_param_has_no_type_and_no_default(): void
    {
        // Create a class dynamically that has an unresolvable constructor param
        $class = new class('placeholder') {
            public function __construct(string $value) {}
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot auto-wire/');

        $this->container->make(get_class($class));
    }
}