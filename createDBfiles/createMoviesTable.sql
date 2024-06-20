CREATE TABLE movies (
    id INT PRIMARY KEY,
    budget DECIMAL(15,2),
    genres JSON,
    homepage VARCHAR(255),
    original_language CHAR(2),
    overview TEXT,
    production_companies JSON,
    release_date DATE,
    revenue DECIMAL(15,2),
    runtime DECIMAL(5,2),
    tagline VARCHAR(255),
    title VARCHAR(255),
    vote_average DECIMAL(3,1),
    vote_count INT,
    poster VARCHAR(255)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;