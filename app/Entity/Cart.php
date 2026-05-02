<?php

namespace App\Entity;

class Cart
{
    public int $id;
    public int $user_id;
    public int $product_id;
    public int $quantity;
    public string $tenant_id;
    public string $created_at;
    public string $updated_at;




}