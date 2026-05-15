-- MisterBoleta Database Schema
-- Compatible with MySQL 5.7+

CREATE DATABASE IF NOT EXISTS misterboleta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE misterboleta;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('concierto', 'deporte', 'teatro', 'festival', 'otro') DEFAULT 'otro',
    venue VARCHAR(200) NOT NULL,
    address VARCHAR(500),
    city VARCHAR(100) NOT NULL,
    event_date DATETIME NOT NULL,
    image VARCHAR(500),
    organizer_name VARCHAR(200),
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Event zones table
CREATE TABLE IF NOT EXISTS event_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    zone_name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    available INT NOT NULL,
    color VARCHAR(20) DEFAULT '#e94560',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code VARCHAR(20) UNIQUE NOT NULL,
    event_id INT NOT NULL,
    zone_id INT NOT NULL,
    user_id INT,
    seat_row VARCHAR(10),
    seat_number VARCHAR(10),
    status ENUM('available', 'reserved', 'sold', 'used', 'cancelled') DEFAULT 'available',
    purchase_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES event_zones(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(30) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    tickets_count INT NOT NULL,
    payment_method VARCHAR(50),
    payment_reference VARCHAR(100),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    customer_name VARCHAR(100),
    customer_email VARCHAR(150),
    customer_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    ticket_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    seat_info VARCHAR(50),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- Payment methods configuration table
CREATE TABLE IF NOT EXISTS payment_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) UNIQUE NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    config_json TEXT,
    display_name VARCHAR(100),
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT INTO payment_settings (method_name, display_name, enabled, instructions) VALUES
('mercadopago', 'MercadoPago', 1, 'Paga de forma segura con tu cuenta de MercadoPago'),
('nequi', 'Nequi', 1, 'Transferencia desde tu app Nequi al número asignado'),
('daviplata', 'Daviplata', 1, 'Transferencia desde tu app Daviplata'),
('breeze', 'BBVA Breeze', 1, 'Pago con tu cuenta BBVA Breeze'),
('cash', 'Pago en Efectivo', 1, 'Paga al momento de recibir tus boletas');

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Administrador', 'admin@misterboleta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample events
INSERT INTO events (title, description, category, venue, address, city, event_date, image, status, featured) VALUES
('Concierto de Juanes', 'El artista colombiano Juanes regresa a Medellín con su gira mundial. Una noche llena de sus mejores éxitos.', 'concierto', 'Parque Norte', 'Carrera 55 #71-80', 'Medellín', '2026-05-15 20:00:00', 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=800', 'published', 1),
('Clásico paisa: Nacional vs Medellín', 'El partido más esperado del año. No te pierdas la emoción del fútbol colombiano.', 'deporte', 'Estadio Atanasio Girardot', 'Calle 48 #70-50', 'Medellín', '2026-04-20 17:00:00', 'https://images.unsplash.com/photo-1522778119026-d647f0565c6a?w=800', 'published', 1),
('Musical El Rey León', 'La experiencia teatral del año. Ven a disfrutar de la magia de Disney en vivo.', 'teatro', 'Teatro Convento de San Sebastián', 'Carrera 42 #57-30', 'Medellín', '2026-06-10 19:30:00', 'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800', 'published', 1),
('Festival Suroeste 2026', 'El festival de música alternativa más grande de la región. 3 días de música.', 'festival', 'Parque recreativo', 'Vereda km 12', 'Suroeste Antioqueño', '2026-07-20 14:00:00', 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800', 'published', 1),
('Comediante Jaime Sacsaw', 'Una noche de risas con el mejor humorista colombiano.', 'otro', 'Teatro Plaza', 'Carrera 48 #30-25', 'Medellín', '2026-05-02 21:00:00', 'https://images.unsplash.com/photo-1585699324551-f6c309eedeca?w=800', 'published', 0);

-- Add zones for first event (Concierto de Juanes)
INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES
(1, 'VIP', 'Acceso VIP con meet & greet', 350000, 50, 50, '#ffd700'),
(1, 'Platea', 'Asientos cercanos al escenario', 250000, 200, 200, '#e94560'),
(1, 'General', 'Acceso general al evento', 120000, 500, 500, '#00d9a5'),
(1, 'Preferencial', 'Asientos numerados zona media', 180000, 300, 300, '#6366f1');

-- Add zones for second event (Clásico paisa)
INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES
(2, 'Preferencia', 'Sector preferencial sur', 80000, 1000, 1000, '#ffd700'),
(2, 'Norte', 'Tribuna norte', 50000, 2000, 2000, '#e94560'),
(2, 'Oriente', 'Tribuna oriente', 45000, 1500, 1500, '#00d9a5'),
(2, 'Occidente', 'Tribuna occidente', 45000, 1500, 1500, '#6366f1'),
(2, 'General', 'Acceso general', 25000, 5000, 5000, '#a0a0b0');

-- Add zones for third event (Musical)
INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES
(3, 'Premium', 'Asientos orchestra primera fila', 400000, 50, 50, '#ffd700'),
(3, 'Platea', 'Platea baja', 280000, 150, 150, '#e94560'),
(3, 'Balcón', 'Balcón lateral', 180000, 200, 200, '#00d9a5');

-- Add zones for fourth event (Festival)
INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES
(4, 'VIP', 'Acceso VIP backstage', 500000, 100, 100, '#ffd700'),
(4, 'General 3 días', 'Acceso general 3 días', 350000, 2000, 2000, '#e94560'),
(4, 'Día específico', 'Acceso un día', 150000, 3000, 3000, '#00d9a5');

-- Add zones for fifth event (Comediante)
INSERT INTO event_zones (event_id, zone_name, description, price, capacity, available, color) VALUES
(5, 'VIP', 'Mesa vip con servicio', 150000, 30, 30, '#ffd700'),
(5, 'General', 'Asientos generales', 80000, 300, 300, '#e94560');