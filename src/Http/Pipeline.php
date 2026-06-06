<?php

namespace EntityForge\Http;

use EntityForge\Http\Middleware\MiddlewareInterface;

class Pipeline
{
    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;
        return $clone;
    }

    public function run(Request $request, callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn(callable $carry, MiddlewareInterface $mw): callable => fn(Request $req): Response => $mw->handle($req, $carry),
            $destination
        );

        return $pipeline($request);
    }
}
