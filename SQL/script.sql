CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario VARCHAR(100) NOT NULL UNIQUE,
  correo VARCHAR(100) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  token_remember VARCHAR(255),
  token_expira  INT
);

CREATE TABLE tipos_comida (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL
);

CREATE TABLE preferencias_usuario (
  usuario_id INT,
  tipo_comida_id INT,
  PRIMARY KEY (usuario_id, tipo_comida_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_comida_id) REFERENCES tipos_comida(id) ON DELETE CASCADE
);

CREATE TABLE restaurantes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  precio INT NOT NULL,
  valoraciones DECIMAL (3,1),
  ubicacion TEXT NOT NULL,
  descripcion TEXT NOT NULL,
  latitud DECIMAL(10, 8),
  longitud DECIMAL(11, 8),
  imagenes JSON
);

CREATE TABLE restaurante_tipo_comida (
  restaurante_id INT,
  tipo_comida_id INT,
  PRIMARY KEY (restaurante_id, tipo_comida_id),
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_comida_id) REFERENCES tipos_comida(id) ON DELETE CASCADE
);

CREATE TABLE horarios_restaurante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurante_id INT NOT NULL,
    dia_semana VARCHAR(20) NOT NULL,
    hora_apertura TIME NOT NULL,
    hora_cierre TIME NOT NULL,
    FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id)
);


CREATE TABLE historial_visitas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario_id INT,
  restaurante_id INT,
  fecha_visita DATE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (restaurante_id) REFERENCES restaurantes(id) ON DELETE SET NULL
);

CREATE TABLE planes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(8,2) NOT NULL,
  duracion_dias INT NOT NULL
);

CREATE TABLE suscripciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  plan_id INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  estado ENUM('activa', 'expirada', 'cancelada') DEFAULT 'activa',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES planes(id)
);

CREATE TABLE admin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  usuario VARCHAR(100) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL
);