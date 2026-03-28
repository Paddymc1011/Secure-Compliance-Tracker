CREATE TABLE options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT,
    option_text VARCHAR(255),
    is_correct TINYINT(1),
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);