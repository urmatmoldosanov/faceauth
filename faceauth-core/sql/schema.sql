CREATE TABLE IF NOT EXISTS tenants (
  id VARCHAR(64) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  domain VARCHAR(255) NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  tenant_secret VARCHAR(255) NOT NULL,
  max_students INT NOT NULL DEFAULT 0,
  max_sessions INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS licenses (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  tenant_id VARCHAR(64) NOT NULL,
  license_key VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'active',
  paid_until DATETIME NOT NULL,
  grace_until DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_licenses_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE IF NOT EXISTS sessions (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  session_token VARCHAR(255) NOT NULL UNIQUE,
  tenant_id VARCHAR(64) NOT NULL,
  user_id VARCHAR(128) NOT NULL,
  quiz_id VARCHAR(128) NOT NULL,
  status VARCHAR(32) NOT NULL,
  started_at DATETIME NOT NULL,
  ended_at DATETIME NULL,
  violations INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_sessions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

CREATE TABLE IF NOT EXISTS verifications (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  session_token VARCHAR(255) NOT NULL,
  result VARCHAR(32) NOT NULL,
  score DOUBLE NOT NULL,
  timestamp DATETIME NOT NULL,
  INDEX idx_verifications_session (session_token)
);
