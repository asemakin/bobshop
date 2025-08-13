CREATE DATABASE IF NOT EXISTS bob_auto_parts;
USE bob_auto_parts;

CREATE TABLE IF NOT EXISTS warehouse (
  orderId INT AUTO_INCREMENT PRIMARY KEY,
  productName VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS `order` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orderDate DATETIME NOT NULL,
  subTotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL,
  tax DECIMAL(10,2) NOT NULL,
  totalAmount DECIMAL(10,2) NOT NULL,
  deliveryAddress VARCHAR(255),
  deliveryDate DATE,
  deliveryTime TIME,
  customerPhone VARCHAR(20),
  customerEmail VARCHAR(100),
  referralSourceId VARCHAR(50),
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orderItem (
  itemId INT AUTO_INCREMENT PRIMARY KEY,
  orderNumber INT NOT NULL,
  productId INT NOT NULL,
  productName VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_orderItem_order FOREIGN KEY (orderNumber) REFERENCES `order`(id) ON DELETE CASCADE,
  CONSTRAINT fk_orderItem_warehouse FOREIGN KEY (productId) REFERENCES warehouse(orderId) ON DELETE RESTRICT
);

INSERT INTO warehouse (productName, quantity, price) VALUES
  ('Шины', 100, 100.00),
  ('Масло', 50, 10.00),
  ('Свечи зажигания', 200, 4.00);