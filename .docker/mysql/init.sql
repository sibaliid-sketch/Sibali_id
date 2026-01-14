-- DB init scripts
-- Create initial schemas / seed minimal data for dev
-- MySQL init
-- Create DB, user, grant privileges, create minimal roles & admin user hashed, create migrations table placeholder, optionally seed test data. Keep secrets out; use env placeholders in scripts invoked by CI.

-- Create database
CREATE DATABASE IF NOT EXISTS sibaliid CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER IF NOT EXISTS 'sibali'@'%' IDENTIFIED BY 'Sibali123!';

-- Grant privileges
GRANT ALL PRIVILEGES ON sibaliid.* TO 'sibali'@'%';

-- Flush privileges
FLUSH PRIVILEGES;

-- Use database
USE sibaliid;

-- Create migrations table (placeholder for Laravel migrations)
CREATE TABLE IF NOT EXISTS migrations (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    migration varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    batch int(11) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create roles table (minimal roles)
CREATE TABLE IF NOT EXISTS roles (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    guard_name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY roles_name_guard_name_unique (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert basic roles
INSERT INTO roles (name, guard_name, created_at, updated_at) VALUES
('Basic Staff', 'web', NOW(), NOW()),
('Senior Staff', 'web', NOW(), NOW()),
('Leader', 'web', NOW(), NOW()),
('Supervisor', 'web', NOW(), NOW()),
('Manager', 'web', NOW(), NOW()),
('Header', 'web', NOW(), NOW()),
('Executives', 'web', NOW(), NOW());

-- Create users table (minimal admin user)
CREATE TABLE IF NOT EXISTS users (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    email_verified_at timestamp NULL DEFAULT NULL,
    password varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    remember_token varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert admin user (password: admin123 - hashed)
INSERT INTO users (name, email, password, created_at, updated_at) VALUES
('Admin User', 'admin@sibali.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Create password_reset_tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    token varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create failed_jobs table
CREATE TABLE IF NOT EXISTS failed_jobs (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    uuid varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    connection text COLLATE utf8mb4_unicode_ci NOT NULL,
    queue text COLLATE utf8mb4_unicode_ci NOT NULL,
    payload longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    exception longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    failed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY failed_jobs_uuid_unique (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create personal_access_tokens table
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    tokenable_type varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    tokenable_id bigint(20) unsigned NOT NULL,
    name varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    token varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
    abilities text COLLATE utf8mb4_unicode_ci,
    last_used_at timestamp NULL DEFAULT NULL,
    expires_at timestamp NULL DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY personal_access_tokens_token_unique (token),
    KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
