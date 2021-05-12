CREATE TABLE test_data
(
    id         INTEGER PRIMARY KEY,
    name       VARCHAR(255),
    department VARCHAR(255),
    age        INTEGER,
    date_added DATETIME
);

INSERT INTO test_data (name, department, age, date_added)
VALUES ('Mark', 'Tech', 22, '2020-01-01 10:33:44'),
       ('Jane', 'Tech', 44, '2020-05-01 12:00:00'),
       ('Ben', 'Admin', 29, '2019-01-01 10:33:44'),
       ('Claire', 'Admin', 33, '2019-05-01 12:00:00'),
       ('Bob', 'Admin', 56, '2018-01-01 10:33:44'),
       ('Dave', 'Marketing', 25, '2018-05-01 12:00:00'),
       ('Dawn', 'Sales', 39, '2020-01-01 10:33:44'),
       ('Kim', 'Sales', 42, '2020-05-01 12:00:00'),
       ('Rob', 'HR', 30, '2021-01-01 10:33:44'),
       ('Pete', 'HR', 63, '2021-05-01 12:00:00');
