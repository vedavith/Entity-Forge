CREATE TABLE carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    quantity INT,
    tenant_id VARCHAR(255),
    created_at VARCHAR(255),
    updated_at VARCHAR(255)
);