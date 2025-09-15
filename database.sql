-- 1) Basis: Teilnehmer
CREATE TABLE participants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Paare pro Jahr (A < B, damit jede Paarung genau einmal gespeichert wird)
CREATE TABLE pairs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  round_year INT NOT NULL,
  a_id INT NOT NULL,
  b_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notified_at DATETIME NULL,
  CONSTRAINT chk_order CHECK (a_id < b_id),
  CONSTRAINT fk_pairs_a FOREIGN KEY (a_id) REFERENCES participants(id) ON DELETE CASCADE,
  CONSTRAINT fk_pairs_b FOREIGN KEY (b_id) REFERENCES participants(id) ON DELETE CASCADE,
  UNIQUE KEY uq_year_pair (round_year, a_id, b_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Einstellungen
CREATE TABLE settings (
  skey VARCHAR(50) PRIMARY KEY,
  svalue VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (skey, svalue) VALUES
  ('registration_open', '1'),              -- 1 = Anmelden erlaubt
  ('current_round_year', YEAR(CURDATE())), -- aktuelles Jahr
  ('admin_password_hash', ''),             -- später via admin setzen
  ('site_title', 'Jährliche Paarziehung');

-- Optional: Logt fehlgeschlagene Zustellungen etc.
CREATE TABLE mail_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ok TINYINT(1) NOT NULL DEFAULT 0,
  error TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
