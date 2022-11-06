use midterm2;
CREATE TABLE IF NOT EXISTS credentials (
    id INT AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    password BINARY(32) NOT NULL,
    salt BINARY(32) NOT NULL,
    PRIMARY KEY(id)
);
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT,
    uploader_id INT NOT NULL,
    content_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT `fk_uploader_id` FOREIGN KEY (uploader_id) REFERENCES credentials (id)
);