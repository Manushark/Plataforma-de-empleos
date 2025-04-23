-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2025 a las 02:51:59
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
-- Base de datos: `empleos_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aplicaciones`
--

CREATE TABLE `aplicaciones` (
  `id` int(11) NOT NULL,
  `candidato_id` int(11) NOT NULL,
  `oferta_id` int(11) NOT NULL,
  `fecha_aplicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','revisada','rechazada','aceptada') DEFAULT 'pendiente',
  `carta_presentacion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aplicaciones`
--

INSERT INTO `aplicaciones` (`id`, `candidato_id`, `oferta_id`, `fecha_aplicacion`, `estado`, `carta_presentacion`) VALUES
(1, 2, 4, '2025-04-22 19:53:28', 'pendiente', '.wekhgiw'),
(3, 3, 4, '2025-04-23 06:19:22', 'pendiente', 'HOla'),
(4, 3, 7, '2025-04-23 06:32:10', 'aceptada', 'hi');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidatos`
--

CREATE TABLE `candidatos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `formacion_academica` text DEFAULT NULL,
  `experiencia_laboral` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL,
  `idiomas` text DEFAULT NULL,
  `objetivo_profesional` text DEFAULT NULL,
  `logros` text DEFAULT NULL,
  `disponibilidad` varchar(50) DEFAULT NULL,
  `redes_profesionales` text DEFAULT NULL,
  `referencias` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `cv_pdf` varchar(255) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nivel_educativo` varchar(100) DEFAULT NULL,
  `titulo_profesional` varchar(100) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `candidatos`
--

INSERT INTO `candidatos` (`id`, `usuario_id`, `nombre`, `apellidos`, `telefono`, `direccion`, `ciudad`, `formacion_academica`, `experiencia_laboral`, `habilidades`, `idiomas`, `objetivo_profesional`, `logros`, `disponibilidad`, `redes_profesionales`, `referencias`, `foto`, `cv_pdf`, `codigo_postal`, `fecha_nacimiento`, `nivel_educativo`, `titulo_profesional`, `linkedin`) VALUES
(1, 1, 'manusharck', 'lio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 'Pedro', 'mandy', '8296108352', 'Santo Domingo', 'Santo domingo', NULL, NULL, 'dskngfskld', 'ssds', 'ladkfjnlsd', NULL, 'inmediata', NULL, NULL, '08e24b793aafb88543dd86dea19a0572_WhatsApp Image 2025-02-14 at 21.20.36_cb528a9e.jpg', 'cv_2_1745330008.pdf', '13131', '2022-12-12', NULL, 'ksef', ''),
(3, 4, 'Juan', 'Alberto', '8651616854', 'Santo Domingo', 'Santo domingo', NULL, NULL, 'Trabajo en equipo', 'Espanol y Ingles', 'Contribuir con la empresa', NULL, 'inmediata', NULL, NULL, '7a3815737cfc69837185640a88202b06_08e24b793aafb88543dd86dea19a0572_WhatsApp Image 2025-02-14 at 21.20.36_cb528a9e.jpg', 'c5411b4090046dd2c4d742de220f8a6a_Cuestionario.pdf', '13131', '2004-10-23', NULL, 'Desarrollo de software', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Informática/IT', NULL),
(2, 'Administración', NULL),
(3, 'Marketing', NULL),
(4, 'Ventas', NULL),
(5, 'Recursos Humanos', NULL),
(6, 'Educación', NULL),
(7, 'Salud', NULL),
(8, 'Ingeniería', NULL),
(9, 'Finanzas', NULL),
(10, 'Diseño', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cv`
--

CREATE TABLE `cv` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `ciudad` varchar(100) NOT NULL,
  `institucion` varchar(255) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `fechas_estudio` varchar(100) NOT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `puesto` varchar(255) DEFAULT NULL,
  `fechas_trabajo` varchar(100) DEFAULT NULL,
  `habilidades` text NOT NULL,
  `idiomas` varchar(255) DEFAULT NULL,
  `objetivo` text DEFAULT NULL,
  `logros` text DEFAULT NULL,
  `disponibilidad` varchar(50) NOT NULL,
  `redes` varchar(255) DEFAULT NULL,
  `referencias` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `cv_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `codigo_postal` varchar(10) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `nivel_educativo` varchar(100) DEFAULT NULL,
  `experiencia_laboral` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `usuario_id`, `nombre`, `direccion`, `telefono`, `descripcion`, `logo`, `sitio_web`) VALUES
(1, 3, 'Mandys corp', NULL, NULL, NULL, NULL, NULL),
(2, 5, 'Mandys corporation', 'Santo Domingo', '8096984224', 'somos una empresa de construccion de conocimientos', '1745367933_Screenshot 2025-04-15 201957.png', 'https://github.com/Manushark/Plataforma-de-empleos.git');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas`
--

CREATE TABLE `ofertas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `requisitos` text NOT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activa','cerrada') DEFAULT 'activa',
  `categoria_id` int(11) DEFAULT 1,
  `tipo_contrato` varchar(50) DEFAULT 'Tiempo completo',
  `ubicacion` varchar(100) DEFAULT '',
  `salario` varchar(100) DEFAULT '',
  `beneficios` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ofertas`
--

INSERT INTO `ofertas` (`id`, `empresa_id`, `titulo`, `descripcion`, `requisitos`, `fecha_publicacion`, `estado`, `categoria_id`, `tipo_contrato`, `ubicacion`, `salario`, `beneficios`) VALUES
(4, 1, 'Desarrollador jr', 'vv', 'v', '2025-04-22 02:38:28', 'activa', 6, 'Freelance', 'Sambil', '500', 'v'),
(6, 2, 'Desarrollador de jr php', 'experiencia minima de 1A', 'Manera base de datos sql \r\ny php', '2025-04-23 00:28:14', 'activa', 1, 'Medio tiempo', 'Sambil', '1000', 'seguro medico'),
(7, 2, 'DBA', 'Trabajo completo', 'saber de mysql', '2025-04-23 00:30:16', 'activa', 1, 'Tiempo completo', 'Sambil', '800', 'mucho dinero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tipo` enum('candidato','empresa') NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `password`, `tipo`, `fecha_registro`) VALUES
(1, 'manuelrivas.1023@gmail.com', '$2y$10$l4/LmvfLqkrJ3r2s9sfiyOchrFEY6oy0CXn6CsvMEBe3ZzsP/fDKu', 'candidato', '2025-04-15 16:28:54'),
(2, 'mrivas.1333@hmail.com', '$2y$10$U1OHISplr3MBQPomOyBICuhDLk4fic4entQSzTJlEB65aSHzF.gG2', 'candidato', '2025-04-15 17:54:36'),
(3, 'mrivas.1333@hkdmail.com', '$2y$10$byQr6sXMFOmJsA4vIxF.aeKz8htA3eRxfFsvkrQaPD1.OkBzYLGtC', 'empresa', '2025-04-15 21:27:19'),
(4, 'Pedromatines99@gmail.com', '$2y$10$bN7Pt71gpX7V6zSXc6fpXeQTM.pkX/oUeUM6222BBUmO/PvYJd6Mu', 'candidato', '2025-04-23 00:14:32'),
(5, 'Mandy05@gmail.com', '$2y$10$mIR4T11WXsBq1aMlDhRdiediBRaSXo7RFkdqPBM3fX3xARwiwCq2S', 'empresa', '2025-04-23 00:21:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_aplicacion` (`candidato_id`,`oferta_id`),
  ADD KEY `oferta_id` (`oferta_id`);

--
-- Indices de la tabla `candidatos`
--
ALTER TABLE `candidatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cv`
--
ALTER TABLE `cv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

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
-- AUTO_INCREMENT de la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `candidatos`
--
ALTER TABLE `candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `cv`
--
ALTER TABLE `cv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  ADD CONSTRAINT `aplicaciones_ibfk_1` FOREIGN KEY (`candidato_id`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aplicaciones_ibfk_2` FOREIGN KEY (`oferta_id`) REFERENCES `ofertas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `candidatos`
--
ALTER TABLE `candidatos`
  ADD CONSTRAINT `candidatos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cv`
--
ALTER TABLE `cv`
  ADD CONSTRAINT `cv_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `empresas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ofertas`
--
ALTER TABLE `ofertas`
  ADD CONSTRAINT `ofertas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
