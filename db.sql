CREATE TABLE sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content TEXT
);

CREATE TABLE problem_words (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(255),
    explanation TEXT
);

CREATE TABLE memory_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    word VARCHAR(255),
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
