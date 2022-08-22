DROP TABLE IF EXISTS test_account_datasource;

CREATE TABLE test_account_datasource
(
    employee_code VARCHAR(255),
    name          VARCHAR(255),
    department    VARCHAR(255),
    year_started  INTEGER,
    PRIMARY KEY (employee_code)
);


INSERT INTO test_account_datasource
VALUES ('AAAA', 'Andrew Allen', 'HR', 2010),
       ('BBBB', 'Becky Ballard', 'HR', 2015),
       ('CCCC', 'Crispin Cullen', 'Marketing', 2020),
       ('DDDD', 'David Dimbleby', 'Technical', 2002),
       ('EEEE', 'Eleanor Everard', 'Technical', 2009),
       ('FFFF', 'Fergus Flannagan', 'Technical', 2021),
       ('GGGG', 'Gordon Grimshaw', 'Sales', 2010),
       ('HHHH', 'Holly Hopcroft', 'Sales', 2010),
       ('IIII', 'Imogen Ingleford', 'Finance', 2018),
       ('JJJJ', 'Jonathan Johnson', 'Finance', 2016);



DROP TABLE IF EXISTS test_word_index;

CREATE TABLE test_word_index (
    document VARCHAR(255),
    word VARCHAR(255),
    frequency INTEGER,
    PRIMARY KEY (document, word)
);

INSERT INTO test_word_index
VALUES
    ('Document 1', 'monkey', 5),
    ('Document 1', 'gorilla', 3),
    ('Document 1', 'ape', 7),
    ('Document 1', 'orangutan', 2),
    ('Document 2', 'monkey', 3),
    ('Document 2', 'gorilla', 4),
    ('Document 2', 'ape', 7),
    ('Document 2', 'chimpanzee', 2),
    ('Document 3', 'monkey', 6),
    ('Document 3', 'gorilla', 3),
    ('Document 3', 'chimpanzee', 7),
    ('Document 3', 'bonobo', 2);
