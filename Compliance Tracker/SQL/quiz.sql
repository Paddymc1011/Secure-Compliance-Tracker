CREATE TABLE quiz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNIQUE,
    title VARCHAR(255),
    due_date DATETIME,
    created_at DATETIME
);