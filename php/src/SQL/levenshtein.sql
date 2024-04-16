-- Main levenshtein comparison table
DROP TABLE IF EXISTS levenshtein_distance;

CREATE TABLE levenshtein_distance
(
    word       VARCHAR(255),
    other_word VARCHAR(255),
    distance   int,
    PRIMARY KEY (word, other_word),
    KEY (word, distance)
);

-- Staging table for levenshtein distances gathered
DROP TABLE IF EXISTS levenshtein_distance_stage;

CREATE TABLE levenshtein_distance_stage
(
    word       VARCHAR(255),
    other_word VARCHAR(255),
    distance   int,
    PRIMARY KEY (word, other_word)
);


-- Levenshtein calculation function - caches to stage table
DROP FUNCTION IF EXISTS levenshtein;

CREATE FUNCTION levenshtein(s1 text, s2 text) returns int deterministic
BEGIN
    DECLARE s1_len, s2_len, i, j, c, c_temp, cost INT;
    DECLARE s1_char CHAR;
    DECLARE cv0, cv1 text;
    DECLARE return_cost INT;
    SET s1_len = CHAR_LENGTH(s1), s2_len = CHAR_LENGTH(s2), cv1 = 0x00, j = 1, i = 1, c = 0;
    IF s1 = s2 THEN
        SET return_cost = 0;
    ELSEIF s1_len = 0 THEN
        SET return_cost = s2_len;
    ELSEIF s2_len = 0 THEN
        SET return_cost = s1_len;
    ELSE
        WHILE j <= s2_len
            DO
                SET cv1 = CONCAT(cv1, UNHEX(HEX(j))), j = j + 1;
            END WHILE;
        WHILE i <= s1_len
            DO
                SET s1_char = SUBSTRING(s1, i, 1), c = i, cv0 = UNHEX(HEX(i)), j = 1;
                WHILE j <= s2_len
                    DO
                        SET c = c + 1;
                        IF s1_char = SUBSTRING(s2, j, 1) THEN
                            SET cost = 0;
                        ELSE
                            SET cost = 1;
                        END IF;
                        SET c_temp = CONV(HEX(SUBSTRING(cv1, j, 1)), 16, 10) + cost;
                        IF c > c_temp THEN SET c = c_temp; END IF;
                        SET c_temp = CONV(HEX(SUBSTRING(cv1, j + 1, 1)), 16, 10) + 1;
                        IF c > c_temp THEN
                            SET c = c_temp;
                        END IF;
                        SET cv0 = CONCAT(cv0, UNHEX(HEX(c))), j = j + 1;
                    END WHILE;
                SET cv1 = cv0, i = i + 1;
            END WHILE;
        SET return_cost = c;
    END IF;
    RETURN return_cost;
END;


-- Trigger changes to levenshtein table
DROP TRIGGER IF EXISTS levenshtein_sync;

create trigger levenshtein_sync
    after insert
    on levenshtein_distance_stage
    for each row
BEGIN
    INSERT IGNORE INTO levenshtein_distance VALUES (new.word, new.other_word, new.distance);
END;




-- Cron based sync job for levenshtein staging table
DROP EVENT IF EXISTS levenshtein_update;

CREATE EVENT levenshtein_update
    ON schedule
        EVERY '1' HOUR
    COMMENT 'Update Levenshein from stage'
    DO
    BEGIN
        INSERT IGNORE INTO levenshtein_distance
        SELECT *
        FROM levenshtein_distance_stage;
        TRUNCATE TABLE levenshtein_distance_stage;
    END;

