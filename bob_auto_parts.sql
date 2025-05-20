CREATE DATABASE IF NOT EXISTS bob_auto_parts;

USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      order_number VARCHAR(20) NOT NULL,
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
                                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SHOW TABLES;

SELECT * FROM orders ORDER BY id DESC LIMIT 10;
SELECT * FROM orders ORDER BY id DESC LIMIT 10;
ALTER TABLE orders
    ADD COLUMN total_quantity INT NOT NULL DEFAULT 0 AFTER spark_quantity;

SELECT * FROM orders ORDER BY id DESC LIMIT 10;

ALTER TABLE orders
    DROP COLUMN id,
    MODIFY order_number INT AUTO_INCREMENT PRIMARY KEY;

ALTER TABLE orders DROP COLUMN id;

SELECT * FROM orders;

ALTER TABLE orders MODIFY order_number INT AUTO_INCREMENT PRIMARY KEY;

SELECT order_number, COUNT(*) as duplicates
FROM orders
GROUP BY order_number
HAVING duplicates > 1;

SET @counter = 0;
UPDATE orders
SET order_number = (SELECT MAX(order_number) FROM orders) + (@counter := @counter + 1)
WHERE order_number IN (
    SELECT order_number FROM (
                                 SELECT order_number FROM orders GROUP BY order_number HAVING COUNT(*) > 1
                             ) AS dupes
);

DELETE t1 FROM orders t1
                   INNER JOIN orders t2
WHERE t1.order_number = t2.order_number AND t1.id > t2.id;

SELECT order_number, COUNT(*) as duplicates
FROM orders
GROUP BY order_number
HAVING duplicates > 1;

DROP TABLE orders;


CREATE TABLE IF NOT EXISTS orders (
                                      order_number INT AUTO_INCREMENT PRIMARY KEY,
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
                                      order_number INT AUTO_INCREMENT PRIMARY KEY,
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
                                      order_number INT AUTO_INCREMENT PRIMARY KEY,
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
