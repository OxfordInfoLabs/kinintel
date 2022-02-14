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