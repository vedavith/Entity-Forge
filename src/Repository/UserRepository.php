<?php

namespace EntityForge\Repository;

use EntityForge\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * @throws \Exception
     */
    public function create(array $data): array
    {
        $data = $this->applyTenantScope($data);

        return $data;
    }
}