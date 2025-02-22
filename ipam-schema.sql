USE ip_management;

-- Drop tables if they exist (optional: use only in development/testing)
DROP TABLE IF EXISTS custom_fields;
DROP TABLE IF EXISTS history_log;
DROP TABLE IF EXISTS ips;
DROP TABLE IF EXISTS subnets;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255),
  role ENUM('admin', 'user', 'guest') DEFAULT 'guest',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Subnets table
CREATE TABLE subnets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  subnet VARCHAR(50) NOT NULL,
  description TEXT,
  vlan_id VARCHAR(20),
  created_by INT,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- IPs table with additional columns:
-- - type (hardware type), defaulting to 'Unknown'
-- - location (deployment site), defaulting to 'Not Specified'
-- - created_by: the user ID of the record creator
-- - last_updated: auto-updated timestamp on changes
CREATE TABLE ips (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ip_address VARCHAR(45) UNIQUE NOT NULL,
  subnet_id INT,
  status ENUM('Available', 'Reserved', 'Assigned', 'Expired') DEFAULT 'Available',
  description TEXT,
  assigned_to VARCHAR(255),
  owner VARCHAR(100),
  type VARCHAR(100) NOT NULL DEFAULT 'Unknown',
  location VARCHAR(255) NOT NULL DEFAULT 'Not Specified',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT,
  FOREIGN KEY (subnet_id) REFERENCES subnets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- History log table
CREATE TABLE history_log (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ip_id INT,
  user_id INT,
  action VARCHAR(255),
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ip_id) REFERENCES ips(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Custom fields table
CREATE TABLE custom_fields (
  id INT PRIMARY KEY AUTO_INCREMENT,
  ip_id INT,
  field_name VARCHAR(50),
  field_value TEXT,
  FOREIGN KEY (ip_id) REFERENCES ips(id)
);
