<?php


namespace Kinintel\Test\Objects\Datasource\SQLDatabase\Util;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterLogic;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Transformation\InclusionCriteriaType;

include_once "autoloader.php";


class SQLFilterJunctionEvaluatorTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;


    public function setUp(): void {
        $this->databaseConnection = new SQLite3DatabaseConnection();
    }

    public function testCanEvaluateSimpleFilterJunctionToSQLForAllSupportedFilterTypes() {
        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);

        // EQUALS
        $this->assertEquals([
            "sql" => "\"name\" = ?",
            "parameters" => [
                "Joe Bloggs"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs")
        ])));

        // NULL
        $this->assertEquals([
            "sql" => "\"name\" IS NULL",
            "parameters" => []
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "", FilterType::null)
        ])));

        $this->assertEquals([
            "sql" => "\"name\" IS NULL",
            "parameters" => []
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "", FilterType::isnull)
        ])));

        // NOT NULL
        $this->assertEquals([
            "sql" => "\"name\" IS NOT NULL",
            "parameters" => []
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "", FilterType::notnull)
        ])));

        $this->assertEquals([
            "sql" => "\"name\" IS NOT NULL",
            "parameters" => []
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "", FilterType::isnotnull)
        ])));

        // GREATER THAN
        $this->assertEquals([
            "sql" => "\"age\" > ?",
            "parameters" => [44]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", 44, FilterType::gt)
        ])));

        // GREATER THAN OR EQUAL TO
        $this->assertEquals([
            "sql" => "\"age\" >= ?",
            "parameters" => [44]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", 44, FilterType::gte)
        ])));

        // LESS THAN
        $this->assertEquals([
            "sql" => "\"age\" < ?",
            "parameters" => [44]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", 44, FilterType::lt)
        ])));

        // LESS THAN OR EQUAL TO
        $this->assertEquals([
            "sql" => "\"age\" <= ?",
            "parameters" => [44]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", 44, FilterType::lte)
        ])));


        // STARTS WITH
        $this->assertEquals([
            "sql" => "\"name\" LIKE CONCAT(?,'%')",
            "parameters" => [
                "ee"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "ee", FilterType::startswith)
        ])));

        // ENDS WITH
        $this->assertEquals([
            "sql" => "\"name\" LIKE CONCAT('%', ?)",
            "parameters" => [
                "ee"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "ee", FilterType::endswith)
        ])));


        // CONTAINS
        $this->assertEquals([
            "sql" => "\"name\" LIKE CONCAT('%', ?, '%')",
            "parameters" => [
                "ee"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "ee", FilterType::contains)
        ])));


        // SIMILAR TO
        $this->assertEquals([
            "sql" => "(ABS(LENGTH(\"name\") - LENGTH(?)) <= ?) AND (LEVENSHTEIN(\"name\", ?) <= ?)",
            "parameters" => [
                "ee",
                5,
                "ee",
                5
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", ["ee", 5], FilterType::similarto)
        ])));


        // PREFIX LIKE
        $this->assertEquals([
            "sql" => "\"name\" LIKE ?",
            "parameters" => [
                "%ee"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "*ee", FilterType::like)
        ])));

        // SUFFIX LIKE
        $this->assertEquals([
            "sql" => "\"name\" LIKE ?",
            "parameters" => [
                "ee%"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "ee*", FilterType::like)
        ])));

        // OPEN LIKE
        $this->assertEquals([
            "sql" => "\"name\" LIKE ?",
            "parameters" => [
                "%ee%"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "*ee*", FilterType::like)
        ])));


        // NOT LIKE
        $this->assertEquals([
            "sql" => "\"name\" NOT LIKE ?",
            "parameters" => [
                "%ee%"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "*ee*", FilterType::notlike)
        ])));


        // OPEN LIKE EXPLICIT WILCARD TYPE
        $this->assertEquals([
            "sql" => "\"name\" LIKE ?",
            "parameters" => [
                "%ee%"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", ["*ee*", Filter::LIKE_MATCH_WILDCARD], FilterType::like)
        ])));


        // NOT LIKE EXPLICIT WILDCARD TYPE
        $this->assertEquals([
            "sql" => "\"name\" NOT LIKE ?",
            "parameters" => [
                "%ee%"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", ["*ee*", Filter::LIKE_MATCH_WILDCARD], FilterType::notlike)
        ])));


        // LIKE FORMULA
        $this->assertEquals([
            "sql" => "\"name\" LIKE CONCAT(%, ?, %)",
            "parameters" => ["hi"]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", ["CONCAT(%, 'hi', %)", Filter::LIKE_MATCH_WILDCARD], FilterType::like)
        ])));

        // MISSING LIKE_MATCH_WILDCARD Defaults to wildcard
        $this->assertEquals([
            "sql" => "\"name\" LIKE CONCAT(%, ?, %)",
            "parameters" => ["hi"]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", ["CONCAT(%, 'hi', %)"], FilterType::like)
        ])));


        // REGEXP LIKE
        $this->assertEquals([
            "sql" => "\"name\" RLIKE ?",
            "parameters" => [
                ".*ee.*"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", [".*ee.*", Filter::LIKE_MATCH_REGEXP], FilterType::like)
        ])));


        // REGEXP NOT LIKE
        $this->assertEquals([
            "sql" => "\"name\" NOT RLIKE ?",
            "parameters" => [
                ".*ee.*"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", [".*ee.*", Filter::LIKE_MATCH_REGEXP], FilterType::notlike)
        ])));


        // BETWEEN
        $this->assertEquals([
            "sql" => "\"age\" BETWEEN ? AND ?",
            "parameters" => [
                12,
                50
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", [12, 50], FilterType::between)
        ])));


        // IN
        $this->assertEquals([
            "sql" => "\"age\" IN (?,?,?)",
            "parameters" => [
                12,
                50,
                75
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", [12, 50, 75], FilterType::in)
        ])));

        // NOT IN
        $this->assertEquals([
            "sql" => "\"age\" NOT IN (?,?,?)",
            "parameters" => [
                12,
                50,
                75
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[age]]", [12, 50, 75], FilterType::notin)
        ])));
    }

    public function testAndAndOrFiltersAreMappedCorrectlyForMultipleFiltersInAJunction() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "\"name\" = ? AND \"age\" IN (?,?,?,?)",
            "parameters" => [
                "Joe Bloggs",
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs"),
            new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
        ])));


        // Or junction
        $this->assertEquals([
            "sql" => "\"name\" = ? OR \"age\" IN (?,?,?,?)",
            "parameters" => [
                "Joe Bloggs",
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs"),
            new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
        ], [], FilterLogic::OR)));

    }

    public function testFiltersOmittedIfInclusionCriteriaNotMet() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);

        // No parameters passed, first clause only expected.
        $this->assertEquals([
            "sql" => "\"name\" = ?",
            "parameters" => [
                "Joe Bloggs"
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs"),
            new Filter("[[age]]", [5, 7, 9, 11], FilterType::in, InclusionCriteriaType::ParameterPresent, "testParam"),
            new Filter("[[shoeSize]]", 25, FilterType::eq, InclusionCriteriaType::ParameterValue, "testParam=55"),
        ]), []));


        // Parameter set, should include two clauses
        $this->assertEquals([
            "sql" => "\"name\" = ? AND \"age\" IN (?,?,?,?)",
            "parameters" => [
                "Joe Bloggs",
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs"),
            new Filter("[[age]]", [5, 7, 9, 11], FilterType::in, InclusionCriteriaType::ParameterPresent, "testParam"),
            new Filter("[[shoeSize]]", 25, FilterType::eq, InclusionCriteriaType::ParameterValue, "testParam=55"),
        ]), ["testParam" => 1]));


        // Parameter value set correctly, should include three clauses
        $this->assertEquals([
            "sql" => "\"name\" = ? AND \"age\" IN (?,?,?,?) AND \"shoeSize\" = ?",
            "parameters" => [
                "Joe Bloggs",
                5,
                7,
                9,
                11,
                25
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([
            new Filter("[[name]]", "Joe Bloggs"),
            new Filter("[[age]]", [5, 7, 9, 11], FilterType::in, InclusionCriteriaType::ParameterPresent, "testParam"),
            new Filter("[[shoeSize]]", 25, FilterType::eq, InclusionCriteriaType::ParameterValue, "testParam=55"),
        ]), ["testParam" => 55]));
    }


    public function testNestedJunctionsAreEvaluatedCorrectly() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "\"dob\" > ? OR (\"name\" = ? AND \"age\" IN (?,?,?,?))",
            "parameters" => [
                '2000-01-01',
                "Joe Bloggs",
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction(
            [
                new Filter("[[dob]]", "2000-01-01", FilterType::gt)
            ],
            [
                new FilterJunction([
                    new Filter("[[name]]", "Joe Bloggs"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ])
            ],
            FilterLogic::OR
        )));

    }


    public function testJunctionsIncludedSelectivelyIfInclusionRulesDefined() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);


        // No params passed
        $this->assertEquals([
            "sql" => "(\"dob\" > ?)",
            "parameters" => [
                '2000-01-01'
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([],
            [
                new FilterJunction([
                    new Filter("[[dob]]", "2000-01-01", FilterType::gt)
                ]),
                new FilterJunction([
                    new Filter("[[name]]", "Joe Bloggs"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterPresent, "testParam"),
                new FilterJunction([
                    new Filter("[[shoeSize]]", "25"),
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterValue, "testParam=5")
            ]

        ), []));


        $this->assertEquals([
            "sql" => "(\"dob\" > ?) AND (\"name\" = ? AND \"age\" IN (?,?,?,?))",
            "parameters" => [
                '2000-01-01',
                'Joe Bloggs',
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([],
            [
                new FilterJunction([
                    new Filter("[[dob]]", "2000-01-01", FilterType::gt)
                ]),
                new FilterJunction([
                    new Filter("[[name]]", "Joe Bloggs"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterPresent, "testParam"),
                new FilterJunction([
                    new Filter("[[shoeSize]]", "25"),
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterValue, "testParam=5")
            ]

        ), ["testParam" => 1]));


        $this->assertEquals([
            "sql" => "(\"dob\" > ?) AND (\"name\" = ? AND \"age\" IN (?,?,?,?)) AND (\"shoeSize\" = ?)",
            "parameters" => [
                '2000-01-01',
                'Joe Bloggs',
                5,
                7,
                9,
                11,
                25
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction([],
            [
                new FilterJunction([
                    new Filter("[[dob]]", "2000-01-01", FilterType::gt)
                ]),
                new FilterJunction([
                    new Filter("[[name]]", "Joe Bloggs"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterPresent, "testParam"),
                new FilterJunction([
                    new Filter("[[shoeSize]]", "25"),
                ], [], FilterLogic::AND, InclusionCriteriaType::ParameterValue, "testParam=5")
            ]

        ), ["testParam" => 5]));



    }


    public function testIfLHSTableAliasSuppliedItIsAddedToAllFieldNamePrefixes() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator("T", null, $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "T.\"dob\" > ? OR (T.\"name\" = ? AND T.\"age\" IN (?,?,?,?))",
            "parameters" => [
                '2000-01-01',
                "Joe Bloggs",
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction(
            [
                new Filter("[[dob]]", "2000-01-01", FilterType::gt)
            ],
            [
                new FilterJunction([
                    new Filter("[[name]]", "Joe Bloggs"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ])
            ],
            FilterLogic::OR
        )));

    }


    public function testIfExpressionWithSquareBracketsSuppliedToLHSTheSquareBracketsAreRemovedAndPrefixedAsRequired() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator("T", null, $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "SUBSTR(T.\"dob\", ?, ?) > ? OR (? || T.\"name\" = ? AND T.\"age\" * ? IN (?,?,?,?))",
            "parameters" => [
                0,
                10,
                '2000-01-01',
                'Me:',
                "Joe Bloggs",
                10,
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction(
            [
                new Filter("SUBSTR([[dob]], 0, 10)", "2000-01-01", FilterType::gt)
            ],
            [
                new FilterJunction([
                    new Filter("'Me:' || [[name]]", "Joe Bloggs"),
                    new Filter("[[age]] * 10", [5, 7, 9, 11], FilterType::in)
                ])
            ],
            FilterLogic::OR
        )));


    }


    public function testIfColumnsSuppliedUsingSquareBracketsTheseAreIncludedLiterallyInLieuOfPlaceholders() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "\"dob\" > \"new_dob\" OR (\"name\" = \"new_name\" AND \"age\" IN (?,?,?,?))",
            "parameters" => [
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction(
            [
                new Filter("[[dob]]", "[[new_dob]]", FilterType::gt)
            ],
            [
                new FilterJunction([
                    new Filter("[[name]]", "[[new_name]]"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ])
            ],
            FilterLogic::OR
        )));
    }


    public function testIfRHSColumnAliasSuppliedWIthColumnsSuppliedUsingSquareBracketsTheseAreIncludedLiterallyInLieuOfPlaceholders() {

        $filterJunctionEvaluator = new SQLFilterJunctionEvaluator(null, "J", $this->databaseConnection);

        // Default AND junction
        $this->assertEquals([
            "sql" => "\"dob\" > J.\"new_dob\" OR (\"name\" = J.\"new_name\" AND \"age\" IN (?,?,?,?))",
            "parameters" => [
                5,
                7,
                9,
                11
            ]
        ], $filterJunctionEvaluator->evaluateFilterJunctionSQL(new FilterJunction(
            [
                new Filter("[[dob]]", "[[new_dob]]", FilterType::gt)
            ],
            [
                new FilterJunction([
                    new Filter("[[name]]", "[[new_name]]"),
                    new Filter("[[age]]", [5, 7, 9, 11], FilterType::in)
                ])
            ],
            FilterLogic::OR
        )));
    }


}