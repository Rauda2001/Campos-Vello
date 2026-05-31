-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-05-2026 a las 22:04:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `campo_vello`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `name` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `name`) VALUES
(8, 'Acondicionadores de Suelo'),
(9, 'Control Biológico'),
(5, 'Equipos y Herramientas'),
(4, 'Fertilizantes'),
(1, 'Herbicidas'),
(3, 'Insecticidas'),
(7, 'Insumos de Empaque'),
(2, 'Semillas'),
(10, 'Servicios de Asesoría'),
(6, 'Sistemas de Riego');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `nit` varchar(80) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `name`, `nit`, `address`, `phone`, `email`, `created_at`) VALUES
(3, 'Juan Alberto Pérez', '99999999-9', 'San Salvador, Centro', '7000-1111', 'juan.perez@ficticio.com', '2025-11-15 00:07:03'),
(4, 'María Elena Rodríguez', '88888888-8', 'Santa Ana, El Congo', '7111-2222', 'maria.rodriguez@ficticio.com', '2025-11-15 00:10:14'),
(5, 'Roberto Carlos Gómez', '77777777-7', 'San Miguel, Centro', '7222-3333', 'roberto.gomez@ficticio.com', '2025-11-15 01:50:00'),
(6, 'Estela Beatriz Martínez', '66666666-6', 'La Libertad, Santa Tecla', '7333-4444', 'estela.martinez@ficticio.com', '2025-11-24 03:38:35'),
(8, 'Ricardo Antonio López', '55555555-5', 'Ahuachapán, Ataco', '7444-5555', 'ricardo.lopez@ficticio.com', '2025-11-24 18:39:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `iva_amount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `user_id`, `client_id`, `subtotal`, `iva_amount`, `total`, `created_at`) VALUES
(1, NULL, NULL, 0.00, 0.00, 121.00, '2025-11-13 16:47:38'),
(2, NULL, NULL, 0.00, 0.00, 78.00, '2025-11-13 16:52:21'),
(3, NULL, 4, 0.00, 0.00, 96.00, '2025-11-15 00:19:54'),
(4, NULL, 5, 0.00, 0.00, 121.00, '2025-11-15 01:52:17'),
(5, NULL, 4, 0.00, 0.00, 25.00, '2025-11-17 03:51:45'),
(7, NULL, 5, 0.00, 0.00, 290.00, '2025-11-17 03:57:07'),
(8, NULL, 3, 0.00, 0.00, 0.00, '2025-11-17 23:38:49'),
(9, NULL, 3, 0.00, 0.00, 0.00, '2025-11-17 23:39:25'),
(10, NULL, 3, 0.00, 0.00, 25.00, '2025-11-17 23:39:36'),
(11, NULL, 4, 100.00, 13.00, 113.00, '2025-11-18 05:18:31'),
(12, 10, 4, 126.00, 16.38, 142.38, '2025-11-24 03:04:31'),
(13, NULL, 6, 12.00, 1.56, 13.56, '2026-05-30 20:02:28'),
(14, NULL, 5, 1788.00, 232.44, 2020.44, '2026-05-30 20:03:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 11, 1, 96.00),
(2, 1, 8, 1, 25.00),
(3, 2, 5, 2, 30.00),
(4, 2, 6, 1, 18.00),
(5, 3, 11, 1, 96.00),
(6, 4, 11, 1, 96.00),
(7, 4, 8, 1, 25.00),
(8, 5, 7, 1, 25.00),
(9, 7, 7, 2, 25.00),
(10, 7, 5, 8, 30.00),
(11, 10, 7, 1, 25.00),
(12, 11, 7, 4, 25.00),
(13, 12, 4, 1, 5.00),
(14, 12, 8, 1, 25.00),
(15, 12, 11, 1, 96.00),
(16, 13, 72, 1, 12.00),
(17, 14, 72, 149, 12.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `name`, `category_id`, `location`, `price`, `stock`) VALUES
(3, 'Maicillo', 2, 'Estante1', 12.50, 10),
(4, 'Guantes', 5, 'Estante 2', 5.00, 7),
(5, 'Semilla Maíz', 2, 'Estante 1', 30.00, 6),
(6, 'Sulfato de Amonio 45 KG', 4, 'Estante 3', 18.00, 3),
(7, 'Cipermetrina', 3, 'Estante 4', 25.00, 0),
(8, 'Gramoxone Galon', 1, 'Estante 5', 25.00, 2),
(10, 'Gramoxone litro', 1, 'estante 5', 15.00, 5),
(11, 'Bomba fumigadora de 16 litros Jacto', 5, 'Estante 2', 96.00, 9),
(12, 'Fertilizantes Sulfato de amonio 100KG', 4, 'Estante 3', 45.00, 8),
(13, 'RoundUp Concentrado 1L', 1, 'Estante 5A', 35.00, 40),
(14, 'Glifosato Líquido 5L', 1, 'Estante 5B', 150.00, 15),
(15, 'Herbicida Selectivo Maíz', 1, 'Estante 5C', 45.00, 22),
(16, 'Paraquat 1 Galón', 1, 'Estante 5A', 28.50, 30),
(17, '2,4-D Ácido', 1, 'Estante 5D', 18.00, 50),
(18, 'Machete XT', 1, 'Estante 5C', 60.00, 10),
(19, 'Fusilade DX', 1, 'Estante 5B', 95.00, 18),
(20, 'Diuron 80% WP', 1, 'Estante 5A', 12.00, 65),
(21, 'Clorimuron Etil', 1, 'Estante 5D', 22.00, 35),
(22, 'Aceite Adherente', 1, 'Estante 5B', 8.50, 70),
(23, 'Semilla de Frijol Rojo (Quintal)', 2, 'Estante 1A', 75.00, 80),
(24, 'Semilla de Tomate Híbrido (Sobre)', 2, 'Estante 1B', 5.50, 150),
(25, 'Semilla de Chile Verde (Sobre)', 2, 'Estante 1B', 6.00, 120),
(26, 'Semilla de Arroz (Quintal)', 2, 'Estante 1C', 68.00, 45),
(27, 'Semilla de Pasto Mombasa (KG)', 2, 'Estante 1A', 15.00, 90),
(28, 'Semilla de Maíz H-508', 2, 'Estante 1D', 32.00, 110),
(29, 'Semilla de Sorgo Forrajero', 2, 'Estante 1C', 25.00, 60),
(30, 'Semilla de Pepino Tipo Slicer', 2, 'Estante 1B', 7.50, 100),
(31, 'Semilla de Sandia Crimson Sweet', 2, 'Estante 1D', 10.00, 85),
(32, 'Semilla de Cebolla Amarilla', 2, 'Estante 1A', 9.00, 130),
(33, 'Clorpirifos 48% EC 1L', 3, 'Estante 4A', 40.00, 35),
(34, 'Lambda-Cihalotrina 1L', 3, 'Estante 4B', 55.00, 25),
(35, 'Imidacloprid 70% WP', 3, 'Estante 4C', 30.00, 40),
(36, 'Dimetoato 1 Galón', 3, 'Estante 4A', 65.00, 15),
(37, 'Malatión Concentrado', 3, 'Estante 4B', 20.00, 55),
(38, 'Thiamethoxam Granular', 3, 'Estante 4C', 48.00, 28),
(39, 'Acephate Polvo Soluble', 3, 'Estante 4A', 38.00, 33),
(40, 'Spinosad Orgánico', 3, 'Estante 4D', 75.00, 12),
(41, 'Permetrina 50% EC', 3, 'Estante 4D', 24.00, 48),
(42, 'Fipronil Gel', 3, 'Estante 4C', 15.00, 60),
(43, 'Urea 46% (50 KG)', 4, 'Estante 3A', 38.00, 100),
(44, 'Fosfato Diamónico DAP (50 KG)', 4, 'Estante 3B', 52.00, 75),
(45, 'Triple 15 (50 KG)', 4, 'Estante 3C', 45.00, 90),
(46, 'Nitrofoska 25 KG', 4, 'Estante 3A', 30.00, 120),
(47, 'Sulfato de Potasio 50 KG', 4, 'Estante 3B', 42.00, 65),
(48, 'Cal Agrícola (Bolsa)', 4, 'Estante 3D', 8.00, 200),
(49, 'Fertilizante Foliar 1 Litro', 4, 'Estante 3C', 18.00, 85),
(50, 'Abono Orgánico Compostado 10 KG', 4, 'Estante 3A', 12.00, 150),
(51, 'Micronutrientes Quelatados', 4, 'Estante 3B', 25.00, 40),
(52, 'Cloruro de Potasio (MOP) 50 KG', 4, 'Estante 3D', 49.00, 55),
(53, 'Motosierra Gasolina 18\"', 5, 'Estante 2A', 250.00, 8),
(54, 'Aspersora Manual 5 Litros', 5, 'Estante 2B', 15.00, 70),
(55, 'Pala Cuadrada Reforzada', 5, 'Estante 2C', 10.50, 95),
(56, 'Rastrillo Metálico 16 Dientes', 5, 'Estante 2A', 8.50, 110),
(57, 'Manguera de Riego 100 Metros', 5, 'Estante 2B', 45.00, 30),
(58, 'Tijeras de Poda Profesional', 5, 'Estante 2D', 12.50, 80),
(59, 'Bomba de Agua Sumergible 1HP', 5, 'Estante 2C', 180.00, 15),
(60, 'Generador Eléctrico Portátil', 5, 'Estante 2A', 450.00, 5),
(61, 'Carretilla Reforzada 6 Pies', 5, 'Estante 2D', 65.00, 20),
(62, 'Cuerda de Nylon 50 Metros', 5, 'Estante 2B', 7.00, 130),
(63, 'Kit de Seguridad Personal', 5, 'Estante 2C', 20.00, 40),
(64, 'Medidor de pH Digital', 5, 'Estante 2D', 90.00, 18),
(65, 'Cinta de Riego por Goteo (1000m)', 6, 'Almacén Riego', 85.00, 15),
(66, 'Conector Codo para Tubería 1/2\"', 6, 'Caja Accesorios', 1.50, 200),
(67, 'Malla Sombreo 50% (50m)', 7, 'Zona Empaque', 75.00, 10),
(68, 'Cajas de Cartón para Hortalizas (paq 100)', 7, 'Zona Empaque', 35.00, 40),
(69, 'Turba Musgo de Sphagnum (200L)', 8, 'Estante Aditivos', 60.00, 25),
(70, 'Vermiculita Grado Fino (5 KG)', 8, 'Estante Aditivos', 18.00, 50),
(71, 'Trichoderma Harzianum (polvo 500g)', 9, 'Refrigerador Bio', 45.00, 30),
(72, 'Trampas Pegajosas Amarillas (paq 10)', 9, 'Estante Biocontrol', 12.00, 0),
(73, 'Asesoría Técnica Agrícola (Hora)', 10, 'Oficina Principal', 50.00, 0),
(74, 'Análisis de Suelo Completo (Muestra)', 10, 'Laboratorio', 95.00, 0),
(75, 'Capacitación en Manejo de Plagas (4 Horas)', 10, 'Sala de Juntas', 120.00, 0),
(76, 'Diseño de Plan de Fertilización (Finca)', 10, 'Oficina Agronomía', 150.00, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','cajero') DEFAULT 'cajero',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `role`, `created_at`) VALUES
(9, 'Administrador', 'Admin@gmail.com', '$2y$10$crYIfsaLcgz6roYtPPKYouErjxaISFDSm4NsEzq9vYXLVDT0PrQLm', 'admin', '2025-11-24 01:35:14'),
(10, 'Cajero', 'Cajero@gmail.com', '$2y$10$327iaHq.eHNKoRulG4kPIe.MK62Hx8IT7IrJxveniCivn47BK6TcO', 'cajero', '2025-11-24 02:56:29');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indices de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `facturas_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
