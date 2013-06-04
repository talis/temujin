CREATE TABLE gearman_status_updates(
`unique_key` VARCHAR(64) PRIMARY KEY,
`job_handle` VARCHAR(255),
`function_name` VARCHAR(255),
`data` LONGBLOB,
`status` VARCHAR(64),
`message` LONGBLOB,
`started` VARCHAR(64),
`finished` VARCHAR(64),
`duration` VARCHAR(64),
`last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`parent_key` VARCHAR(64),
`log` LONGBLOB
);