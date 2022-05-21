DROP SCHEMA IF EXISTS sample_db;

CREATE SCHEMA sample_db;

USE sample_db;

DROP TABLE IF EXISTS employee;

CREATE TABLE employee (id INT(10), name VARCHAR(40));

INSERT INTO employee (id, name) VALUES (1, "Nagaoka");

INSERT INTO employee (id, name) VALUES (2, "Tanaka");
