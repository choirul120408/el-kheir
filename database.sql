CREATE DATABASE IF NOT EXISTS smk_bnb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smk_bnb;

CREATE TABLE IF NOT EXISTS school_profile (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  school_name VARCHAR(160) NOT NULL,
  location VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  about_primary TEXT NOT NULL,
  about_secondary TEXT NOT NULL,
  map_query VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS school_features (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  icon VARCHAR(16) NOT NULL,
  title VARCHAR(120) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new', 'read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_messages_created_at (created_at)
) ENGINE=InnoDB;

INSERT INTO school_profile (
  id, school_name, location, description, about_primary, about_secondary, map_query
) VALUES (
  1,
  'SMK Bangun Nusa Bangsa',
  'Karadenan · Bogor',
  'Selamat datang di SMK Bangun Nusa Bangsa, Karadenan. Tempat belajar untuk membangun keterampilan, karakter, dan keberanian meraih masa depan.',
  'SMK Bangun Nusa Bangsa hadir di Karadenan, Bogor sebagai ruang bagi generasi muda untuk terus belajar dan mempersiapkan diri menghadapi dunia yang terus berubah.',
  'Kami percaya pendidikan kejuruan yang kuat tumbuh dari perpaduan pengetahuan, pengalaman praktik, karakter baik, dan semangat untuk terus berkembang.',
  'SMK Bangun Nusa Bangsa Karadenan'
)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO school_features (icon, title, description, sort_order) VALUES
  ('⌘', 'Keterampilan nyata', 'Menghubungkan proses belajar dengan keterampilan yang berguna untuk menghadapi tantangan masa depan.', 1),
  ('✳', 'Karakter bertumbuh', 'Menumbuhkan rasa tanggung jawab, rasa ingin tahu, dan keyakinan untuk terus mengembangkan diri.', 2),
  ('⤴', 'Berani berkarya', 'Mendorong kreativitas dan kolaborasi agar ide baik dapat berkembang menjadi karya yang bermanfaat.', 3)
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  role ENUM('siswa', 'guru', 'admin') NOT NULL DEFAULT 'siswa',
  major VARCHAR(100) NULL,
  class_name VARCHAR(60) NULL,
  academic_tokens INT UNSIGNED NOT NULL DEFAULT 1000,
  gpa VARCHAR(10) NOT NULL DEFAULT '3.85',
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (username, email, password, full_name, role, major, class_name, academic_tokens, gpa, avatar)
VALUES
  ('202401001', 'siswa@smkbnb.sch.id', '$2y$12$EsTgTqUKKu51ob4b9wKyqe4LC.XolvvAleL9UEgsJfNjBJ.bkn20u', 'Ahmad Rizky Pratama', 'siswa', 'Rekayasa Perangkat Lunak', 'XII RPL 1', 2850, '3.92', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80'),
  ('198503152010011002', 'guru@smkbnb.sch.id', '$2y$12$XJ.Lqd8vqogbhwI5sq0MIuigEmgyFrLdnrgTPiKIZ45Wuk9fod8aK', 'Budi Santoso, S.Kom., M.T.', 'guru', 'Teknik Informatika & RPL', 'Instruktur Senior', 8420, '4.00', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80'),
  ('admin', 'admin@smkbnb.sch.id', '$2y$12$AjgI7uKvIgpbZ6Z79xzIN.5QAI4j1CoiW9r1d6V.30Fdyyb1BtPbi', 'Administrator Portal BNB', 'admin', 'Biro Sistem Informasi', 'Super Admin', 50000, '4.00', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&q=80')
ON DUPLICATE KEY UPDATE id = id;
