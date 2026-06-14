<?php

namespace EntityForge\Generator\Builder;

class MiddlewareBuilder
{
    public function build(string $name): string
    {
        return <<<PHP
<?php

namespace App\Http\Middleware;

use EntityForge\Http\Middleware\MiddlewareInterface;
use EntityForge\Http\Request;
use EntityForge\Http\Response;

class {$name} implements MiddlewareInterface
{
    public function handle(Request \$request, callable \$next): Response
    {
        return \$next(\$request);
    }
}
PHP;
    }

    public function buildAuth(string $name): string
    {
        return <<<PHP
<?php

namespace App\Http\Middleware;

use EntityForge\Http\Middleware\AuthMiddlewareInterface;
use EntityForge\Http\Request;
use EntityForge\Http\Response;

class {$name} implements AuthMiddlewareInterface
{
    public function handle(Request \$request, callable \$next): Response
    {
        // 1. Extract credentials from the request (e.g. Bearer token, session, API key).
        //    \$token = \$request->header('Authorization');

        // 2. Verify the credentials with your auth provider (Firebase, Auth0, custom JWT, etc.).
        //    \$user = \$this->authProvider->verify(\$token);

        // 3. Attach the resolved identity to the request and pass it downstream.
        //    return \$next(\$request->withAttribute('user', \$user));

        // 4. On failure, return an early response without calling \$next.
        //    return (new Response())->withStatus(401)->withJson(['error' => 'Unauthorized']);

        return \$next(\$request);
    }
}
PHP;
    }
}
