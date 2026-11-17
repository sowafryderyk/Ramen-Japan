CREATE DATABASE IF NOT EXISTS ramen_japan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE ramen_japan;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    ingredients VARCHAR(255) NOT NULL,
    price DECIMAL(8,2) UNSIGNED NOT NULL,
    available TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(80) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    total DECIMAL(9,2) UNSIGNED NOT NULL,
    status ENUM('Nowe', 'W przygotowaniu', 'W drodze', 'Dostarczone') NOT NULL DEFAULT 'Nowe',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    menu_item_id BIGINT UNSIGNED NULL,
    item_name VARCHAR(100) NOT NULL,
    unit_price DECIMAL(8,2) UNSIGNED NOT NULL,
    quantity TINYINT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO menu_items (name, description, ingredients, price)
SELECT 'Shoyu Ramen', 'Lekki, aromatyczny bulion na bazie sosu sojowego.', 'Bulion drobiowy · makaron · chashu · jajko · nori', 32.00
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Shoyu Ramen');
INSERT INTO menu_items (name, description, ingredients, price)
SELECT 'Miso Ramen', 'Głęboki smak fermentowanej pasty miso i sezamu.', 'Bulion miso · makaron · kukurydza · jajko · szczypior', 35.00
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Miso Ramen');
INSERT INTO menu_items (name, description, ingredients, price)
SELECT 'Spicy Tan Tan', 'Kremowy, pikantny bulion z nutą orzechów.', 'Bulion sezamowy · wieprzowina · pak choi · chili', 37.00
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Spicy Tan Tan');
INSERT INTO menu_items (name, description, ingredients, price)
SELECT 'Tonkotsu Ramen', 'Długo gotowany bulion wieprzowy o aksamitnej konsystencji.', 'Bulion tonkotsu · makaron · chashu · jajko · nori', 39.00
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Tonkotsu Ramen');
