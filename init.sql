USE assignment6;

CREATE TABLE IF NOT EXISTS credentials (
    id INT UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password BINARY(32) NOT NULL,
    salt BINARY(32) NOT NULL,
    PRIMARY KEY (email)
);

CREATE TABLE IF NOT EXISTS advisors (
    name VARCHAR(255) UNIQUE NOT NULL,
    lower_bound INT NOT NULL CHECK(lower_bound >= 1),
    upper_bound INT NOT NULL CHECK(upper_bound > lower_bound),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone_number VARCHAR(15) UNIQUE NOT NULL,
    PRIMARY KEY(email)
);