--DROP TABLE IF EXISTS urls;
--DROP TABLE IF EXISTS url_checks;

CREATE TABLE IF NOT EXISTS urls (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks (
    id SERIAL PRIMARY KEY,
    url_id BIGINT NOT NULL,
    status_code VARCHAR(255) NULL,
    h1 VARCHAR(255) NULL,
    title TEXT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL
);