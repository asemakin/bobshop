CREATE DATABASE IF NOT EXISTS bob_auto_parts;

USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      order_date DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      total_amount DECIMAL(10, 2) NOT NULL,
                                      delivery_address VARCHAR(255),
                                      delivery_date DATE,
                                      delivery_time TIME,
                                      customer_phone VARCHAR(20),
                                      customer_email VARCHAR(100),
                                      referral_source VARCHAR(50),
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

SHOW  TABLES ;

ALTER TABLE orders
    ADD COLUMN total_quantity INT DEFAULT 0 AFTER spark_quantity;

SELECT * FROM orders;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      order_date DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      total_amount DECIMAL(10, 2) NOT NULL,
                                      delivery_address VARCHAR(255),
                                      delivery_date DATE,
                                      delivery_time TIME,
                                      customer_phone VARCHAR(20),
                                      customer_email VARCHAR(100),
                                      referral_source VARCHAR(50),
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

CREATE DATABASE IF NOT EXISTS bob_auto_parts;

USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      order_date DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      total_amount DECIMAL(10, 2) NOT NULL,
                                      delivery_address VARCHAR(255),
                                      delivery_date DATE,
                                      delivery_time TIME,
                                      customer_phone VARCHAR(20),
                                      customer_email VARCHAR(100),
                                      referral_source VARCHAR(50),
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

SELECT * FROM orders;
SELECT * FROM orders;
USE bob_auto_parts;
CREATE TABLE warehouse (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       product_name VARCHAR(100) NOT NULL,
                       quantity INT NOT NULL,
                       price DECIMAL(10, 2) NOT NULL
);

SHOW  TABLES ;
SELECT * FROM orders;
SELECT * FROM warehouse;
INSERT INTO warehouse (product_name, quantity, price) VALUES
('Шины', 100, 100.00),
('Масло', 50, 10.00),
('Свечи зажигания', 200, 4.00);
DROP TABLE orders;
CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      order_date DATETIME NOT NULL,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      total_amount DECIMAL(10, 2) NOT NULL,
                                      delivery_address VARCHAR(255),
                                      delivery_date DATE,
                                      delivery_time TIME,
                                      customer_phone VARCHAR(20),
                                      customer_email VARCHAR(100),
                                      referral_source VARCHAR(50),
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS order_items (
                                           item_id INT AUTO_INCREMENT PRIMARY KEY,
                                           id INT NOT NULL,
                                           product_id INT NOT NULL,
                                           product_name VARCHAR(100) NOT NULL,
                                           quantity INT NOT NULL,
                                           price DECIMAL(10, 2) NOT NULL,
                                           FOREIGN KEY (id) REFERENCES orders(id),
                                           FOREIGN KEY (product_id) REFERENCES warehouse(id)
);
SELECT * FROM order_items;
SELECT * FROM warehouse;
SELECT * FROM orders;