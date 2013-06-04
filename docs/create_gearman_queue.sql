CREATE TABLE gearman_queue(
`unique_key` VARCHAR(64) PRIMARY KEY,
`function_name` VARCHAR(255),
`priority` INT,
`data` LONGBLOB
);