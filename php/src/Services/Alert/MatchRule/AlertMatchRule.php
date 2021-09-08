<?php


namespace Kinintel\Services\Alert\MatchRule;


use Kinintel\Objects\Dataset\Dataset;
use Kinintel\ValueObjects\Alert\MatchRule\AlertMatchRuleConfiguration;

/**
 * Interface for an alert match rule.
 *
 * @implementation rowcount Kinintel\Services\Alert\MatchRule\RowCountAlertMatchRule
 */
interface AlertMatchRule {

    /**
     * Return the config class for this match rule
     *
     * @return string
     */
    public function getConfigClass();


    /**
     * Return boolean indicator for whether the passed data set matches
     * the rule described by the passed configuration
     *
     * @param Dataset $dataset
     * @param AlertMatchRuleConfiguration $configuration
     *
     * @return boolean
     */
    public function matchesRule($dataset, $configuration);


}