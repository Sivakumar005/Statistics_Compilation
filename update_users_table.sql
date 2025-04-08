-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing foreign key constraints
ALTER TABLE datasets DROP FOREIGN KEY datasets_ibfk_1;
ALTER TABLE report_notes DROP FOREIGN KEY report_notes_ibfk_1;
ALTER TABLE user_profiles DROP FOREIGN KEY user_profiles_ibfk_1;

-- Create temporary table with new structure
CREATE TABLE users_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    verification_token VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Copy data from old table to new table (only existing columns)
INSERT INTO users_new (id, username, email, password)
SELECT id, username, email, password
FROM users;

-- Drop the old table
DROP TABLE users;

-- Rename the new table to the original name
RENAME TABLE users_new TO users;

-- Recreate foreign key constraints
ALTER TABLE datasets ADD CONSTRAINT datasets_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE report_notes ADD CONSTRAINT report_notes_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id);
ALTER TABLE user_profiles ADD CONSTRAINT user_profiles_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1; 