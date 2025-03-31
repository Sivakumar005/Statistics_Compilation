CREATE TABLE IF NOT EXISTS dataset_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dataset_id INT NOT NULL,
    value DECIMAL(10,2),
    label VARCHAR(255),
    category VARCHAR(255),
    timestamp DATETIME,
    FOREIGN KEY (dataset_id) REFERENCES datasets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 