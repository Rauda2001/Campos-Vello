-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2026 a las 23:09:53
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

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
(8, 'Ricardo Antonio López', '55555555-5', 'Ahuachapán, Ataco', '7444-5555', 'ricardo.lopez@ficticio.com', '2025-11-24 18:39:03'),
(9, 'Clientes Varios', '06202372-1', 'metalio', '0935-3453', 'dany1542001@gmail.com', '2026-07-03 18:02:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `tipo_documento` enum('FCF','CCF') NOT NULL DEFAULT 'FCF',
  `num_documento` int(11) NOT NULL DEFAULT 1,
  `metodo_pago` varchar(20) NOT NULL DEFAULT 'Efectivo',
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `iva_amount` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`id`, `user_id`, `client_id`, `tipo_documento`, `num_documento`, `metodo_pago`, `subtotal`, `iva_amount`, `total`, `created_at`) VALUES
(1, NULL, NULL, 'FCF', 1, 'Efectivo', 0.00, 0.00, 121.00, '2025-11-13 16:47:38'),
(2, NULL, NULL, 'FCF', 2, 'Efectivo', 0.00, 0.00, 78.00, '2025-11-13 16:52:21'),
(3, NULL, 4, 'FCF', 3, 'Efectivo', 0.00, 0.00, 96.00, '2025-11-15 00:19:54'),
(4, NULL, 5, 'FCF', 4, 'Efectivo', 0.00, 0.00, 121.00, '2025-11-15 01:52:17'),
(5, NULL, 4, 'FCF', 5, 'Efectivo', 0.00, 0.00, 25.00, '2025-11-17 03:51:45'),
(7, NULL, 5, 'FCF', 7, 'Efectivo', 0.00, 0.00, 290.00, '2025-11-17 03:57:07'),
(8, NULL, 3, 'FCF', 8, 'Efectivo', 0.00, 0.00, 0.00, '2025-11-17 23:38:49'),
(9, NULL, 3, 'FCF', 9, 'Efectivo', 0.00, 0.00, 0.00, '2025-11-17 23:39:25'),
(10, NULL, 3, 'FCF', 10, 'Efectivo', 0.00, 0.00, 25.00, '2025-11-17 23:39:36'),
(11, NULL, 4, 'FCF', 11, 'Efectivo', 100.00, 13.00, 113.00, '2025-11-18 05:18:31'),
(12, 10, 4, 'FCF', 12, 'Efectivo', 126.00, 16.38, 142.38, '2025-11-24 03:04:31'),
(13, NULL, 6, 'FCF', 13, 'Efectivo', 12.00, 1.56, 13.56, '2026-05-30 20:02:28'),
(14, NULL, 5, 'FCF', 14, 'Efectivo', 1788.00, 232.44, 2020.44, '2026-05-30 20:03:50'),
(15, NULL, 6, 'FCF', 15, 'Efectivo', 45.00, 5.85, 50.85, '2026-05-30 21:37:38'),
(16, NULL, 4, 'FCF', 16, 'Efectivo', 45.00, 5.85, 50.85, '2026-05-30 21:38:32'),
(17, NULL, 4, 'FCF', 17, 'Efectivo', 45.00, 5.85, 50.85, '2026-05-30 21:47:34'),
(18, NULL, 8, 'FCF', 18, 'Efectivo', 45.00, 5.85, 50.85, '2026-05-30 21:52:48'),
(19, 10, 5, 'FCF', 19, 'Efectivo', 45.00, 5.85, 50.85, '2026-05-30 21:56:10'),
(20, NULL, 6, 'FCF', 20, 'Efectivo', 12.50, 1.63, 14.13, '2026-05-30 22:01:49'),
(21, NULL, 6, 'FCF', 21, 'Efectivo', 85.00, 11.05, 96.05, '2026-05-30 22:13:48'),
(22, 10, 5, 'FCF', 22, 'Efectivo', 60.00, 7.80, 67.80, '2026-05-31 04:00:20'),
(23, 10, 5, 'FCF', 23, 'Efectivo', 35.00, 4.55, 39.55, '2026-06-02 04:15:43'),
(24, 10, 6, 'FCF', 24, 'Efectivo', 1.50, 0.20, 1.70, '2026-06-02 04:19:18'),
(25, 10, 6, 'FCF', 25, 'Efectivo', 90.00, 11.70, 101.70, '2026-06-02 04:20:12'),
(26, 10, 6, 'FCF', 26, 'Efectivo', 45.00, 5.85, 50.85, '2026-06-02 04:20:37'),
(27, 10, 6, 'FCF', 27, 'Tarjeta', 45.00, 5.85, 50.85, '2026-06-02 04:23:57'),
(28, 10, 5, 'FCF', 28, 'Tarjeta', 225.00, 29.25, 254.25, '2026-06-02 04:24:53'),
(29, 10, 6, 'FCF', 29, 'Tarjeta', 2183.50, 283.86, 2467.36, '2026-06-02 04:54:09'),
(30, 10, 3, 'FCF', 30, 'Efectivo', 106.00, 13.78, 119.78, '2026-06-02 05:02:39'),
(31, 10, 6, 'FCF', 31, 'Tarjeta', 111.00, 14.43, 125.43, '2026-06-02 05:06:09'),
(32, 10, 6, 'FCF', 32, 'Efectivo', 90.00, 11.70, 101.70, '2026-07-03 14:11:50'),
(33, 10, 9, 'FCF', 33, 'Efectivo', 15.00, 1.95, 16.95, '2026-07-03 18:54:35'),
(34, 10, 9, 'FCF', 34, 'Efectivo', 18.00, 0.00, 18.00, '2026-07-06 19:19:07'),
(35, 10, 9, 'CCF', 1, 'Efectivo', 105.00, 13.65, 118.65, '2026-07-06 19:50:05'),
(36, 10, 9, 'CCF', 2, 'Efectivo', 18.00, 2.34, 20.34, '2026-07-06 20:10:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL,
  `correlativo` varchar(20) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `orden_compra` varchar(50) DEFAULT NULL,
  `numero_factura_proveedor` varchar(50) DEFAULT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `estado` enum('Completado','Anulado') DEFAULT 'Completado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingreso_detalles`
--

CREATE TABLE `ingreso_detalles` (
  `id` int(11) NOT NULL,
  `id_ingreso` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta_actualizado` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(17, 14, 72, 149, 12.00),
(18, 15, 71, 1, 45.00),
(19, 16, 71, 1, 45.00),
(20, 17, 71, 1, 45.00),
(21, 18, 71, 1, 45.00),
(22, 19, 71, 1, 45.00),
(23, 20, 58, 1, 12.50),
(24, 21, 65, 1, 85.00),
(25, 22, 69, 1, 60.00),
(26, 23, 68, 1, 35.00),
(27, 24, 66, 1, 1.50),
(28, 25, 64, 1, 90.00),
(29, 26, 71, 1, 45.00),
(30, 27, 71, 1, 45.00),
(31, 28, 71, 5, 45.00),
(32, 29, 66, 199, 1.50),
(33, 29, 61, 29, 65.00),
(34, 30, 50, 1, 12.00),
(35, 30, 52, 1, 49.00),
(36, 30, 71, 1, 45.00),
(37, 31, 69, 1, 60.00),
(38, 31, 47, 1, 42.00),
(39, 31, 32, 1, 9.00),
(40, 32, 71, 1, 45.00),
(41, 32, 71, 1, 45.00),
(42, 33, 30, 2, 7.50),
(43, 34, 70, 1, 18.00),
(44, 35, 71, 1, 45.00),
(45, 35, 69, 1, 60.00),
(46, 36, 70, 1, 18.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `name`, `category_id`, `location`, `price`, `stock`) VALUES
(3, 'PROD-0003', 'Maicillo', 2, 'Estante1', 12.50, 10),
(4, 'PROD-0004', 'Guantes', 5, 'Estante 2', 5.00, 7),
(5, 'PROD-0005', 'Semilla Maíz', 2, 'Estante 1', 30.00, 6),
(6, 'PROD-0006', 'Sulfato de Amonio 45 KG', 4, 'Estante 3', 18.00, 3),
(7, 'PROD-0007', 'Cipermetrina', 3, 'Estante 4', 25.00, 0),
(8, 'PROD-0008', 'Gramoxone Galon', 1, 'Estante 5', 25.00, 2),
(10, 'PROD-0010', 'Gramoxone litro', 1, 'estante 5', 15.00, 5),
(11, 'PROD-0011', 'Bomba fumigadora de 16 litros Jacto', 5, 'Estante 2', 96.00, 9),
(12, 'PROD-0012', 'Fertilizantes Sulfato de amonio 100KG', 4, 'Estante 3', 45.00, 8),
(13, 'PROD-0013', 'RoundUp Concentrado 1L', 1, 'Estante 5A', 35.00, 40),
(14, 'PROD-0014', 'Glifosato Líquido 5L', 1, 'Estante 5B', 150.00, 15),
(15, 'PROD-0015', 'Herbicida Selectivo Maíz', 1, 'Estante 5C', 45.00, 22),
(16, 'PROD-0016', 'Paraquat 1 Galón', 1, 'Estante 5A', 28.50, 30),
(17, 'PROD-0017', '2,4-D Ácido', 1, 'Estante 5D', 18.00, 50),
(18, 'PROD-0018', 'Machete XT', 1, 'Estante 5C', 60.00, 10),
(19, 'PROD-0019', 'Fusilade DX', 1, 'Estante 5B', 95.00, 18),
(20, 'PROD-0020', 'Diuron 80% WP', 1, 'Estante 5A', 12.00, 65),
(21, 'PROD-0021', 'Clorimuron Etil', 1, 'Estante 5D', 22.00, 35),
(22, 'PROD-0022', 'Aceite Adherente', 1, 'Estante 5B', 8.50, 70),
(23, 'PROD-0023', 'Semilla de Frijol Rojo (Quintal)', 2, 'Estante 1A', 75.00, 80),
(24, 'PROD-0024', 'Semilla de Tomate Híbrido (Sobre)', 2, 'Estante 1B', 5.50, 150),
(25, 'PROD-0025', 'Semilla de Chile Verde (Sobre)', 2, 'Estante 1B', 6.00, 120),
(26, 'PROD-0026', 'Semilla de Arroz (Quintal)', 2, 'Estante 1C', 68.00, 45),
(27, 'PROD-0027', 'Semilla de Pasto Mombasa (KG)', 2, 'Estante 1A', 15.00, 90),
(28, 'PROD-0028', 'Semilla de Maíz H-508', 2, 'Estante 1D', 32.00, 110),
(29, 'PROD-0029', 'Semilla de Sorgo Forrajero', 2, 'Estante 1C', 25.00, 60),
(30, 'PROD-0030', 'Semilla de Pepino Tipo Slicer', 2, 'Estante 1B', 7.50, 98),
(31, 'PROD-0031', 'Semilla de Sandia Crimson Sweet', 2, 'Estante 1D', 10.00, 85),
(32, 'PROD-0032', 'Semilla de Cebolla Amarilla', 2, 'Estante 1A', 9.00, 129),
(33, 'PROD-0033', 'Clorpirifos 48% EC 1L', 3, 'Estante 4A', 40.00, 35),
(34, 'PROD-0034', 'Lambda-Cihalotrina 1L', 3, 'Estante 4B', 55.00, 25),
(35, 'PROD-0035', 'Imidacloprid 70% WP', 3, 'Estante 4C', 30.00, 40),
(36, 'PROD-0036', 'Dimetoato 1 Galón', 3, 'Estante 4A', 65.00, 15),
(37, 'PROD-0037', 'Malatión Concentrado', 3, 'Estante 4B', 20.00, 55),
(38, 'PROD-0038', 'Thiamethoxam Granular', 3, 'Estante 4C', 48.00, 28),
(39, 'PROD-0039', 'Acephate Polvo Soluble', 3, 'Estante 4A', 38.00, 33),
(40, 'PROD-0040', 'Spinosad Orgánico', 3, 'Estante 4D', 75.00, 12),
(41, 'PROD-0041', 'Permetrina 50% EC', 3, 'Estante 4D', 24.00, 48),
(42, 'PROD-0042', 'Fipronil Gel', 3, 'Estante 4C', 15.00, 60),
(43, 'PROD-0043', 'Urea 46% (50 KG)', 4, 'Estante 3A', 38.00, 100),
(44, 'PROD-0044', 'Fosfato Diamónico DAP (50 KG)', 4, 'Estante 3B', 52.00, 75),
(45, 'PROD-0045', 'Triple 15 (50 KG)', 4, 'Estante 3C', 45.00, 90),
(46, 'PROD-0046', 'Nitrofoska 25 KG', 4, 'Estante 3A', 30.00, 120),
(47, 'PROD-0047', 'Sulfato de Potasio 50 KG', 4, 'Estante 3B', 42.00, 64),
(48, 'PROD-0048', 'Cal Agrícola (Bolsa)', 4, 'Estante 3D', 8.00, 200),
(49, 'PROD-0049', 'Fertilizante Foliar 1 Litro', 4, 'Estante 3C', 18.00, 85),
(50, 'PROD-0050', 'Abono Orgánico Compostado 10 KG', 4, 'Estante 3A', 12.00, 149),
(51, 'PROD-0051', 'Micronutrientes Quelatados', 4, 'Estante 3B', 25.00, 40),
(52, 'PROD-0052', 'Cloruro de Potasio (MOP) 50 KG', 4, 'Estante 3D', 49.00, 54),
(53, 'PROD-0053', 'Motosierra Gasolina 18\"', 5, 'Estante 2A', 250.00, 8),
(54, 'PROD-0054', 'Aspersora Manual 5 Litros', 5, 'Estante 2B', 15.00, 70),
(55, 'PROD-0055', 'Pala Cuadrada Reforzada', 5, 'Estante 2C', 10.50, 95),
(56, 'PROD-0056', 'Rastrillo Metálico 16 Dientes', 5, 'Estante 2A', 8.50, 110),
(57, 'PROD-0057', 'Manguera de Riego 100 Metros', 5, 'Estante 2B', 45.00, 30),
(58, 'PROD-0058', 'Tijeras de Poda Profesional', 5, 'Estante 2D', 12.50, 79),
(59, 'PROD-0059', 'Bomba de Agua Sumergible 1HP', 5, 'Estante 2C', 180.00, 15),
(60, 'PROD-0060', 'Generador Eléctrico Portátil', 5, 'Estante 2A', 450.00, 5),
(61, 'PROD-0061', 'Carretilla Reforzada 6 Pies', 5, 'Estante 2D', 65.00, -9),
(62, 'PROD-0062', 'Cuerda de Nylon 50 Metros', 5, 'Estante 2B', 7.00, 130),
(63, 'PROD-0063', 'Kit de Seguridad Personal', 5, 'Estante 2C', 20.00, 40),
(64, 'PROD-0064', 'Medidor de pH Digital', 5, 'Estante 2D', 90.00, 17),
(65, 'PROD-0065', 'Cinta de Riego por Goteo (1000m)', 6, 'Almacén Riego', 85.00, 14),
(66, 'PROD-0066', 'Conector Codo para Tubería 1/2\"', 6, 'Caja Accesorios', 1.50, 0),
(67, 'PROD-0067', 'Malla Sombreo 50% (50m)', 7, 'Zona Empaque', 75.00, 10),
(68, 'PROD-0068', 'Cajas de Cartón para Hortalizas (paq 100)', 7, 'Zona Empaque', 35.00, 39),
(69, 'PROD-0069', 'Turba Musgo de Sphagnum (200L)', 8, 'Estante Aditivos', 60.00, 22),
(70, 'PROD-0070', 'Vermiculita Grado Fino (5 KG)', 8, 'Estante Aditivos', 18.00, 48),
(71, 'PROD-0071', 'Trichoderma Harzianum (polvo 500g)', 9, 'Refrigerador Bio', 45.00, 14),
(72, 'PROD-0072', 'Trampas Pegajosas Amarillas (paq 10)', 9, 'Estante Biocontrol', 12.00, 0),
(73, 'PROD-0073', 'Asesoría Técnica Agrícola (Hora)', 10, 'Oficina Principal', 50.00, 0),
(74, 'PROD-0074', 'Análisis de Suelo Completo (Muestra)', 10, 'Laboratorio', 95.00, 0),
(75, 'PROD-0075', 'Capacitación en Manejo de Plagas (4 Horas)', 10, 'Sala de Juntas', 120.00, 0),
(76, 'PROD-0076', 'Diseño de Plan de Fertilización (Finca)', 10, 'Oficina Agronomía', 150.00, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `telefono`, `email`, `direccion`, `created_at`) VALUES
(1, 'AgroSuministros del Norte', '2233-4455', 'contacto@agrosuministros.com', 'Av. Principal #123', '2026-10-06 20:16:26'),
(2, 'Distribuidora Campo Vello', '2288-9900', 'ventas@campovello.com', 'Zona Industrial Lote 4', '2026-10-06 20:16:26'),
(3, 'Fertilizantes y Semillas S.A.', '2500-1122', 'info@fertisemillas.com', 'Carretera Central Km 12', '2026-10-06 20:16:26');

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
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correlativo` (`correlativo`);

--
-- Indices de la tabla `ingreso_detalles`
--
ALTER TABLE `ingreso_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ingreso` (`id_ingreso`);

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
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ingreso_detalles`
--
ALTER TABLE `ingreso_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- Filtros para la tabla `ingreso_detalles`
--
ALTER TABLE `ingreso_detalles`
  ADD CONSTRAINT `ingreso_detalles_ibfk_1` FOREIGN KEY (`id_ingreso`) REFERENCES `ingresos` (`id`) ON DELETE CASCADE;

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
