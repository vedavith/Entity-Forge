CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(255),
    customer_name VARCHAR(255),
    total_amount INT,
    status VARCHAR(255),
    payment_method VARCHAR(255),
    created_at VARCHAR(255),
    tenant_id VARCHAR(255),
    updated_at VARCHAR(255)
);