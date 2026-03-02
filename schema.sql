CREATE DATABASE IF NOT EXISTS fangke CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fangke;

CREATE TABLE IF NOT EXISTS temp_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  random_id VARCHAR(32) NOT NULL UNIQUE,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  temp_link_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,
  company VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  has_car TINYINT(1) NOT NULL,
  car_number VARCHAR(30) DEFAULT NULL,
  openid VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_temp_openid (temp_link_id, openid),
  FOREIGN KEY (temp_link_id) REFERENCES temp_links(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sign_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  temp_link_id INT NOT NULL,
  method ENUM('qrcode') NOT NULL DEFAULT 'qrcode',
  total_times INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (temp_link_id) REFERENCES temp_links(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sign_task_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sign_task_id INT NOT NULL,
  slot_index INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  FOREIGN KEY (sign_task_id) REFERENCES sign_tasks(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sign_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sign_task_id INT NOT NULL,
  visitor_id INT NOT NULL,
  slot_index INT NOT NULL,
  signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_sign (sign_task_id, visitor_id, slot_index),
  FOREIGN KEY (sign_task_id) REFERENCES sign_tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
);
