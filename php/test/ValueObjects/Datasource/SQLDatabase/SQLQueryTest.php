<?php


namespace Kinintel\ValueObjects\Datasource\SQLDatabase;


include_once "autoloader.php";

class SQLQueryTest extends \PHPUnit\Framework\TestCase {


    public function testCanApplyWhereClausesAndOrderingsInAnyOrder() {

        // Try normal way round
        $sqlQuery = new SQLQuery("*", "test");
        $sqlQuery->setWhereClause("apples = ? AND animal = ?", ["pears", 5]);
        $this->assertEquals("SELECT * FROM test WHERE apples = ? AND animal = ?", $sqlQuery->getSQL());
        $this->assertEquals(["pears", 5], $sqlQuery->getParameters());

        $sqlQuery->setOrderByClause("apples DESC, animal ASC");
        $this->assertEquals("SELECT * FROM test WHERE apples = ? AND animal = ? ORDER BY apples DESC, animal ASC", $sqlQuery->getSQL());
        $this->assertEquals(["pears", 5], $sqlQuery->getParameters());


        // Try other way round
        $sqlQuery = new SQLQuery("*", "?", ["test"]);
        $sqlQuery->setOrderByClause("apples DESC, animal ASC");
        $this->assertEquals("SELECT * FROM ? ORDER BY apples DESC, animal ASC", $sqlQuery->getSQL());
        $this->assertEquals(["test"], $sqlQuery->getParameters());

        $sqlQuery->setWhereClause("apples = ? AND animal = ?", ["pears", 5]);
        $this->assertEquals("SELECT * FROM ? WHERE apples = ? AND animal = ? ORDER BY apples DESC, animal ASC", $sqlQuery->getSQL());
        $this->assertEquals(["test", "pears", 5], $sqlQuery->getParameters());


    }


    public function testOffsetAndLimitAppliedAtTheEnd() {

        $sqlQuery = new SQLQuery("*", "test");
        $sqlQuery->setWhereClause("apples = ? AND animal = ?", ["pears", 5]);
        $sqlQuery->setOrderByClause("apples DESC, animal ASC");
        $sqlQuery->setLimit(100);
        $sqlQuery->setOffset(10);
        $this->assertEquals("SELECT * FROM test WHERE apples = ? AND animal = ? ORDER BY apples DESC, animal ASC LIMIT ? OFFSET ?", $sqlQuery->getSQL());
        $this->assertEquals(["pears", 5, 100, 10], $sqlQuery->getParameters());

    }


    public function testGroupByAppliedInRightPlaceAndCancelsAnyOrderLimitOffsetClauses() {

        $sqlQuery = new SQLQuery("*", "test");
        $sqlQuery->setWhereClause("apples = ? AND animal = ?", ["pears", 5]);
        $sqlQuery->setOrderByClause("apples DESC, animal ASC");
        $sqlQuery->setLimit(100);
        $sqlQuery->setOffset(10);
        $sqlQuery->setGroupByClause("apples, count(*)", "apples");

        $this->assertEquals("SELECT apples, count(*) FROM test WHERE apples = ? AND animal = ? GROUP BY apples", $sqlQuery->getSQL());
    }


    public function testHavingClauseOnlyAppliedIfGroupByExists() {

        $sqlQuery = new SQLQuery("*", "test");
        $sqlQuery->setWhereClause("apples = ? AND animal = ?", ["pears", 5]);
        $sqlQuery->setOrderByClause("apples DESC, animal ASC");
        $sqlQuery->setLimit(100);
        $sqlQuery->setOffset(10);
        $sqlQuery->setHavingClause("count(*) > ?", [6]);

        $this->assertEquals("SELECT * FROM test WHERE apples = ? AND animal = ? ORDER BY apples DESC, animal ASC LIMIT ? OFFSET ?", $sqlQuery->getSQL());
        $this->assertEquals(["pears", 5, 100, 10], $sqlQuery->getParameters());

        $sqlQuery->setGroupByClause("apples, count(*)", "apples");
        $sqlQuery->setHavingClause("count(*) > ?", [6]);
        $this->assertEquals("SELECT apples, count(*) FROM test WHERE apples = ? AND animal = ? GROUP BY apples HAVING count(*) > ?", $sqlQuery->getSQL());
        $this->assertEquals(["pears", 5, 6], $sqlQuery->getParameters());
    }





}