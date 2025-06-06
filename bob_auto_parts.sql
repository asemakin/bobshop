CREATE DATABASE IF NOT EXISTS bob_auto_parts;

USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      orderDate DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      totalAmount DECIMAL(10, 2) NOT NULL,
                                      deliveryAddress VARCHAR(255),
                                      deliveryDate DATE,
                                      deliveryTime TIME,
                                      customerPhone VARCHAR(20),
                                      customerEmail VARCHAR(100),
                                      referralSource VARCHAR(50),
                                      createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

SHOW  TABLES ;

ALTER TABLE `order`
    ADD COLUMN total_quantity INT DEFAULT 0 AFTER spark_quantity;

SELECT * FROM `order`;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      orderDate DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      totalAmount DECIMAL(10, 2) NOT NULL,
                                      deliveryAddress VARCHAR(255),
                                      deliveryDate DATE,
                                      deliveryTime TIME,
                                      customerPhone VARCHAR(20),
                                      customerEmail VARCHAR(100),
                                      referralSource VARCHAR(50),
                                      createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

CREATE DATABASE IF NOT EXISTS bob_auto_parts;

USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS orders (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      orderDate DATETIME NOT NULL,
                                      tire_quantity INT DEFAULT 0,
                                      oil_quantity INT DEFAULT 0,
                                      spark_quantity INT DEFAULT 0,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      totalAmount DECIMAL(10, 2) NOT NULL,
                                      deliveryAddress VARCHAR(255),
                                      deliveryDate DATE,
                                      deliveryTime TIME,
                                      customerPhone VARCHAR(20),
                                      customerEmail VARCHAR(100),
                                      referralSource VARCHAR(50),
                                      createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP );

SELECT * FROM `order`;
SELECT * FROM `order`;
USE bob_auto_parts;
CREATE TABLE warehouse (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       productName VARCHAR(100) NOT NULL,
                       quantity INT NOT NULL,
                       price DECIMAL(10, 2) NOT NULL
);

SHOW  TABLES ;
SELECT * FROM `order`;
SELECT * FROM warehouse;
INSERT INTO warehouse (productName, quantity, price) VALUES
('Шины', 100, 100.00),
('Масло', 50, 10.00),
('Свечи зажигания', 200, 4.00);
DROP TABLE `order`;
CREATE TABLE IF NOT EXISTS order (
                                      id INT AUTO_INCREMENT PRIMARY KEY,
                                      orderDate DATETIME NOT NULL,
                                      subtotal DECIMAL(10, 2) NOT NULL,
                                      discount DECIMAL(10, 2) DEFAULT 0.00,
                                      tax DECIMAL(10, 2) NOT NULL,
                                      totalAmount DECIMAL(10, 2) NOT NULL,
                                      deliveryAddress VARCHAR(255),
                                      deliveryDate DATE,
                                      deliveryTime TIME,
                                      customerPhone VARCHAR(20),
                                      customerEmail VARCHAR(100),
                                      referralSource VARCHAR(50),
                                      createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS order_items (
                                           itemID INT AUTO_INCREMENT PRIMARY KEY,
                                           id INT NOT NULL,
                                           productID INT NOT NULL,
                                           productName VARCHAR(100) NOT NULL,
                                           quantity INT NOT NULL,
                                           price DECIMAL(10, 2) NOT NULL,
                                           FOREIGN KEY (id) REFERENCES order(id),
                                           FOREIGN KEY (productID) REFERENCES1 warehouse(id)
);
SELECT * FROM orderItems;
SELECT * FROM warehouse;
SELECT * FROM order;

ALTER TABLE orderitems
    DROP FOREIGN KEY orderitems_ibfk_2;

ALTER TABLE orderitems
    ADD CONSTRAINT orderitems_ibfk_2
        FOREIGN KEY (productID) REFERENCES warehouse(productID)
            ON DELETE CASCADE;


USE bob_auto_parts;

SELECT * FROM warehouse;
SELECT * FROM `order`;
SELECT * FROM orderitems;

ALTER TABLE `orderitems`
    MODIFY COLUMN `productName` VARCHAR(255) NULL,
    MODIFY COLUMN `productID` INT NULL;

ALTER TABLE `order` AUTO_INCREMENT = 1;
TRUNCATE TABLE `order`;
DELETE FROM `order`;
ALTER TABLE `order` AUTO_INCREMENT = 1;

-- Отключите проверку внешних ключей
SET FOREIGN_KEY_CHECKS = 0;

-- Удалите все записи
DELETE FROM `warehouse`;

-- Сбросьте автоинкремент
ALTER TABLE `warehouse` AUTO_INCREMENT = 1;

-- Включите проверку обратно
SET FOREIGN_KEY_CHECKS = 1;

USE bob_auto_parts;

CREATE TABLE `orderReferralInfo` (
                                       `orderID` int(11) NOT NULL,
                                       `sourceCode` char(1) NOT NULL,
                                       `sourceName` varchar(50) NOT NULL,
                                       PRIMARY KEY (`orderID`),
                                       CONSTRAINT `fk_ori_order` FOREIGN KEY (`orderID`) REFERENCES `order` (`orderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
