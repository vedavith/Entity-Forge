<?php

namespace App\Entity;

class Order
{
    public int $id;
    public string $order_number;
    public string $customer_name;
    public int $total_amount;
    public string $status;
    public string $payment_method;
    public string $created_at;
    public string $tenant_id;
    public string $updated_at;




}