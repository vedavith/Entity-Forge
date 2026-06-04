<?php

use EntityForge\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    public function create(array $data): array
    {
        $data = $this->applyTenantScope($data);
        return $data;
    }
}