-- Dot product calculation function
DROP FUNCTION IF EXISTS dot_product;

CREATE FUNCTION dot_product(v1 JSON, v2 JSON) returns float deterministic
BEGIN
    DECLARE i INT;
    DECLARE total FLOAT;
    SET i = 0;
    SET total = 0.;
    WHILE JSON_EXTRACT(v1, '$.i') IS NOT NULL
        DO
            SET total = total + JSON_EXTRACT(v1, '$.i') * JSON_EXTRACT(v2, '$.i');
            SET i = i + 1;
        END WHILE;

    RETURN total;
END;