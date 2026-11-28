-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-11-2026 a las 06:17:51
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
  `ID_ALUMNOS` int(11) NOT NULL,
  `MATRICULA` varchar(15) NOT NULL,
  `NOMBRE` varchar(30) NOT NULL,
  `APELLIDO_PATERNO` varchar(15) NOT NULL,
  `APELLIDO_MATERNO` varchar(15) NOT NULL,
  `DOMICILIO` varchar(80) NOT NULL,
  `CORREO` varchar(50) NOT NULL,
  `TELEFONO` varchar(35) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`ID_ALUMNOS`, `MATRICULA`, `NOMBRE`, `APELLIDO_PATERNO`, `APELLIDO_MATERNO`, `DOMICILIO`, `CORREO`, `TELEFONO`, `ESTATUS`) VALUES
(10, '261260020', 'Nuru', 'Hernández', 'Cruz', 'Rosales', 'nurucruz@gmail.com', '5505042019', 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura`
--

CREATE TABLE `asignatura` (
  `ID_ASIGNATURA` int(11) NOT NULL,
  `ID_MATERIAS` int(11) NOT NULL,
  `ID_ALUMNOS` int(11) NOT NULL,
  `ID_PROFESORES` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asignatura`
--

INSERT INTO `asignatura` (`ID_ASIGNATURA`, `ID_MATERIAS`, `ID_ALUMNOS`, `ID_PROFESORES`) VALUES
(10, 9, 10, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `ID_CALIFICACION` int(11) NOT NULL,
  `ID_ALUMNOS` int(11) NOT NULL,
  `ID_MATERIAS` int(11) NOT NULL,
  `CALIFICACION` decimal(4,1) NOT NULL,
  `ID_UNIDAD` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificaciones`
--

INSERT INTO `calificaciones` (`ID_CALIFICACION`, `ID_ALUMNOS`, `ID_MATERIAS`, `CALIFICACION`, `ID_UNIDAD`) VALUES
(9, 10, 9, 9.8, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `ID_GRUPOS` int(11) NOT NULL,
  `NOMBRE_GRUPO` varchar(20) NOT NULL,
  `TURNO` varchar(15) NOT NULL,
  `SEMESTRE` varchar(10) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`ID_GRUPOS`, `NOMBRE_GRUPO`, `TURNO`, `SEMESTRE`, `ESTATUS`) VALUES
(9, '9T1', 'Vespertino', '9', 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo_alumno`
--

CREATE TABLE `grupo_alumno` (
  `ID_GRUPOS` int(11) NOT NULL,
  `ID_ALUMNOS` int(11) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupo_alumno`
--

INSERT INTO `grupo_alumno` (`ID_GRUPOS`, `ID_ALUMNOS`, `ESTATUS`) VALUES
(9, 10, 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `ID_MATERIAS` int(11) NOT NULL,
  `CLAVE_MATERIA` varchar(15) NOT NULL,
  `NOMBRE_MATERIA` varchar(50) NOT NULL,
  `CREDITOS` varchar(5) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`ID_MATERIAS`, `CLAVE_MATERIA`, `NOMBRE_MATERIA`, `CREDITOS`, `ESTATUS`) VALUES
(9, 'M1234', 'Español', '0', 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `ID_PROFESORES` int(11) NOT NULL,
  `CLAVE` varchar(15) NOT NULL,
  `NOMBRE` varchar(30) NOT NULL,
  `APELLIDO_PATERNO` varchar(15) NOT NULL,
  `APELLIDO_MATERNO` varchar(15) NOT NULL,
  `CORREO` varchar(50) NOT NULL,
  `TELEFONO` varchar(35) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`ID_PROFESORES`, `CLAVE`, `NOMBRE`, `APELLIDO_PATERNO`, `APELLIDO_MATERNO`, `CORREO`, `TELEFONO`, `ESTATUS`) VALUES
(9, '190503', 'Alex', 'Hernández', 'Juárez', 'alexjuarez@gmail.com', '5615165679', 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puesto`
--

CREATE TABLE `puesto` (
  `ID_PUESTO` int(11) NOT NULL,
  `DESCRIPCION_PUESTO` varchar(50) NOT NULL,
  `STATUS_PUESTO` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `puesto`
--

INSERT INTO `puesto` (`ID_PUESTO`, `DESCRIPCION_PUESTO`, `STATUS_PUESTO`) VALUES
(1, 'Administrador', 'ALTA'),
(2, 'Maestro', 'ALTA'),
(3, 'Alumno', 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad`
--

CREATE TABLE `unidad` (
  `ID_UNIDAD` int(11) NOT NULL,
  `ESTATUS` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `unidad`
--

INSERT INTO `unidad` (`ID_UNIDAD`, `ESTATUS`) VALUES
(1, 'ALTA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `ID_USER` int(11) NOT NULL,
  `NOMBREUSER` varchar(50) NOT NULL,
  `PWUSER` varchar(40) NOT NULL,
  `PUESTO` int(11) NOT NULL,
  `ID_PROFESORES` int(11) DEFAULT NULL,
  `ID_ALUMNOS` int(11) DEFAULT NULL,
  `STATUS_USER` varchar(4) NOT NULL DEFAULT 'ALTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`ID_USER`, `NOMBREUSER`, `PWUSER`, `PUESTO`, `ID_PROFESORES`, `ID_ALUMNOS`, `STATUS_USER`) VALUES
(1, 'Alma', 'f865b53623b121fd34ee5426c792e5c33af8c227', 1, NULL, NULL, 'ALTA'),
(2, 'nuru', '59682de86c0add02cb639fa706b8b22a647fc6c3', 3, NULL, 10, 'ALTA'),
(3, 'Alex', '731990ec145624822eee97d6bedb0a79efb28ccb', 2, 9, NULL, 'ALTA');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`ID_ALUMNOS`);

--
-- Indices de la tabla `asignatura`
--
ALTER TABLE `asignatura`
  ADD PRIMARY KEY (`ID_ASIGNATURA`),
  ADD UNIQUE KEY `uq_materia_alumno` (`ID_MATERIAS`,`ID_ALUMNOS`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`ID_CALIFICACION`),
  ADD UNIQUE KEY `uq_alumno_materia_unidad` (`ID_ALUMNOS`,`ID_MATERIAS`,`ID_UNIDAD`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`ID_GRUPOS`);

--
-- Indices de la tabla `grupo_alumno`
--
ALTER TABLE `grupo_alumno`
  ADD PRIMARY KEY (`ID_GRUPOS`,`ID_ALUMNOS`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`ID_MATERIAS`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`ID_PROFESORES`);

--
-- Indices de la tabla `puesto`
--
ALTER TABLE `puesto`
  ADD PRIMARY KEY (`ID_PUESTO`);

--
-- Indices de la tabla `unidad`
--
ALTER TABLE `unidad`
  ADD PRIMARY KEY (`ID_UNIDAD`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`ID_USER`),
  ADD UNIQUE KEY `uq_nombreuser` (`NOMBREUSER`),
  ADD UNIQUE KEY `uq_usuario_profesor` (`ID_PROFESORES`),
  ADD UNIQUE KEY `uq_usuario_alumno` (`ID_ALUMNOS`),
  ADD KEY `fk_usuarios_puesto` (`PUESTO`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `ID_ALUMNOS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `asignatura`
--
ALTER TABLE `asignatura`
  MODIFY `ID_ASIGNATURA` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `ID_CALIFICACION` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `ID_GRUPOS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `ID_MATERIAS` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `ID_PROFESORES` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `puesto`
--
ALTER TABLE `puesto`
  MODIFY `ID_PUESTO` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `ID_USER` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_alumno` FOREIGN KEY (`ID_ALUMNOS`) REFERENCES `alumnos` (`ID_ALUMNOS`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuarios_profesor` FOREIGN KEY (`ID_PROFESORES`) REFERENCES `profesores` (`ID_PROFESORES`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuarios_puesto` FOREIGN KEY (`PUESTO`) REFERENCES `puesto` (`ID_PUESTO`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
