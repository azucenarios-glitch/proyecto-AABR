-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-11-2026 a las 02:06:45
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `escuela`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id_alumno` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apaterno` varchar(50) NOT NULL,
  `amaterno` varchar(50) DEFAULT NULL,
  `dom` varchar(100) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `id_grupo` varchar(50) DEFAULT NULL,
  `estatus` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id_alumno`, `nombre`, `apaterno`, `amaterno`, `dom`, `mail`, `tel`, `id_grupo`, `estatus`) VALUES
(1, 'nruruc', 'dm cd', 'Brito Rios', 'dkcvfd', 'd@mfc', '5566638548', '2', 1),
(2, 'nruruc', 'dm cd', 'Brito Rios', 'dkcvfd', 'd@mfc', '5566638548', '8t1', 0),
(3, 'Alma', 'a', 'Brito Rios', 'a', 'a@gmien.com', '5566638548', '4t2', 1),
(4, 'Alma', 'Brito', 'Brito', 'NURU', 'azulzcn@gmail.com', '5566638548', '4t2', 0),
(5, 'Alma', 'Brito', 'Brito', 'NURU', 'azulzcn@gmail.com', '5566638548', '4t1', 1),
(6, 'Alma', 'Brito', 'Brito', 'NURU', 'azulzcn@gmail.com', '5566638548', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asigmat`
--

