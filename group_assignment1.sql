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