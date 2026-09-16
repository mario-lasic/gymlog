CREATE
    DATABASE gymlog CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci;

USE gymlog;

CREATE TABLE workouts
(
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workout_date DATE         NOT NULL,
    name         VARCHAR(100) NOT NULL,
    note         TEXT         NULL,
    CONSTRAINT chk_workouts_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)
) ENGINE InnoDB;

CREATE TABLE exercises
(
    id   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    CONSTRAINT chk_exercises_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0),
    CONSTRAINT uq_exercises_name UNIQUE (name)
) ENGINE InnoDB;

CREATE TABLE workout_exercises
(
    id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workout_id  INT UNSIGNED NOT NULL,
    exercise_id INT UNSIGNED NOT NULL,
    position    INT UNSIGNED NOT NULL,
    CONSTRAINT chk_workouts_exercises_position_positive CHECK (position > 0),
    CONSTRAINT fk_workout_exercises_workout FOREIGN KEY (workout_id) REFERENCES workouts (id) ON DELETE CASCADE,
    CONSTRAINT fk_workout_exercises_exercise FOREIGN KEY (exercise_id) REFERENCES exercises (id) ON DELETE RESTRICT,
    CONSTRAINT uq_workout_exercises_workout_exercise UNIQUE (workout_id, exercise_id),
    CONSTRAINT uq_workout_exercises_workout_position UNIQUE (workout_id, position)
) ENGINE InnoDB;

CREATE TABLE sets
(
    id                  INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workout_exercise_id INT UNSIGNED      NOT NULL,
    set_number          SMALLINT UNSIGNED NOT NULL,
    reps                SMALLINT UNSIGNED NOT NULL,
    weight_kg           DECIMAL(6, 2)     NOT NULL,
    CONSTRAINT chk_sets_set_number_positive CHECK (set_number > 0),
    CONSTRAINT chk_sets_reps_positive CHECK (reps > 0),
    CONSTRAINT chk_sets_weight_kg_non_negative CHECK (weight_kg >= 0),
    CONSTRAINT fk_sets_workout_exercise FOREIGN KEY (workout_exercise_id) REFERENCES workout_exercises (id) ON DELETE CASCADE,
    CONSTRAINT uq_sets_workout_exercise_set_number UNIQUE (workout_exercise_id, set_number)
) ENGINE InnoDB;
