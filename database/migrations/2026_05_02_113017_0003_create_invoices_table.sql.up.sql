CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(255),
    amount INT,
    customer_id INT,
    tenant_id VARCHAR(255),
    created_at VARCHAR(255),
    updated_at VARCHAR(255)
);