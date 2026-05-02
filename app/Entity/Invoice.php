<?php

namespace App\Entity;

class Invoice
{
    public int $id;
    public string $invoice_number;
    public int $amount;
    public int $customer_id;
    public string $tenant_id;
    public string $created_at;
    public string $updated_at;



            public function customer(): string
                {
                return 'Customer via customer_id';
                }
                

}