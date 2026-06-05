<?php

namespace EntityForge\Http\Middleware;

use EntityForge\Http\Request;
use EntityForge\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}