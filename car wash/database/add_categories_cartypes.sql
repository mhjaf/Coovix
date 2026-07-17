-- Add categories and car_types tables

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '🚗',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS car_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '🚗',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT INTO categories (name, icon) VALUES
('Car Wash', '🚗'),
('Polish', '✨')
ON DUPLICATE KEY UPDATE name=name;

-- Insert default car types
INSERT INTO car_types (name, icon) VALUES
('All Types', '🚗'),
('Sedan', '🚙'),
('SUV', '🚐'),
('Hatchback', '🚗'),
('Coupe', '🏎️'),
('Convertible', '🚘'),
('Truck', '🚚'),
('Van', '🚐'),
('Minivan', '🚐'),
('Wagon', '🚙')
ON DUPLICATE KEY UPDATE name=name;
