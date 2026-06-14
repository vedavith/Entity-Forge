<?php

namespace EntityForge\Http\Middleware;

/**
 * Marker interface for auth middleware.
 *
 * Implementations must authenticate the request and attach the resolved
 * identity to it before passing it downstream:
 *
 *   return $next($request->withAttribute('user', $user));
 *
 * Downstream handlers retrieve the identity via:
 *
 *   $user = $request->getAttribute('user');
 */
interface AuthMiddlewareInterface extends MiddlewareInterface {}
