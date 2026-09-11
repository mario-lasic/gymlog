CREATE
DATABASE gymlog CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci;

USE gymlog;

CREATE TABLE workouts(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_date DATE NOT NULL,
    name VARCHAR(100) NOT NULL,
    note TEXT NULL,
    CONSTRAINT chk_workouts_name_not_blank CHECK ( CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE InnoDB;
