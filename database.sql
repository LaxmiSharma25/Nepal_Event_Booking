-- Create the database
CREATE DATABASE IF NOT EXISTS nepali_event_booking;
USE nepali_event_booking;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Event categories table
CREATE TABLE IF NOT EXISTS event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default event categories
INSERT INTO event_categories (name, description, image) VALUES
('Bratabandh', 'Traditional Nepali coming-of-age ceremony for boys', 'bratabandh.jpg'),
('Marriage', 'Traditional Nepali wedding ceremony', 'marriage.jpg'),
('Mehendi', 'Henna application ceremony before marriage', 'mehendi.jpg'),
('Birthday', 'Birthday celebration event', 'birthday.jpg');

-- Service categories table
CREATE TABLE IF NOT EXISTS service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default service categories
INSERT INTO service_categories (name, description) VALUES
('Photography', 'Professional photography services'),
('Hall', 'Venue and hall booking services'),
('Catering', 'Food and beverage services'),
('Decoration', 'Event decoration services');

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE
);

-- Insert default services
INSERT INTO services (category_id, name, description, price, image) VALUES
(1, 'Basic Photography', 'Basic photography package with 100 photos', 15000.00, 'basic_photo.jpg'),
(1, 'Premium Photography', 'Premium photography package with 300 photos and album', 25000.00, 'premium_photo.jpg'),
(2, 'Small Hall', 'Small hall for up to 100 guests', 30000.00, 'small_hall.jpg'),
(2, 'Large Hall', 'Large hall for up to 300 guests', 50000.00, 'large_hall.jpg'),
(3, 'Basic Catering', 'Basic catering package for up to 100 guests', 20000.00, 'basic_catering.jpg'),
(3, 'Premium Catering', 'Premium catering package for up to 300 guests', 40000.00, 'premium_catering.jpg'),
(4, 'Basic Decoration', 'Basic decoration package', 10000.00, 'basic_decor.jpg'),
(4, 'Premium Decoration', 'Premium decoration package', 25000.00, 'premium_decor.jpg');

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_category_id INT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_category_id) REFERENCES event_categories(id) ON DELETE CASCADE
);

-- Booking details table (services selected for each booking)
CREATE TABLE IF NOT EXISTS booking_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'esewa', 'khalti') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    rating INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin (username, password, email) VALUES
('admin', '$2y$10$Gg8uZtQljgxcGtw80EJeZuA2k.j3HPPZx1w7QlQAvWXRUWUjCR6fq', 'admin@example.com'); 