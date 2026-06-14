<?php

namespace EntityForge\Generator\Builder;

class ControllerBuilder
{
    public function build(string $name): string
    {
        return <<<PHP
<?php

namespace App\Http\Controller;

use EntityForge\Http\Request;
use EntityForge\Http\Response;

class {$name}
{
    public function index(Request \$request): Response
    {
        return (new Response())->withJson([]);
    }

    public function show(Request \$request): Response
    {
        \$id = (int) \$request->param('id');
        return (new Response())->withJson([]);
    }

    public function store(Request \$request): Response
    {
        return (new Response())->withJson([], 201);
    }

    public function update(Request \$request): Response
    {
        \$id = (int) \$request->param('id');
        return (new Response())->withJson([]);
    }

    public function destroy(Request \$request): Response
    {
        \$id = (int) \$request->param('id');
        return (new Response())->withJson(['deleted' => true]);
    }
}
PHP;
    }
}
