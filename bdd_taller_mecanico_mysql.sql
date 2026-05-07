-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3307
-- Tiempo de generación: 11-11-2025 a las 21:54:32
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
-- Base de datos: `bdd_taller_mecanico_mysql`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `cliente_DNI` varchar(10) NOT NULL,
  `cliente_contrasena` varchar(255) NOT NULL,
  `cliente_nombre` varchar(50) NOT NULL,
  `cliente_direccion` varchar(50) DEFAULT NULL,
  `cliente_localidad` varchar(15) DEFAULT NULL,
  `cliente_telefono` varchar(15) DEFAULT NULL,
  `cliente_email` varchar(255) NOT NULL,
  `token_recuperacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`cliente_DNI`, `cliente_contrasena`, `cliente_nombre`, `cliente_direccion`, `cliente_localidad`, `cliente_telefono`, `cliente_email`, `token_recuperacion`) VALUES
('11179113', '$2y$10$XZ4MO8mXG4/GO6YTtfbvVO0lwE/BqX2DkIuC97ESE2f6N/b3O1K/S', 'Ana Maria Gomes Rosa', 'Portela 1136', 'CABA', '1166890208', 'anamaria396424@gmail.com', NULL),
('18762965', '$2y$10$tYDuZiiScaJgGqJE8SLtguuhgyIGYD8kcrx.gUkeSL/rA98G6TXaO', 'Laura Martínez', NULL, NULL, '3515555678', 'christian.caprarulo@gmail.com', NULL),
('19786413', '$2y$10$xzLTMTIzZHSbk0Fsq0/HUeaoHAGzfWLJ5duC5X96k3I9IyFZ2Ui6i', 'María García', 'Ascasubi 1342', 'Buenos Aires', '1167439855', '', NULL),
('22870111', '$2y$10$Uz1AKpqPJ/07.s7IHOokq.1klFUIMmj1YpN4/Sxz3ZkAmq92oGoVi', 'Juan Villalba', 'Monroe 87', 'Lanus', '1137081077', '', NULL),
('27552991', '$2y$10$TPPOV5Q7xnNTsR3PwMamTe68VNo7eBQkfvRV.1zESaCYNO5BGguZ2', 'Alejandro López', NULL, NULL, NULL, '', NULL),
('28090318', '$2y$10$4yPA4IJbyFidZn5fybqYgeI6L1/ByLyna1A.Kgljndsw/fqhz8u6e', 'Miguel Martinez', 'Pedro Lozano 452', 'Caba', '1145269854', '', NULL),
('30164750', '$2y$10$HObPHxeBfMcrhRmwpd3A5On8.vWBy56uZnRonzI4MCZMrRkdbWNR6', 'Maria Sotelo', 'Tandil 6940', 'Caba', '1122083320', '', NULL),
('30700247', '$2y$10$ho/z/4IPCiRt7yQlGPZnU.IB9YgPFve6PqSvcO4Jspm/wUYDv1/JS', 'Christian Caprarulo', 'Portela 1136', 'CABA', '01157172522', 'christian.caprarulo@gmail.com', 'b6c493c0d6996a23f1b1c6243ed1ea02c908eec989fbf62200a4efefe94086af'),
('32489632', '$2y$10$isL6a3BO9M9Afhqpn84LGuZH0DfLR0jFYsVHeDa2sB4I1KPhigem.', 'Juan Pérez', 'San Martin 514', 'Caba', '1167349281', '', NULL),
('32690367', '$2y$10$WvQLICi2WXZ/kOVx5yhtke3SSOwafNFq1KdP/mUdx0u8lg0Hzh9GG', 'Adrian Favio Caprarulo', 'Jose Ingenieros', 'San Justo', '1157316427', 'adrian.caprarulo@gmail.com', NULL),
('41298533', '$2y$10$BkSZA7Oo/WbXyBgbSgU5LOgXvoB1LLbv6qp./sPf6vr1PN5xeFVnq', 'Carlos Rodríguez', NULL, 'Mendoza', NULL, '', NULL),
('43796532', '$2y$10$ZT.y6r.9A6mqqNOvX4w1/.JCD9VQQrfc.qnDp2RItDSelpkxqmxU6', 'Sergio Benitez', 'Av.Saenz 708', 'Caba', '1147552201', '', NULL),
('44671150', '$2y$10$puw7dci8b2nvSEyCSSTi2udf7WJLdcWB.Fv/advhZVFlmFuNFUN5y', 'Sofia Duarte Villan', 'Homero 919', 'CABA', '1135932021', 'sofiduvi@gmail.com', NULL),
('47651867', '$2y$10$ujHC4B1Rac0BNRT3vM5lteCFosundlBVPW/734vNucl3ebDAwTO92', 'Martin Damian Caprarulo', 'Portela 1136', 'CABA', '1157379981', 'martin.d.caprarulo@gmail.com', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleados`
--

