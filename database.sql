CREATE DATABASE IF NOT EXISTS shareplate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shareplate_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  role ENUM('donor','recipient','charity','admin') NOT NULL DEFAULT 'recipient',
  address VARCHAR(255) NOT NULL DEFAULT '',
  locality VARCHAR(120) NOT NULL DEFAULT '',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role), INDEX idx_users_locality (locality)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS food_donations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id INT UNSIGNED NOT NULL,
  food_name VARCHAR(150) NOT NULL,
  category ENUM('Vegetarian','Non-Vegetarian','Vegan','Bakery','Fruits','Other') NOT NULL,
  description TEXT NOT NULL,
  ingredients TEXT NULL,
  allergens VARCHAR(500) NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit VARCHAR(30) NOT NULL,
  remaining_quantity DECIMAL(10,2) NOT NULL,
  prepared_at DATETIME NOT NULL,
  expiry_at DATETIME NOT NULL,
  pickup_address VARCHAR(255) NOT NULL,
  locality VARCHAR(120) NOT NULL,
  contact_number VARCHAR(30) NOT NULL,
  image VARCHAR(255) NULL,
  pickup_instructions TEXT NULL,
  status ENUM('Active','Partially Collected','Fully Collected','Expired','Cancelled','Deactivated') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_donation_donor FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_donations_status_expiry (status, expiry_at), INDEX idx_donations_category (category), INDEX idx_donations_locality (locality), INDEX idx_donations_name (food_name), INDEX idx_donations_donor (donor_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collection_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id INT UNSIGNED NOT NULL,
  recipient_id INT UNSIGNED NOT NULL,
  requested_quantity DECIMAL(10,2) NOT NULL,
  status ENUM('Pending','Accepted','Rejected','Collected') NOT NULL DEFAULT 'Pending',
  request_message VARCHAR(500) NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_request_donation FOREIGN KEY (donation_id) REFERENCES food_donations(id) ON DELETE CASCADE,
  CONSTRAINT fk_request_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_donation_recipient (donation_id, recipient_id),
  INDEX idx_requests_status (status), INDEX idx_requests_recipient (recipient_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collection_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donation_id INT UNSIGNED NOT NULL,
  request_id INT UNSIGNED NOT NULL UNIQUE,
  donor_id INT UNSIGNED NOT NULL,
  recipient_id INT UNSIGNED NOT NULL,
  collected_quantity DECIMAL(10,2) NOT NULL,
  collected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_record_donation FOREIGN KEY (donation_id) REFERENCES food_donations(id) ON DELETE CASCADE,
  CONSTRAINT fk_record_request FOREIGN KEY (request_id) REFERENCES collection_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_record_donor FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_record_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_records_collected_at (collected_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT UNSIGNED NOT NULL,
  donation_id INT UNSIGNED NOT NULL,
  reason VARCHAR(120) NOT NULL,
  description TEXT NULL,
  status ENUM('Pending','Reviewed','Resolved','Dismissed') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_report_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_report_donation FOREIGN KEY (donation_id) REFERENCES food_donations(id) ON DELETE CASCADE,
  INDEX idx_reports_status (status)
) ENGINE=InnoDB;

-- Optional demonstration listing; donor_id references the first registered user only, so add sample data through the app.
