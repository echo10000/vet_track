-- database.sql

CREATE DATABASE IF NOT EXISTS vettrack_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vettrack_db;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','owner') NOT NULL DEFAULT 'owner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE owners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_owners_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE animals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    species VARCHAR(100) NOT NULL DEFAULT 'other',
    breed VARCHAR(100) DEFAULT NULL,
    age DECIMAL(5,2) DEFAULT NULL,
    weight DECIMAL(6,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_animals_owner
        FOREIGN KEY (owner_id)
        REFERENCES owners(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    animal_id INT UNSIGNED NOT NULL,
    staff_id INT UNSIGNED NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','confirmed','done','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_animal
        FOREIGN KEY (animal_id)
        REFERENCES animals(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_staff
        FOREIGN KEY (staff_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE health_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    animal_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    diagnosis TEXT NOT NULL,
    treatment TEXT NOT NULL,
    notes TEXT,
    recorded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_health_records_animal
        FOREIGN KEY (animal_id)
        REFERENCES animals(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_health_records_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_health_records_recorded_by
        FOREIGN KEY (recorded_by)
        REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;