CREATE TABLE `asigmat` (
  `id_asigmat` int(11) NOT NULL,
  `id_mat` varchar(50) NOT NULL,
  `id_alumno` varchar(50) NOT NULL,
  `id_profesor` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asigmat`
--

INSERT INTO `asigmat` (`id_asigmat`, `id_mat`, `id_alumno`, `id_profesor`) VALUES
(1, 'cv', 'cv', 'cv'),
(6, '1', '3', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ba_grupos`
--

CREATE TABLE `ba_grupos` (
  `id_baja` int(11) NOT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `nombre_alumno` varchar(100) NOT NULL,
  `grupo` varchar(50) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `semestre` varchar(20) NOT NULL,
  `estatus_grupo` varchar(20) NOT NULL,
  `fecha_baja` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capcal`
--

CREATE TABLE `capcal` (
  `id_capcal` int(11) NOT NULL,
  `id_alumno` varchar(50) NOT NULL,
  `cal` decimal(5,2) NOT NULL,
  `id_unidad` int(11) NOT NULL,
  `id_mat` varchar(50) NOT NULL DEFAULT '',
  `calificacion` decimal(6,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `capcal`
--

INSERT INTO `capcal` (`id_capcal`, `id_alumno`, `cal`, `id_unidad`, `id_mat`, `calificacion`) VALUES
(7, '2', 0.00, 4, '1', 60.00),
(8, '5', 0.00, 1, '2', 80.00),
(9, '3', 0.00, 1, '2', 70.00),
(10, '6', 0.00, 1, '2', 90.00),
(11, '1', 0.00, 1, '2', 70.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `nombre_alumno` varchar(100) NOT NULL,
  `grupo` varchar(50) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `semestre` varchar(20) NOT NULL,
  `estatus_grupo` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `nombre_alumno`, `grupo`, `matricula`, `carrera`, `semestre`, `estatus_grupo`) VALUES
(2, 'alma', '8t1', 'L251250033@gamadero2.tecnm.mx', 'tic´s', '8', 'Activo'),
(3, '22', '4t1', 'L251250033@gamadero2.tecnm.mx', 'tic´s', '4', 'Activo'),
(4, '', '4t2', '', '', '', ''),
(5, '', '1t1', '', '', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insgrup`
--

CREATE TABLE `insgrup` (
  `id_in` int(11) NOT NULL,
  `id_grupo` varchar(50) NOT NULL,
  `id_alumno` varchar(50) NOT NULL,
  `status_ins` varchar(20) DEFAULT 'ACTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insgrup`
--

INSERT INTO `insgrup` (`id_in`, `id_grupo`, `id_alumno`, `status_ins`) VALUES
(1, 'hhn', 'cdxs', 'ACTA'),
(2, 'hhn', 'cdxs', 'ACTA'),
(3, 'hhn', 'cdxs', 'ACTA'),
(4, 'hhn', 'cdxs', 'ACTA'),
(5, 'hhn', 'cdxs', 'ACTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id_mat` int(11) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `id_prof` int(11) NOT NULL,
  `estatus` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id_mat`, `descripcion`, `id_prof`, `estatus`) VALUES
(1, ' nmm,', 2, 1),
(2, 'matematica', 1, 1),
(3, 'matematica', 1, 1),
(4, 'matematica', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id_prof` int(11) NOT NULL,
  `nom_prof` varchar(50) NOT NULL,
  `apaterno_prof` varchar(50) NOT NULL,
  `amaterno_prof` varchar(50) DEFAULT NULL,
  `estatus` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id_prof`, `nom_prof`, `apaterno_prof`, `amaterno_prof`, `estatus`) VALUES
(1, 'Alma', 'a', 'Brito Rios', 1),
(2, 'Alma', 'Brito', 'Brito', 1),
(3, 'Alma', 'Brito', 'Brito', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puestos`
--

INSERT INTO `puestos` (`id_puesto`, `nombre`, `estatus`) VALUES
(1, 'Administrador', 1),
(2, 'Coordinador', 1),
(3, 'Capturista', 1),
(4, 'alma', 1),
(5, 'alma', 1),
(6, 'alma', 1),
(7, 'alma', 1),
(8, 'alma', 1),
(9, 'alma', 1),
(10, 'Alumno', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad`
--

CREATE TABLE `unidad` (
  `id_unidad` int(11) NOT NULL,
  `descripcion` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidad`
--

INSERT INTO `unidad` (`id_unidad`, `descripcion`) VALUES
(1, ' nmm,'),
(2, 'alma'),
(3, 'alma'),
(4, ' nmm,'),
(5, ' nmm,'),
(6, ' nmm,'),
(7, 'matematica'),
(8, ' nmm,'),
(9, 'alma azucena'),
(10, 'alex');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_puesto` int(11) NOT NULL,
  `estatus` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `usuario`, `password`, `nombre`, `id_puesto`, `estatus`) VALUES
(20, 'rudy2', '$2y$10$q1c1PT1Y4pFpSfXHzXOoIOJkmxS8lu4I0PAq5UvfIdJ4oLkS9eQfO', 'adsc', 1, 0),
(21, 'nuru', '$2y$10$uLnNSMkHZK.r2Qc5KTN8cuFlVbAbHHuj0JwvRnF4PLk7UBLgHlfRC', 'alma azucena', 1, 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id_alumno`);

--
-- Indices de la tabla `asigmat`
--
ALTER TABLE `asigmat`
  ADD PRIMARY KEY (`id_asigmat`);

--
-- Indices de la tabla `ba_grupos`
--
ALTER TABLE `ba_grupos`
  ADD PRIMARY KEY (`id_baja`);

--
-- Indices de la tabla `capcal`
--
ALTER TABLE `capcal`
  ADD PRIMARY KEY (`id_capcal`),
  ADD KEY `id_unidad` (`id_unidad`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`);

--
-- Indices de la tabla `insgrup`
--
ALTER TABLE `insgrup`
  ADD PRIMARY KEY (`id_in`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_mat`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id_prof`);

--
-- Indices de la tabla `puestos`
--
ALTER TABLE `puestos`
  ADD PRIMARY KEY (`id_puesto`);

--
-- Indices de la tabla `unidad`
--
ALTER TABLE `unidad`
  ADD PRIMARY KEY (`id_unidad`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_usuarios_puestos` (`id_puesto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id_alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `asigmat`
--
ALTER TABLE `asigmat`
  MODIFY `id_asigmat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ba_grupos`
--
ALTER TABLE `ba_grupos`
  MODIFY `id_baja` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `capcal`
--
ALTER TABLE `capcal`
  MODIFY `id_capcal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `insgrup`
--
ALTER TABLE `insgrup`
  MODIFY `id_in` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id_mat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id_prof` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `unidad`
--
ALTER TABLE `unidad`
  MODIFY `id_unidad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `capcal`
--
ALTER TABLE `capcal`
  ADD CONSTRAINT `capcal_ibfk_1` FOREIGN KEY (`id_unidad`) REFERENCES `unidad` (`id_unidad`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_puestos` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
