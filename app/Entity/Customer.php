<?php

namespace App\Entity;

class Customer
{
    public int $id;
    public string $name;
    public string $tenant_id;
    public string $created_at;
    public string $updated_at;



        public function invoices(): string
            {
            return 'Invoice list via customer_id';
            }

}