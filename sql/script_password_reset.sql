-- Table pour les tokens, mdp oubliés

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expiration DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tokens pour réinitialisation de mot de passe';

-- Index qui permettent d'optimiser les recherches
CREATE INDEX idx_token ON password_reset_tokens(token);
CREATE INDEX idx_expiration ON password_reset_tokens(expiration);
CREATE INDEX idx_user_id ON password_reset_tokens(user_id);
