Database Name: wasabi_kitchen

Path: http://localhost/database_project/api.php

=====================================

Admin Access:-

CREATE TABLE admin_passwords (
  id INT AUTO_INCREMENT PRIMARY KEY,
  password VARCHAR(255) NOT NULL
);

Insert Password:-

INSERT INTO admin_passwords (password) VALUES ('112233');

=====================================

Create Product:-

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL
);

Delete Single Product: DELETE FROM products WHERE id = ;

Delete All Products: DELETE FROM products;

Sirial Reorganize: TRUNCATE TABLE products;

=====================================

Create Order:-

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(100) NOT NULL,
  food_names JSON NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  delivered BOOLEAN NOT NULL DEFAULT false
);

Delete Single Product: DELETE FROM orders WHERE id = ;

Delete All Products: DELETE FROM orders;

Sirial Reorganize: TRUNCATE TABLE orders;
