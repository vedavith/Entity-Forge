<?php

namespace App\Repository;

use EntityForge\Repository\BaseRepository;

class CartRepository extends BaseRepository
{
    public function create(array $data): array
    {
        unset($data['tenant_id']);
        $data = $this->applyTenantScope($data);
        
        return $data;
    }
}