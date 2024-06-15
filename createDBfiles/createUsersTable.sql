Create Table Users(
    user_id BIGINT AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE, 
    password CHAR(60) NOT NULL,
    api_key CHAR(64) NOT NULL,
    api_date DATE NOT NULL,
    PRIMARY KEY (user_id)
);