-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-07-2025 a las 20:10:58
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
-- Base de datos: `negocio_fotografia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `cedula` varchar(13) DEFAULT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `telefono_celular` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena_hash` char(64) DEFAULT NULL,
  `token_confirmacion` char(64) DEFAULT NULL,
  `vencimiento_token` datetime DEFAULT NULL,
  `esta_activo` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_confirmacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `cedula`, `nombre_completo`, `telefono_celular`, `correo`, `contrasena_hash`, `token_confirmacion`, `vencimiento_token`, `esta_activo`, `fecha_registro`, `fecha_confirmacion`) VALUES
(3, '8-979-2406', 'Harold', '6563-8265', 'haroldmadrid18@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', NULL, NULL, 1, '2025-07-28 05:58:43', '2025-07-28 12:38:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_edicion`
--

CREATE TABLE `comentarios_edicion` (
  `id_comentario` int(11) NOT NULL,
  `galeria_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `comentarios` text NOT NULL,
  `fotos_seleccionadas` text NOT NULL,
  `estado` enum('pendiente','en_proceso','completado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios_edicion`
--

INSERT INTO `comentarios_edicion` (`id_comentario`, `galeria_id`, `cliente_id`, `comentarios`, `fotos_seleccionadas`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 2, 3, 'mas lindas', '[\"7\",\"8\"]', 'pendiente', '2025-07-28 13:02:10', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos`
--

CREATE TABLE `fotos` (
  `id_foto` int(11) NOT NULL,
  `galeria_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `url_editada` varchar(255) DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `es_final` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fotos`
--

INSERT INTO `fotos` (`id_foto`, `galeria_id`, `filename`, `url`, `url_editada`, `is_selected`, `es_final`, `uploaded_at`) VALUES
(1, 1, 'img_6887b301037ec_1753723649_0.png', 'img_6887b301037ec_1753723649_0.png', NULL, 0, 0, '2025-07-28 12:27:29'),
(2, 1, 'img_6887b30105082_1753723649_1.png', 'img_6887b30105082_1753723649_1.png', NULL, 0, 0, '2025-07-28 12:27:29'),
(3, 1, 'img_6887b30106699_1753723649_2.png', 'img_6887b30106699_1753723649_2.png', NULL, 0, 0, '2025-07-28 12:27:29'),
(4, 1, 'img_6887b30108c3e_1753723649_3.png', 'img_6887b30108c3e_1753723649_3.png', NULL, 0, 0, '2025-07-28 12:27:29'),
(5, 1, 'img_6887b3010985c_1753723649_4.png', 'img_6887b3010985c_1753723649_4.png', NULL, 0, 0, '2025-07-28 12:27:29'),
(6, 2, 'img_6887b30328685_1753723651_0.png', 'img_6887b30328685_1753723651_0.png', NULL, 0, 0, '2025-07-28 12:27:31'),
(7, 2, 'img_6887b30329070_1753723651_1.png', 'img_6887b30329070_1753723651_1.png', NULL, 1, 0, '2025-07-28 12:27:31'),
(8, 2, 'img_6887b30329997_1753723651_2.png', 'img_6887b30329997_1753723651_2.png', NULL, 1, 0, '2025-07-28 12:27:31'),
(9, 2, 'img_6887b3032a278_1753723651_3.png', 'img_6887b3032a278_1753723651_3.png', NULL, 0, 0, '2025-07-28 12:27:31'),
(10, 2, 'img_6887b3032ad87_1753723651_4.png', 'img_6887b3032ad87_1753723651_4.png', NULL, 0, 0, '2025-07-28 12:27:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galerias`
--

CREATE TABLE `galerias` (
  `id_galeria` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo_galeria` enum('inicial','final') NOT NULL DEFAULT 'inicial',
  `estado` enum('borrador','revision','seleccion','completada') NOT NULL DEFAULT 'borrador',
  `token` char(32) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `galerias`
--

INSERT INTO `galerias` (`id_galeria`, `sesion_id`, `usuario_id`, `tipo_galeria`, `estado`, `token`, `creado_en`) VALUES
(1, 3, 1, 'final', 'completada', '51c4859ecbfc4192887130dc03924193', '2025-07-28 12:27:29'),
(2, 3, 1, 'final', 'completada', '761e5d6d66f448ef4f9fcbf465f90395', '2025-07-28 12:27:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_impresion`
--

CREATE TABLE `ordenes_impresion` (
  `id_orden` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `foto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `tamanio_id` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unitario`) STORED,
  `estado` enum('en espera','completada','cancelado') NOT NULL DEFAULT 'en espera',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_impresion`
--

CREATE TABLE `pagos_impresion` (
  `id_pago_impresion` int(11) NOT NULL,
  `orden_impresion_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_sesiones`
--

CREATE TABLE `pagos_sesiones` (
  `id_pago_sesion` int(11) NOT NULL,
  `sesion_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_sesiones`
--

INSERT INTO `pagos_sesiones` (`id_pago_sesion`, `sesion_id`, `monto`, `metodo_pago`, `numero_factura`, `fecha_pago`) VALUES
(3, 3, 90.00, 'Tarjeta de Crédito', 'FCT-20250728-125843-2112', '2025-07-28 05:58:43'),
(4, 4, 90.00, 'Tarjeta de Crédito', 'FCT-20250728-133755-7358', '2025-07-28 06:37:55'),
(6, 3, 210.00, 'Tarjeta de Crédito', 'FCT-20250728-200151-9876', '2025-07-28 13:01:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id_sesion` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_sesion` enum('cobertura de evento','temática','estudio','exterior','corporativo','familiar','retrato','otro') NOT NULL,
  `descripcion_sesion` text NOT NULL,
  `duracion_sesion` varchar(50) NOT NULL,
  `precio_base` decimal(10,2) DEFAULT NULL,
  `lugar_sesion` enum('estudio','domicilio','exterior') NOT NULL,
  `direccion_sesion` varchar(255) DEFAULT NULL,
  `estilo_fotografia` enum('clasico','moderno','creativo','otro') NOT NULL,
  `servicios_adicionales` text DEFAULT NULL,
  `otros_datos` varchar(200) DEFAULT NULL,
  `total_pagar` decimal(10,2) NOT NULL,
  `abono_inicial` decimal(10,2) NOT NULL,
  `saldo` decimal(10,2) GENERATED ALWAYS AS (`total_pagar` - `abono_inicial`) STORED,
  `fecha_sesion` date NOT NULL,
  `hora_sesion` time NOT NULL,
  `estado` enum('en_espera','completado','cancelado') NOT NULL DEFAULT 'en_espera',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id_sesion`, `cliente_id`, `tipo_sesion`, `descripcion_sesion`, `duracion_sesion`, `precio_base`, `lugar_sesion`, `direccion_sesion`, `estilo_fotografia`, `servicios_adicionales`, `otros_datos`, `total_pagar`, `abono_inicial`, `fecha_sesion`, `hora_sesion`, `estado`, `creado_en`) VALUES
(3, 3, 'temática', 'Sesión fotográfica con temática personalizada', '2h 0min', NULL, 'estudio', '', 'clasico', '', '', 300.00, 90.00, '2222-02-22', '22:02:00', 'completado', '2025-07-28 05:58:43'),
(4, 3, 'temática', 'Sesión fotográfica con temática personalizada', '2h 0min', NULL, 'estudio', '', 'clasico', '', '', 300.00, 90.00, '0000-00-00', '22:22:00', 'en_espera', '2025-07-28 06:37:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tamanos_impresion`
--

CREATE TABLE `tamanos_impresion` (
  `id_tamanio` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `popular` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` char(64) NOT NULL COMMENT 'Hash de la contraseña',
  `rol` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `contrasena`, `rol`, `creado_en`) VALUES
(1, 'admin', 'ac9689e2272427085e35b9d3e3e8bed88cb3434828b43b86fc0596cad4c6e270', 'admin', '2025-07-28 07:09:05'),
(2, 'haroldmadrid18@gmail.com', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'cliente', '2025-07-28 12:38:52');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `comentarios_edicion`
--
ALTER TABLE `comentarios_edicion`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `galeria_id` (`galeria_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD PRIMARY KEY (`id_foto`),
  ADD KEY `galeria_id` (`galeria_id`);

--
-- Indices de la tabla `galerias`
--
ALTER TABLE `galerias`
  ADD PRIMARY KEY (`id_galeria`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `sesion_id` (`sesion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `ordenes_impresion`
--
ALTER TABLE `ordenes_impresion`
  ADD PRIMARY KEY (`id_orden`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `foto_id` (`foto_id`),
  ADD KEY `tamanio_id` (`tamanio_id`);

--
-- Indices de la tabla `pagos_impresion`
--
ALTER TABLE `pagos_impresion`
  ADD PRIMARY KEY (`id_pago_impresion`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `orden_impresion_id` (`orden_impresion_id`);

--
-- Indices de la tabla `pagos_sesiones`
--
ALTER TABLE `pagos_sesiones`
  ADD PRIMARY KEY (`id_pago_sesion`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`),
  ADD KEY `sesion_id` (`sesion_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `tamanos_impresion`
--
ALTER TABLE `tamanos_impresion`
  ADD PRIMARY KEY (`id_tamanio`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `comentarios_edicion`
--
ALTER TABLE `comentarios_edicion`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `fotos`
--
ALTER TABLE `fotos`
  MODIFY `id_foto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `galerias`
--
ALTER TABLE `galerias`
  MODIFY `id_galeria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ordenes_impresion`
--
ALTER TABLE `ordenes_impresion`
  MODIFY `id_orden` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_impresion`
--
ALTER TABLE `pagos_impresion`
  MODIFY `id_pago_impresion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pagos_sesiones`
--
ALTER TABLE `pagos_sesiones`
  MODIFY `id_pago_sesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tamanos_impresion`
--
ALTER TABLE `tamanos_impresion`
  MODIFY `id_tamanio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comentarios_edicion`
--
ALTER TABLE `comentarios_edicion`
  ADD CONSTRAINT `comentarios_edicion_ibfk_1` FOREIGN KEY (`galeria_id`) REFERENCES `galerias` (`id_galeria`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_edicion_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fotos`
--
ALTER TABLE `fotos`
  ADD CONSTRAINT `fotos_ibfk_1` FOREIGN KEY (`galeria_id`) REFERENCES `galerias` (`id_galeria`) ON DELETE CASCADE;

--
-- Filtros para la tabla `galerias`
--
ALTER TABLE `galerias`
  ADD CONSTRAINT `galerias_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id_sesion`) ON DELETE CASCADE,
  ADD CONSTRAINT `galerias_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ordenes_impresion`
--
ALTER TABLE `ordenes_impresion`
  ADD CONSTRAINT `ordenes_impresion_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`),
  ADD CONSTRAINT `ordenes_impresion_ibfk_2` FOREIGN KEY (`foto_id`) REFERENCES `fotos` (`id_foto`),
  ADD CONSTRAINT `ordenes_impresion_ibfk_3` FOREIGN KEY (`tamanio_id`) REFERENCES `tamanos_impresion` (`id_tamanio`);

--
-- Filtros para la tabla `pagos_impresion`
--
ALTER TABLE `pagos_impresion`
  ADD CONSTRAINT `pagos_impresion_ibfk_1` FOREIGN KEY (`orden_impresion_id`) REFERENCES `ordenes_impresion` (`id_orden`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos_sesiones`
--
ALTER TABLE `pagos_sesiones`
  ADD CONSTRAINT `pagos_sesiones_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones` (`id_sesion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
