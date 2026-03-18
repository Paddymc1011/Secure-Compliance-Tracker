CREATE TABLE quiz_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT,
    employee_id INT,
    assigned_at DATETIME,
    status ENUM('assigned','completed') DEFAULT 'assigned'
);