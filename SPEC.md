# MisterBoleta - Sistema de Venta de Boletas

## 1. Project Overview

- **Project Name**: MisterBoleta
- **Type**: Web Application (PHP + MySQL)
- **Core Functionality**: Plataforma de venta de boletas para eventos (conciertos, deportes, teatro)similar a Ticketmaster
- **Target Users**: Organizadores de eventos, compradores de boletas, administradores

## 2. Technology Stack

- **Backend**: PHP 8.x con PDO
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **Payment**: Integración flexible (MercadoPago, Nequi, Daviplata, Bbva/Breeze)
- **Server**: orangehost.com (compatible con PHP 8.x y MySQL)

## 3. UI/UX Specification

### Color Palette
- **Primary**: `#1a1a2e` (Dark Navy)
- **Secondary**: `#16213e` (Deep Blue)
- **Accent**: `#e94560` (Vibrant Red/Coral)
- **Success**: `#00d9a5` (Teal Green)
- **Warning**: `#ffc107` (Amber)
- **Background**: `#0f0f23` (Dark Background)
- **Card Background**: `#1f1f3a` (Dark Card)
- **Text Primary**: `#ffffff`
- **Text Secondary**: `#a0a0b0`

### Typography
- **Headings**: 'Poppins', sans-serif (600, 700)
- **Body**: 'Inter', sans-serif (400, 500)
- **Font Sizes**:
  - H1: 2.5rem
  - H2: 2rem
  - H3: 1.5rem
  - Body: 1rem
  - Small: 0.875rem

### Layout Structure
- **Header**: Logo, navegación, búsqueda, usuario/carrito
- **Hero**: Carrusel de eventos destacados
- **Content**: Grid de eventos, filtros
- **Footer**: Links, contacto, redes sociales

### Responsive Breakpoints
- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: > 1024px

## 4. Database Schema

### Tablas

```sql
-- Usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Eventos
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category ENUM('concierto', 'deporte', 'teatro', 'festival', 'otro') DEFAULT 'otro',
    venue VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL,
    event_date DATETIME NOT NULL,
    image VARCHAR(500),
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Zonas/Precios
CREATE TABLE event_zones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    zone_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    available INT NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Boletas
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code VARCHAR(20) UNIQUE NOT NULL,
    event_id INT NOT NULL,
    zone_id INT NOT NULL,
    user_id INT,
    status ENUM('available', 'reserved', 'sold', 'used', 'cancelled') DEFAULT 'available',
    purchase_date TIMESTAMP NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES event_zones(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Órdenes
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Orden items
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    ticket_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);
```

## 5. Functionality Specification

### Frontend (Público)
1. **Página Principal**: Eventos destacados, búsqueda, categorías
2. **Catálogo de Eventos**: Filtrado por categoría, fecha, ciudad
3. **Detalle del Evento**: Información, selección de zona, compra
4. **Carrito de Compras**: Resumen, proceder al pago
5. **Checkout**: Datos del comprador, método de pago

### Panel de Usuario
1. **Registro/Login**: Autenticación con email/contraseña
2. **Mi Perfil**: Editar datos, cambiar contraseña
3. **Mis Boletas**: Historial de compras, descargar/view boletas
4. **Mis Órdenes**: Estado de pedidos

### Panel de Administrador
1. **Dashboard**: Estadísticas, ventas recientes
2. **Gestión de Eventos**: Crear, editar, publicar, eliminar
3. **Gestión de Zonas**: Agregar zonas y precios
4. **Gestión de Órdenes**: Ver pedidos, cambiar estado
5. **Reportes**: Ventas por evento, ingresos

## 6. Payment Integration

Sistema de pagos flexible compatible con:
- **MercadoPago** (API de MercadoPago Colombia)
- **Nequi** (Transferencias Nequi)
- **Daviplata** (Transferencias Daviplata)
- **Bbva/Breeze** (Pagos con Bbva)

Implementación: Gateway de pagos abstracto que permite configurar el método preferido.

## 7. File Structure

```
/misterboleta
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── public/
│   ├── index.php
│   ├── events.php
│   ├── event-detail.php
│   ├── cart.php
│   ├── checkout.php
│   ├── payment.php
├── user/
│   ├── login.php
│   ├── register.php
│   ├── profile.php
│   ├── my-tickets.php
│   └── orders.php
├── admin/
│   ├── index.php
│   ├── events.php
│   ├── add-event.php
│   ├── orders.php
│   └── zones.php
├── css/
│   └── style.css
├── js/
│   └── main.js
└── sql/
    └── database.sql
```

## 8. Acceptance Criteria

- [ ] Usuario puede navegar eventos por categoría
- [ ] Usuario puede buscar eventos por nombre/fecha
- [ ] Usuario puede seleccionar zona y cantidad de boletas
- [ ] Usuario puede agregar al carrito y procesar pago
- [ ] Usuario recibe código de boleta único
- [ ] Admin puede crear/editar eventos con múltiples zonas
- [ ] Admin puede ver reportes de ventas
- [ ] Diseño responsivo funciona en móvil y desktop
- [ ] Base de datos almacenada correctamente
- [ ] Sistema listo para deploy en orangehost.com