CREATE TABLE `empleados` (
  `empleado_DNI` varchar(10) NOT NULL,
  `empleado_contrasena` varchar(255) NOT NULL,
  `empleado_nombre` varchar(50) NOT NULL,
  `empleado_roll` varchar(255) NOT NULL,
  `empleado_email` text NOT NULL,
  `token_recuperacion` varchar(255) DEFAULT NULL,
  `empleado_direccion` varchar(50) DEFAULT NULL,
  `empleado_localidad` varchar(15) DEFAULT NULL,
  `empleado_telefono` varchar(15) DEFAULT NULL,
  `empleado_habilitado` tinyint(1) NOT NULL DEFAULT 1,
  `empleado_estado` enum('disponible','no_disponible','licencia','baja') NOT NULL DEFAULT 'disponible',
  `licencia_desde` date DEFAULT NULL,
  `licencia_hasta` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleados`
--

INSERT INTO `empleados` (`empleado_DNI`, `empleado_contrasena`, `empleado_nombre`, `empleado_roll`, `empleado_email`, `token_recuperacion`, `empleado_direccion`, `empleado_localidad`, `empleado_telefono`, `empleado_habilitado`, `empleado_estado`, `licencia_desde`, `licencia_hasta`) VALUES
('08326014', '$2y$10$Ub8aB4gEwxLT0BkUhoS2TOS9tmkqrK2uNe6.46B7jS8JvXbf9FBky', 'Norberto Caprarulo', 'mecanico', 'norberto.caprarulo@gmail.com', NULL, 'J. Ingenieros 3964', 'San Justo', '1160102685', 1, 'disponible', NULL, NULL),
('24874723', '$2y$10$D4u9GL/1rhsdVbWBLeyv2urFR9/OdEL89KcO028uFKFxJ3bpuxOt6', 'Stella Brzostowski', 'recepcionista', '0', NULL, 'Pedro M. Obligado 1489', 'Laferrere', NULL, 1, 'disponible', NULL, NULL),
('30700247', '$2y$10$TZQtjVmYfrIcqxqU/EkeSe7nPgEkIz25dKNmRRs4idnF86HLx162.', 'Christian Caprarulo', 'mecanico', 'christian.caprarulo@gmail.com', 'c8b9d38cdfcadc04e2b96decdc25b0b25ce5430f5ca4a6ed3fb9ca75965ab649', 'Portela 1136', 'CABA', '1157172522', 1, 'disponible', NULL, NULL),
('32690365', '$2y$10$FcQx59RPzAEcsicewP.00.5jrIUcRBv5cjzjphZOE6X0pxweaWxCG', 'Adrián Caprarulo', 'mecanico', '0', NULL, 'J. Ingenieros 3964', 'San Justo', '1160152685', 1, 'disponible', NULL, NULL),
('44671150', '$2y$10$8I/M3RbRn.FUuovkMkvJO.WGcLxWXaq689Xg5xQ.AU73w7INxmSEK', 'Sofia Duarte', 'recepcionista', 'sofiduvi@gmail.com', '85d6877e5029ba391ce5d2a72d48151ab00a14a36f9d1830f0abd8672f8424eb', 'Homero 919', 'CABA', '', 1, 'disponible', NULL, NULL),
('47651867', '$2y$10$nyqZ/3LasLxH/FfKUhp5MeBSACkABS.mF12eZ5zJoSVhzKqrRWm9u', 'Martin Caprarulo', 'mecanico', 'martin.d.caprarulo@gmail.com', NULL, 'Portela 1136', 'CABA', '1157379981', 1, 'disponible', NULL, NULL),
('57109916', '$2y$10$1oGU9IYaw2YGNsyM1D47jeLiTCzzs0qY1QkIoj3wJt607keSbU3pa', 'Dante Caprarulo', 'gerente', '0', NULL, 'Portela 1136', 'CABA', '1178521609', 1, 'disponible', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `facturas`
--

CREATE TABLE `facturas` (
  `factura_id` int(11) NOT NULL,
  `tipo` enum('A','B','C') NOT NULL,
  `nro_comprobante` int(11) NOT NULL,
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
  `orden_numero` int(11) NOT NULL,
  `servicio_codigo` varchar(5) NOT NULL,
  `cliente_dni` varchar(10) NOT NULL,
  `vehiculo_patente` varchar(10) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `pdf_nombre` varchar(255) DEFAULT NULL,
  `email_destino` varchar(255) DEFAULT NULL,
  `email_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `empleado_emisor` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `facturas`
--

INSERT INTO `facturas` (`factura_id`, `tipo`, `nro_comprobante`, `fecha_emision`, `orden_numero`, `servicio_codigo`, `cliente_dni`, `vehiculo_patente`, `total`, `pdf_nombre`, `email_destino`, `email_enviado`, `empleado_emisor`) VALUES
(1, 'C', 1, '2025-10-09 20:44:58', 13, 'D001', '30700247', 'GCR891', 5500.00, 'Factura_C_00000001_Orden_13.pdf', NULL, 0, '44671150'),
(2, 'C', 2, '2025-10-09 20:48:43', 11, 'D001', '22870111', 'POD166', 5500.00, 'Factura_C_00000002_Orden_11.pdf', NULL, 0, '44671150'),
(4, 'C', 3, '2025-10-09 20:53:55', 9, 'SEL00', '32489632', 'GHI410', 11760.00, 'Factura_C_00000003_Orden_9.pdf', NULL, 0, '44671150'),
(5, 'C', 4, '2025-10-12 17:56:15', 7, 'FR002', '41298533', 'FOM132', 16380.00, 'Factura_C_00000004_Orden_7.pdf', NULL, 0, '44671150'),
(6, 'A', 1, '2025-10-13 16:21:10', 2, 'FR003', '30164750', 'NJK038', 12540.00, 'Factura_A_00000001_Orden_2.pdf', NULL, 0, '44671150'),
(7, 'B', 1, '2025-10-13 16:21:29', 2, 'CLA00', '30164750', 'NJK038', 7920.00, 'Factura_B_00000001_Orden_2.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(10, 'C', 5, '2025-10-13 16:25:15', 4, 'EB001', '22870111', 'POD166', 71760.00, 'Factura_C_00000005_Orden_4.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(11, 'C', 6, '2025-10-13 16:28:02', 5, 'EB001', '30164750', 'AA459FT', 65780.00, 'Factura_C_00000006_Orden_5.pdf', '', 0, '44671150'),
(14, 'C', 7, '2025-10-13 16:28:24', 6, 'ST002', '43796532', 'EOZ386', 45840.00, 'Factura_C_00000007_Orden_6.pdf', '', 0, '44671150'),
(15, 'C', 8, '2025-10-13 16:29:10', 6, 'SD002', '43796532', 'EOZ386', 30140.00, 'Factura_C_00000008_Orden_6.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(16, 'C', 9, '2025-10-13 16:32:25', 18, 'SA001', '30700247', 'GCR891', 20570.00, 'Factura_C_00000009_Orden_18.pdf', 'christian.caprarulo@gmail.com', 0, '44671150'),
(17, 'C', 10, '2025-10-13 16:33:29', 19, 'RR001', '30700247', 'GCR891', 8580.00, 'Factura_C_00000010_Orden_19.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(18, 'C', 11, '2025-10-13 16:36:43', 16, 'SD001', '30700247', 'UWL004', 20020.00, 'Factura_C_00000011_Orden_16.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(20, 'B', 2, '2025-10-13 16:49:24', 14, 'FR002', '44671150', 'A221GAR', 13860.00, 'Factura_B_00000002_Orden_14.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(21, 'C', 12, '2025-10-13 16:49:56', 12, 'S002', '18762965', 'AB307CI', 24200.00, 'Factura_C_00000012_Orden_12.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(22, 'C', 13, '2025-10-13 16:51:30', 10, 'SDI00', '30164750', 'AA459FT', 14630.00, 'Factura_C_00000013_Orden_10.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(23, 'C', 14, '2025-10-13 16:52:05', 5, 'CV001', '30164750', 'AA459FT', 79860.00, 'Factura_C_00000014_Orden_5.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(24, 'C', 15, '2025-10-13 21:04:06', 21, 'MT001', '30700247', 'GCR891', 108240.00, 'Factura_C_00000015_Orden_21.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(25, 'C', 16, '2025-10-13 21:05:59', 7, 'FR001', '41298533', 'FOM132', 15600.00, 'Factura_C_00000016_Orden_7.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(26, 'C', 17, '2025-10-13 23:44:54', 4, 'CV001', '22870111', 'POD166', 87120.00, 'Factura_C_00000017_Orden_4.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(31, 'A', 2, '2025-10-15 16:50:14', 22, 'FR001', '30700247', 'GCR891', 13200.00, 'Factura_A_00000002_Orden_22.pdf', NULL, 0, '44671150'),
(32, 'B', 3, '2025-10-15 16:50:43', 17, 'OT001', '30700247', 'GCR891', 61160.00, 'Factura_B_00000003_Orden_17.pdf', NULL, 0, '44671150'),
(33, 'C', 18, '2025-10-15 16:51:57', 6, 'SD002', '43796532', 'EOZ386', 30140.00, 'Factura_C_00000018_Orden_6.pdf', NULL, 0, '44671150'),
(34, 'B', 4, '2025-10-15 16:54:38', 6, 'ST002', '43796532', 'EOZ386', 45840.00, 'Factura_B_00000004_Orden_6.pdf', 'sofiduvi@gmail.com', 0, '44671150'),
(35, 'C', 19, '2025-11-11 10:36:46', 18, 'SA001', '30700247', 'GCR891', 20570.00, 'Factura_C_00000019_Orden_18.pdf', NULL, 0, '44671150'),
(36, 'C', 20, '2025-11-11 10:37:48', 19, 'RR001', '30700247', 'GCR891', 8580.00, 'Factura_C_00000020_Orden_19.pdf', NULL, 0, '44671150'),
(37, 'B', 5, '2025-11-11 10:48:54', 20, 'S001', '30700247', 'GCR891', 30530.50, 'Factura_B_00000005_Orden_20.pdf', NULL, 0, '32690365'),
(38, 'C', 21, '2025-11-11 11:03:42', 8, 'SES00', '19786413', 'CDE091', 101005.20, 'Factura_C_00000021_Orden_8.pdf', NULL, 0, '08326014'),
(39, 'B', 6, '2025-11-11 11:07:47', 21, 'MT001', '30700247', 'GCR891', 182795.80, 'Factura_B_00000006_Orden_21.pdf', NULL, 0, '47651867');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_numeradores`
--

CREATE TABLE `factura_numeradores` (
  `tipo` enum('A','B','C') NOT NULL,
  `proximo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura_numeradores`
--

INSERT INTO `factura_numeradores` (`tipo`, `proximo`) VALUES
('A', 3),
('B', 7),
('C', 22);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `historico`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `historico` (
`orden_fecha` varchar(255)
,`vehiculo_patente` varchar(10)
,`orden_numero` int(11)
,`orden_kilometros` int(10) unsigned
,`servicio_descripcion` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes`
--

CREATE TABLE `ordenes` (
  `orden_numero` int(11) NOT NULL,
  `orden_fecha` varchar(255) NOT NULL,
  `vehiculo_patente` varchar(10) NOT NULL,
  `orden_costo` decimal(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ordenes`
--

INSERT INTO `ordenes` (`orden_numero`, `orden_fecha`, `vehiculo_patente`, `orden_costo`) VALUES
(0, '', 'GCR891', NULL),
(1, '2019-07-12', 'AB307CI', NULL),
(2, '2019-08-15', 'NJK038', NULL),
(3, '2019-08-19', 'JKM733', NULL),
(4, '2019-09-01', 'POD166', NULL),
(5, '2019-09-15', 'AA459FT', NULL),
(6, '2019-09-22', 'EOZ386', NULL),
(7, '2019-09-28', 'FOM132', NULL),
(8, '2019-10-11', 'CDE091', NULL),
(9, '2019-12-19', 'GHI410', NULL),
(10, '2020-03-25', 'AA459FT', NULL),
(11, '2020-06-04', 'POD166', NULL),
(12, '2020-08-06', 'AB307CI', NULL),
(13, '2025-05-25', 'GCR891', NULL),
(14, '2025-05-29', 'A221GAR', NULL),
(16, '2025-06-15', 'UWL004', NULL),
(17, '2025-10-13', 'GCR891', NULL),
(18, '2025-10-14', 'GCR891', NULL),
(19, '2025-10-13', 'GCR891', NULL),
(20, '2025-10-13', 'GCR891', NULL),
(21, '2025-10-13', 'GCR891', NULL),
(22, '2025-10-15', 'GCR891', NULL),
(23, '2025-11-11', 'GCR891', NULL),
(24, '2025-11-11', 'UWL004', NULL),
(25, '2025-11-11', 'UWL004', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_productos`
--

CREATE TABLE `orden_productos` (
  `id` int(11) NOT NULL,
  `orden_numero` int(11) NOT NULL,
  `prod_id` int(11) NOT NULL,
  `prod_codigo` varchar(32) NOT NULL,
  `prod_descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `mecanico_DNI` varchar(20) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_productos`
--

INSERT INTO `orden_productos` (`id`, `orden_numero`, `prod_id`, `prod_codigo`, `prod_descripcion`, `cantidad`, `precio_unitario`, `mecanico_DNI`, `creado_en`) VALUES
(1, 18, 41, 'ACC041', 'Escobilla limpiaparabrisas 16”', 1.00, 5505.50, '30700247', '2025-11-11 10:34:49'),
(2, 18, 42, 'ACC042', 'Escobilla limpiaparabrisas 20”', 1.00, 8567.90, '30700247', '2025-11-11 10:34:49'),
(3, 19, 45, 'ACC045', 'Fusibles surtidos', 1.00, 46555.30, '30700247', '2025-11-11 10:37:28'),
(4, 19, 11, 'COR011', 'Correa de distribución 120 dientes', 1.00, 9713.00, '30700247', '2025-11-11 10:37:28'),
(5, 20, 30, 'BAT030', 'Batería libre mantenimiento 55Ah', 1.00, 7049.90, '32690365', '2025-11-11 10:48:33'),
(6, 20, 19, 'BUJ019', 'Bujía doble electrodo', 1.00, 14130.60, '32690365', '2025-11-11 10:48:33'),
(7, 8, 45, 'ACC045', 'Fusibles surtidos', 1.00, 46555.30, '08326014', '2025-11-11 11:03:27'),
(8, 8, 30, 'BAT030', 'Batería libre mantenimiento 55Ah', 1.00, 7049.90, '08326014', '2025-11-11 11:03:27'),
(9, 21, 17, 'BUJ017', 'Bujía de iridio Bosch', 1.00, 22455.40, '47651867', '2025-11-11 11:07:28'),
(10, 21, 12, 'COR012', 'Correa de distribución 130 dientes', 1.00, 52100.40, '47651867', '2025-11-11 11:07:36'),
(11, 25, 42, 'ACC042', 'Escobilla limpiaparabrisas 20”', 2.00, 8567.90, '30700247', '2025-11-11 13:16:21'),
(12, 24, 45, 'ACC045', 'Fusibles surtidos', 1.00, 46555.30, '30700247', '2025-11-11 13:40:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_trabajo`
--

CREATE TABLE `orden_trabajo` (
  `orden_numero` int(11) NOT NULL,
  `servicio_codigo` varchar(5) NOT NULL,
  `complejidad` int(11) NOT NULL,
  `costo_ajustado` decimal(8,2) DEFAULT NULL,
  `orden_kilometros` int(10) UNSIGNED NOT NULL,
  `orden_comentario` varchar(255) NOT NULL,
  `orden_estado` tinyint(1) NOT NULL,
  `mecanico_DNI` varchar(15) DEFAULT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `factura_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_trabajo`
--

INSERT INTO `orden_trabajo` (`orden_numero`, `servicio_codigo`, `complejidad`, `costo_ajustado`, `orden_kilometros`, `orden_comentario`, `orden_estado`, `mecanico_DNI`, `turno_id`, `factura_id`) VALUES
(2, 'CLA00', 1, 7920.00, 120324, '', 1, '30700247', NULL, NULL),
(2, 'FR003', 1, 12540.00, 120324, '', 1, '47651867', NULL, NULL),
(2, 'SA001', 3, 24310.00, 120324, '', 0, '32690365', NULL, NULL),
(3, 'MT002', 2, 146880.00, 250341, '', 0, '47651867', NULL, NULL),
(4, 'CV001', 2, 87120.00, 60724, '', 0, '30700247', NULL, NULL),
(4, 'EB001', 2, 71760.00, 60724, '', 1, '32690365', NULL, NULL),
(5, 'CV001', 1, 79860.00, 71543, '', 0, '30700247', NULL, NULL),
(5, 'EB001', 1, 65780.00, 71543, '', 1, '47651867', NULL, NULL),
(6, 'SD002', 1, 30140.00, 47980, '', 1, '32690365', NULL, 33),
(6, 'ST002', 2, 45840.00, 47980, '', 1, '30700247', NULL, 34),
(7, 'FR001', 3, 15600.00, 56782, '', 0, '47651867', NULL, NULL),
(7, 'FR002', 3, 16380.00, 56782, '', 1, '32690365', NULL, 5),
(8, 'SES00', 2, 47400.00, 25619, '', 1, '08326014', NULL, 38),
(9, 'SEL00', 2, 11760.00, 94723, '', 1, '08326014', NULL, 4),
(10, 'SDI00', 1, 14630.00, 119832, '', 0, '30700247', NULL, NULL),
(11, 'D001', 1, 5500.00, 43909, '', 1, '08326014', NULL, 2),
(12, 'S002', 1, 24200.00, 67413, '', 0, '30700247', NULL, NULL),
(13, 'D001', 1, 5500.00, 1000, 'NINGUNO', 1, '08326014', NULL, 1),
(14, 'FR002', 1, 13860.00, 1338, 'La clienta dice que no frena un carajo.', 0, '30700247', NULL, NULL),
(16, 'SD001', 1, 20020.00, 85021, 'Hace un ruidito', 0, '30700247', NULL, NULL),
(17, 'OT001', 1, 61160.00, 1, '1234', 1, '08326014', 47, 32),
(18, 'SA001', 1, 20570.00, 61999, '123456789', 1, '30700247', 48, 35),
(19, 'RR001', 1, 8580.00, 62000, '123456789', 1, '30700247', 49, 36),
(20, 'S001', 1, 9350.00, 62001, '123654125874126541', 1, '32690365', 50, 37),
(21, 'MT001', 1, 108240.00, 62005, '12368741236584', 1, '47651867', 51, 39),
(22, 'FR001', 1, 13200.00, 62009, '1236987412', 1, '32690365', 52, 31),
(23, 'SD002', 1, 30140.00, 62010, 'vsdfbcbncxb', 0, '08326014', 53, NULL),
(24, 'RR002', 1, 8910.00, 10000000, 'jghjdnn', 1, '30700247', 54, NULL),
(25, 'RR002', 1, 8910.00, 10000000, 'jghjdnn', 1, '30700247', 55, NULL);

--
-- Disparadores `orden_trabajo`
--
DELIMITER $$
CREATE TRIGGER `before_insert_orden_trabajo` BEFORE INSERT ON `orden_trabajo` FOR EACH ROW BEGIN
  DECLARE base_costo DECIMAL(10,2) DEFAULT 0;
  DECLARE mult       DECIMAL(4,2)  DEFAULT 1;

  SELECT COALESCE(servicio_costo,0) INTO base_costo
  FROM servicios
  WHERE servicio_codigo = NEW.servicio_codigo;

  SET mult = CASE NEW.complejidad
               WHEN 1 THEN 1.10
               WHEN 2 THEN 1.20
               WHEN 3 THEN 1.30
               ELSE 1
             END;

  SET NEW.costo_ajustado = ROUND(base_costo * mult, 2);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_orden_trabajo` BEFORE UPDATE ON `orden_trabajo` FOR EACH ROW BEGIN
  DECLARE base_costo DECIMAL(10,2) DEFAULT 0;
  DECLARE mult       DECIMAL(4,2)  DEFAULT 1;

  SELECT COALESCE(servicio_costo,0) INTO base_costo
  FROM servicios
  WHERE servicio_codigo = NEW.servicio_codigo;

  SET mult = CASE NEW.complejidad
               WHEN 1 THEN 1.10
               WHEN 2 THEN 1.20
               WHEN 3 THEN 1.30
               ELSE 1
             END;

  SET NEW.costo_ajustado = ROUND(base_costo * mult, 2);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `prod_id` int(11) NOT NULL,
  `prod_codigo` varchar(20) NOT NULL,
  `prod_categoria` varchar(100) NOT NULL,
  `prod_descripcion` varchar(255) NOT NULL,
  `prod_stock` int(11) NOT NULL DEFAULT 0,
  `prod_precio_proveedor` decimal(10,2) NOT NULL,
  `prod_precio_venta` decimal(10,2) NOT NULL,
  `prod_disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`prod_id`, `prod_codigo`, `prod_categoria`, `prod_descripcion`, `prod_stock`, `prod_precio_proveedor`, `prod_precio_venta`, `prod_disponible`) VALUES
(1, 'LUB001', 'Lubricantes', 'Aceite sintético 5W30 1L', 8, 18702.00, 20572.20, 1),
(2, 'LUB002', 'Lubricantes', 'Aceite mineral 20W50 1L', 15, 34570.00, 38027.00, 1),
(3, 'LUB003', 'Lubricantes', 'Aceite semisintético 10W40 1L', 25, 16103.00, 17713.30, 1),
(4, 'LUB004', 'Lubricantes', 'Aceite sintético 0W20 1L', 96, 33384.00, 36722.40, 1),
(5, 'LUB005', 'Lubricantes', 'Aceite para caja automática ATF 1L', 14, 22279.00, 24506.90, 1),
(6, 'FIL006', 'Filtros', 'Filtro de aceite motor', 26, 35276.00, 38803.60, 1),
(7, 'FIL007', 'Filtros', 'Filtro de aire estándar', 68, 9081.00, 9989.10, 1),
(8, 'FIL008', 'Filtros', 'Filtro de aire deportivo', 85, 25053.00, 27558.30, 1),
(9, 'FIL009', 'Filtros', 'Filtro de combustible', 73, 11745.00, 12919.50, 1),
(10, 'FIL010', 'Filtros', 'Filtro de cabina', 61, 47959.00, 52754.90, 1),
(11, 'COR011', 'Correas', 'Correa de distribución 120 dientes', 79, 8830.00, 9713.00, 1),
(12, 'COR012', 'Correas', 'Correa de distribución 130 dientes', 49, 47364.00, 52100.40, 1),
(13, 'COR013', 'Correas', 'Correa poly-V 6PK', 85, 3621.00, 3983.10, 1),
(14, 'COR014', 'Correas', 'Correa alternador', 95, 17709.00, 19479.90, 1),
(15, 'COR015', 'Correas', 'Correa bomba de agua', 36, 10099.00, 11108.90, 1),
(16, 'BUJ016', 'Bujías', 'Bujía estándar NGK', 77, 1712.00, 1883.20, 1),
(17, 'BUJ017', 'Bujías', 'Bujía de iridio Bosch', 97, 20414.00, 22455.40, 1),
(18, 'BUJ018', 'Bujías', 'Bujía de platino Denso', 65, 47066.00, 51772.60, 1),
(19, 'BUJ019', 'Bujías', 'Bujía doble electrodo', 92, 12846.00, 14130.60, 1),
(20, 'BUJ020', 'Bujías', 'Bujía larga resistencia', 18, 10688.00, 11756.80, 1),
(21, 'FRE021', 'Frenos', 'Pastillas de freno delanteras', 41, 16070.00, 17677.00, 1),
(22, 'FRE022', 'Frenos', 'Pastillas de freno traseras', 49, 49565.00, 54521.50, 1),
(23, 'FRE023', 'Frenos', 'Disco de freno ventilado', 69, 8871.00, 9758.10, 1),
(24, 'FRE024', 'Frenos', 'Disco de freno sólido', 24, 28210.00, 31031.00, 1),
(25, 'FRE025', 'Frenos', 'Líquido de frenos DOT 4', 33, 5764.00, 6340.40, 1),
(26, 'BAT026', 'Baterías', 'Batería 45Ah', 65, 21885.00, 24073.50, 1),
(27, 'BAT027', 'Baterías', 'Batería 60Ah', 89, 44261.00, 48687.10, 1),
(28, 'BAT028', 'Baterías', 'Batería 75Ah', 87, 39854.00, 43839.40, 1),
(29, 'BAT029', 'Baterías', 'Batería AGM 70Ah', 59, 37594.00, 41353.40, 1),
(30, 'BAT030', 'Baterías', 'Batería libre mantenimiento 55Ah', 60, 6409.00, 7049.90, 1),
(31, 'NEU031', 'Neumáticos', 'Neumático 175/70 R13', 12, 44887.00, 49375.70, 1),
(32, 'NEU032', 'Neumáticos', 'Neumático 185/65 R14', 49, 9439.00, 10382.90, 1),
(33, 'NEU033', 'Neumáticos', 'Neumático 195/60 R15', 97, 31104.00, 34214.40, 1),
(34, 'NEU034', 'Neumáticos', 'Neumático 205/55 R16', 26, 48649.00, 53513.90, 1),
(35, 'NEU035', 'Neumáticos', 'Neumático 215/45 R17', 92, 45814.00, 50395.40, 1),
(36, 'LÍQ036', 'Líquidos', 'Refrigerante 1L', 62, 40835.00, 44918.50, 1),
(37, 'LÍQ037', 'Líquidos', 'Refrigerante 5L', 87, 9981.00, 10979.10, 1),
(38, 'LÍQ038', 'Líquidos', 'Líquido de dirección hidráulica 1L', 26, 5207.00, 5727.70, 1),
(39, 'LÍQ039', 'Líquidos', 'Agua destilada 5L', 31, 5903.00, 6493.30, 1),
(40, 'LÍQ040', 'Líquidos', 'Aditivo limpia inyectores', 67, 25587.00, 28145.70, 1),
(41, 'ACC041', 'Accesorios', 'Escobilla limpiaparabrisas 16”', 60, 5005.00, 6056.05, 1),
(42, 'ACC042', 'Accesorios', 'Escobilla limpiaparabrisas 20”', 22, 7789.00, 9424.69, 1),
(43, 'ACC043', 'Accesorios', 'Kit de lámparas H4', 85, 35191.00, 38710.10, 1),
(44, 'ACC044', 'Accesorios', 'Kit de lámparas H7', 89, 5977.00, 6574.70, 1),
(45, 'ACC045', 'Accesorios', 'Fusibles surtidos', 5, 42323.00, 46555.30, 1),
(46, 'SUS046', 'Suspensión', 'Amortiguador delantero', 41, 49546.00, 54500.60, 1),
(47, 'SUS047', 'Suspensión', 'Amortiguador trasero', 80, 32891.00, 36180.10, 1),
(48, 'SUS048', 'Suspensión', 'Parrilla de suspensión', 34, 44292.00, 48721.20, 1),
(49, 'SUS049', 'Suspensión', 'Rótula de dirección', 50, 17168.00, 18884.80, 1),
(50, 'SUS050', 'Suspensión', 'Barra estabilizadora', 88, 14567.00, 16023.70, 1),
(51, 'BAT031', 'Baterías', 'Bateria 80Ah', 50, 40914.00, 45005.40, 1),
(52, 'ACC046', 'Accesorios', 'Kit de lámparas H1', 15, 7985.00, 8783.50, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `servicio_codigo` varchar(5) NOT NULL,
  `servicio_nombre` varchar(35) NOT NULL,
  `servicio_descripcion` varchar(100) NOT NULL,
  `servicio_costo` decimal(8,2) NOT NULL,
  `servicio_disponible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`servicio_codigo`, `servicio_nombre`, `servicio_descripcion`, `servicio_costo`, `servicio_disponible`) VALUES
('CLA00', 'CAMBIO DE LAMPARAS', 'REVICION Y CAMBIO DE LAMPARAS', 7200.00, 1),
('CV001', 'CAJA DE VELOCIDADES', 'REPARACION DE COMPETA DE CAJA DE VELOCIDADES CAMBIO DE RETENES', 72600.00, 1),
('D001', 'DIAGNOSTICO COMPUTARIZADO', 'TOMA DE DIAGNOSTICO', 5000.00, 1),
('EB001', 'EMBRAGUE', 'CAMBIO O REPARACION DE EMBRAGUE, BOMBA, BOMBIN, RULEMAN DE EMPUJE', 59800.00, 1),
('FR001', 'FRENOS DELANTEROS', 'REPARACION O CAMBIO DE DISCO, CAMBIO DE PASTILLAS.', 12000.00, 1),
('FR002', 'FRENOS TRASERO', 'REPARACION O CAMBIO DE ZAPATAS, REPARACION O CAMBIO DE CAMPANAS.', 12600.00, 1),
('FR003', 'SISTEMA DE FRENOS', 'BOMBA, ABS, CALIBRQACION DE FRENOS', 11400.00, 1),
('LIM00', 'LIMPIEZA DE INYECTORES', 'LIMPIEZA Y PUESTA A PUNTO DE INYECTORES', 19700.00, 1),
('MT001', '½ MOTOR', 'CAMBIO DE AROS, METALES Y RETENES', 98400.00, 1),
('MT002', 'MOTOR COMPLETO', 'DESARME Y REPARACION COMPLETA DE MOTOR', 122400.00, 1),
('NEU00', 'Neumatico Delanteros', 'Cambio de neumáticos delanteros', 60000.00, 1),
('NEU01', 'NEUMATICOS TRASEROS', 'CAMBIO DE NEUMÁTICOS TRASEROS', 60000.00, 1),
('OT001', 'OTROS', 'TRABAJOS NO CONTEMPLADOS.', 55600.00, 1),
('RE001', 'REVISION ECU', 'REVISION, AJUSTE PROGRAMACION DE ECU', 19600.00, 1),
('RR001', 'RULEMANES DE RUEDA DELANTEROS', 'CAMBIO DE RULEMANES DE RUEDA', 7800.00, 1),
('RR002', 'RULEMANES DE RUEDA TRASEROS', 'CAMBIO DE RULEMANES DE RUEDA', 8100.00, 1),
('S001', 'SERVICE', 'CAMBIO DE FILTROS –ACEITE, AIREMOTOR, AIRE HABITACULO, COMBUSTIBLE- CAMBIO DE FLUIDOS', 8500.00, 1),
('S002', 'SERVICE DISTRIBUCION', 'CORREA O CADENA DE DISTRIBUCION, TENSORES, CORREA DE ACCESORIOS', 22000.00, 1),
('SA001', 'SISTEMA DE ADMSION', 'LIMPIEZA, REGULACION Y PUESTA A PUNTO DE CARBURADOR O CUERPO MARIPOSA', 18700.00, 1),
('SD001', 'SUSPENSIÓN DELANTERA BÁSICA', 'CAMBIO DE ROTULAS, BIELETA,BUJES DE PARRILLA, BUJES BARRA ESTABILIZADORA', 18200.00, 1),
('SD002', 'SUSPENSIÓN DELANTERA COMPLETA', 'SUPENCION DELANTERA BASICA + AMORTIGUADORES, CASOLETAS, ESPIRALES, PARRILLAS', 27400.00, 1),
('SDI00', 'SISTEMA DE DIRECCION', 'CREMALLERA, EXTREMOS, PRECAP, COLUMNA DE DIRECCION', 13300.00, 1),
('SE001', 'SISTEMA DE ENCENDIDO', 'BOBINA, CABLES, BUJIAS, PRECALENTADORES, DISTRIBUIDOR', 10400.00, 1),
('SEL00', 'SISTEMA ELECTRICO', 'BATERIA, ALTERNADOR, ARRANQUE', 9800.00, 1),
('SES00', 'SISTEMA DE ESCAPE ', 'REPARACION DE MULTIPLE DE ESCAPE, CAÑO DE ESCAPE, SILENCIADOR, CAMBIO DE JUNTAS.', 39500.00, 1),
('SIS00', 'SISTEMA DE ESCAPE', 'REPARACIÓN DE SISTEMA DE ESCAPE', 50000.00, 1),
('SRF00', 'SISTEMA DE REFRIGERACION', 'CAMBIO DE MANGUERAS, RADIADOR, TERMOSTATO, BULBO DE TEMPERATURA,  BIDON DE REFRIGERANTE, BOMBE DE AG', 14600.00, 1),
('ST001', 'SUSPENSIÓN TRASERA BÁSICA', 'BUJES DE PARRILLA SUPERIOR E INFERIOR, BUJES PUENTE TRASERO O BRAZO OSCILANTE', 22100.00, 1),
('ST002', 'SUSPENSIÓN TRASERA COMPLETA', 'SUSPENSIÓN TRASERA BASICA + AMORTIGUADORES, ESPIRALES, PUENTE TRASERO, PARRILLAS INFERIOR Y SUPERIOR', 38200.00, 1),
('STR00', 'SISTEMA TRACCION', 'PALIERES, TRICETAS,  HOMOCINETICAS, CARDAN, DIFERENCIAL', 17600.00, 1),
('TP001', 'TAPA DE CILINDRO', 'REPARACION DE TAPA DE CILINDROS, CAMBIO DE JUNTAS, RETENES Y BULONES', 48500.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `turno_id` int(11) NOT NULL,
  `turno_fecha` date NOT NULL,
  `turno_hora` time NOT NULL,
  `cliente_DNI` char(8) DEFAULT NULL,
  `vehiculo_patente` varchar(10) DEFAULT NULL,
  `mecanico_dni` char(8) DEFAULT NULL,
  `turno_estado` enum('pendiente','finalizado') DEFAULT 'pendiente',
  `turno_comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`turno_id`, `turno_fecha`, `turno_hora`, `cliente_DNI`, `vehiculo_patente`, `mecanico_dni`, `turno_estado`, `turno_comentario`) VALUES
(47, '2025-10-13', '08:00:00', '30700247', 'GCR891', '08326014', 'pendiente', '1234'),
(48, '0000-00-00', '00:00:00', '30700247', 'GCR891', '30700247', 'pendiente', '123456789'),
(49, '2025-10-13', '08:00:00', '30700247', 'GCR891', '30700247', 'pendiente', '123456789'),
(50, '2025-10-13', '08:00:00', '30700247', 'GCR891', '32690365', 'pendiente', '123654125874126541'),
(51, '2025-10-13', '08:00:00', '30700247', 'GCR891', '47651867', 'pendiente', '12368741236584'),
(52, '2025-10-15', '08:00:00', '30700247', 'GCR891', '32690365', 'pendiente', '1236987412'),
(53, '2025-11-11', '08:00:00', '30700247', 'GCR891', '08326014', 'pendiente', 'vsdfbcbncxb'),
(54, '2025-11-11', '08:00:00', '30700247', 'UWL004', '30700247', 'pendiente', 'jghjdnn'),
(55, '2025-11-11', '08:00:00', '30700247', 'UWL004', '30700247', 'pendiente', 'jghjdnn');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculos`
--

CREATE TABLE `vehiculos` (
  `vehiculo_patente` varchar(10) NOT NULL,
  `cliente_DNI` varchar(10) NOT NULL,
  `vehiculo_marca` varchar(10) NOT NULL,
  `vehiculo_modelo` varchar(10) NOT NULL,
  `vehiculo_anio` varchar(4) DEFAULT NULL,
  `vehiculo_color` varchar(10) DEFAULT NULL,
  `vehiculo_motor` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehiculos`
--

INSERT INTO `vehiculos` (`vehiculo_patente`, `cliente_DNI`, `vehiculo_marca`, `vehiculo_modelo`, `vehiculo_anio`, `vehiculo_color`, `vehiculo_motor`) VALUES
('A221GAR', '44671150', 'Bajaja', 'Rouser', '2024', 'Negro', '125cc'),
('AA459FT', '30164750', 'Toyota', 'Corolla', '2016', 'Blanco', 'VVTI 1.8'),
('AB307CI', '18762965', 'Volkswagen', 'Gol trend', '2017', 'Negro', 'HTV 1.6'),
('AE489AB', '28090318', 'Peugeot', '208', '2020', NULL, NULL),
('CDE091', '19786413', 'Renault', 'Clio', '2003', NULL, 'BLUE DCI'),
('EOZ386', '43796532', 'Ford', 'Fiesta', '2004', 'Rojo', 'ZETECK 1.6'),
('FOM132', '41298533', 'Fiat', 'Palio', '2012', 'Negro', 'Fire 1.6'),
('GCR891', '30700247', 'Chevrolet', 'Zafira', '2007', 'Gris', '2.0L 16V'),
('GHI410', '32489632', 'Citroen', 'Xsara', '2007', 'Rojo', 'L416V 1.8'),
('JKM733', '32489632', 'Susuki', 'Fun', NULL, 'Gris', NULL),
('NJK038', '30164750', 'Chevrolet', 'Corsa', '2011', 'Azul', NULL),
('POD166', '22870111', 'Dodge', 'Journey', '2015', 'Verde', 'DOHC Penta'),
('UWL004', '30700247', 'FIAT', '128 IAVA', '1973', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura para la vista `historico`
--
DROP TABLE IF EXISTS `historico`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `historico`  AS SELECT `o`.`orden_fecha` AS `orden_fecha`, `o`.`vehiculo_patente` AS `vehiculo_patente`, `ot`.`orden_numero` AS `orden_numero`, `ot`.`orden_kilometros` AS `orden_kilometros`, `s`.`servicio_descripcion` AS `servicio_descripcion` FROM ((`ordenes` `o` join `orden_trabajo` `ot` on(`o`.`orden_numero` = `ot`.`orden_numero`)) join `servicios` `s` on(`ot`.`servicio_codigo` = `s`.`servicio_codigo`)) ORDER BY `o`.`vehiculo_patente` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`cliente_DNI`);

--
-- Indices de la tabla `empleados`
--
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`empleado_DNI`);

--
-- Indices de la tabla `facturas`
--
ALTER TABLE `facturas`
  ADD PRIMARY KEY (`factura_id`),
  ADD UNIQUE KEY `uk_tipo_nro` (`tipo`,`nro_comprobante`),
  ADD KEY `idx_orden_serv` (`orden_numero`,`servicio_codigo`);

--
-- Indices de la tabla `factura_numeradores`
--
ALTER TABLE `factura_numeradores`
  ADD PRIMARY KEY (`tipo`);

--
-- Indices de la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD PRIMARY KEY (`orden_numero`),
  ADD KEY `vehiculo_patente` (`vehiculo_patente`);

--
-- Indices de la tabla `orden_productos`
--
ALTER TABLE `orden_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_orden_producto` (`orden_numero`,`prod_id`);

--
-- Indices de la tabla `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD PRIMARY KEY (`orden_numero`,`servicio_codigo`),
  ADD KEY `servicio_codigo` (`servicio_codigo`),
  ADD KEY `fk_turno_orden_trabajo` (`turno_id`),
  ADD KEY `idx_factura_id` (`factura_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`prod_id`),
  ADD UNIQUE KEY `prod_codigo` (`prod_codigo`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`servicio_codigo`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`turno_id`),
  ADD KEY `cliente_dni` (`cliente_DNI`),
  ADD KEY `vehiculo_patente` (`vehiculo_patente`),
  ADD KEY `mecanico_dni` (`mecanico_dni`);

--
-- Indices de la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD PRIMARY KEY (`vehiculo_patente`),
  ADD KEY `cliente_DNI` (`cliente_DNI`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `facturas`
--
ALTER TABLE `facturas`
  MODIFY `factura_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `orden_productos`
--
ALTER TABLE `orden_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `prod_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `turno_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ordenes`
--
ALTER TABLE `ordenes`
  ADD CONSTRAINT `ordenes_ibfk_1` FOREIGN KEY (`vehiculo_patente`) REFERENCES `vehiculos` (`vehiculo_patente`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD CONSTRAINT `fk_ot_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`factura_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turno_orden_trabajo` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`turno_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orden_trabajo_ibfk_1` FOREIGN KEY (`servicio_codigo`) REFERENCES `servicios` (`servicio_codigo`),
  ADD CONSTRAINT `orden_trabajo_ibfk_2` FOREIGN KEY (`orden_numero`) REFERENCES `ordenes` (`orden_numero`);

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`cliente_dni`) REFERENCES `clientes` (`cliente_DNI`),
  ADD CONSTRAINT `turnos_ibfk_2` FOREIGN KEY (`vehiculo_patente`) REFERENCES `vehiculos` (`vehiculo_patente`),
  ADD CONSTRAINT `turnos_ibfk_3` FOREIGN KEY (`mecanico_dni`) REFERENCES `empleados` (`empleado_DNI`);

--
-- Filtros para la tabla `vehiculos`
--
ALTER TABLE `vehiculos`
  ADD CONSTRAINT `vehiculos_ibfk_1` FOREIGN KEY (`cliente_DNI`) REFERENCES `clientes` (`cliente_DNI`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
