<?php
namespace EntityForge\Generator\Builder;

use EntityForge\Generator\Schema\EntitySchema;

class RepositoryBuilder
{
    public function build(EntitySchema $schema): string
    {
        $className = $schema->getEntityName() . 'Repository';

        return <<<PHP
<?php

namespace App\Repository;

use EntityForge\Repository\BaseRepository;

class {$className} extends BaseRepository
{
    public function create(array \$data): array
    {
        unset(\$data['tenant_id']);
        \$data = \$this->applyTenantScope(\$data);
        
        return \$data;
    }
}
PHP;
    }
}