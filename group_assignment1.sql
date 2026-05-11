CREATE DATABASE IF NOT EXISTS admin_order;

USE admin_order;

CREATE TABLE IF NOT EXISTS items(
	`id` INT PRIMARY KEY AUTO_INCREMENT,
    `i_name` VARCHAR(500),
    `description` VARCHAR (500),
    `price` DECIMAL(10,2),
    `category` ENUM('FOOD','DRINK') DEFAULT 'FOOD',
    `stock` INT(11),
    `image`VARCHAR(500)
);

CREATE TABLE IF NOT EXISTS orders(
	`id` INT PRIMARY KEY AUTO_INCREMENT,
    `table_number` VARCHAR(50),
    `total_price`DECIMAL(10,2),
    `status`ENUM('PENDING','COMPLETED') DEFAULT 'PENDING',
    `created_time` TIMESTAMP DEFAULT current_timestamp
);

CREATE TABLE IF NOT EXISTS order_details (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT,
    `item_name` VARCHAR(255),
    `price` DECIMAL(10, 2),
    `quantity` INT,
    `remark` TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50),
  `password` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL;*/

ALTER TABLE `orders` MODIFY `status` ENUM('Pending', 'Completed', 'Paid', 'Cancelled') NOT NULL;