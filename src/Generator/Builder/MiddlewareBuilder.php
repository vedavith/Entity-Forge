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
}
