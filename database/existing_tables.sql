-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user_profiles table
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100),
    bio TEXT,
    location VARCHAR(100),
    phone VARCHAR(20),
    website VARCHAR(255),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create datasets table
CREATE TABLE IF NOT EXISTS datasets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dataset_name_per_user (user_id, dataset_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Create charts table
CREATE TABLE IF NOT EXISTS charts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    config JSON NOT NULL,
    chart_type VARCHAR(50) NOT NULL,
    chart_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create report_notes table
CREATE TABLE IF NOT EXISTS report_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dataset_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 