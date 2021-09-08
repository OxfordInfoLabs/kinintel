<?php


namespace Kinintel\ValueObjects\Alert\MatchRule;


/**
 * Row count alert match rule configuration
 *
 * Class RowCountAlertMatchRuleConfiguration
 * @package Kinintel\ValueObjects\Alert\MatchRule
 */
class RowCountAlertMatchRuleConfiguration implements AlertMatchRuleConfiguration {

    /**
     * One of the match type constants
     *
     * @var string
     * @required
     */
    private $matchType;


    /**
     * Value to match the type
     *
     * @var integer
     * @required
     */
    private $value;

    // Match types for row count evaluation
    const MATCH_TYPE_EQUALS = "equals";
    const MATCH_TYPE_GREATER_THAN = "greater";
    const MATCH_TYPE_LESS_THAN = "less";

    /**
     * RowCountAlertMatchRuleConfiguration constructor.
     *
     * @param int $value
     * @param string $matchType
     */
    public function __construct($value, $matchType = self::MATCH_TYPE_EQUALS) {
        $this->matchType = $matchType;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getMatchType() {
        return $this->matchType;
    }

    /**
     * @param string $matchType
     */
    public function setMatchType($matchType) {
        $this->matchType = $matchType;
    }

    /**
     * @return int
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value) {
        $this->value = $value;
    }


}