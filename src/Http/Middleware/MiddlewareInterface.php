<?php

namespace EntityForge\Http\Middleware;

use EntityForge\Http\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): void;
}