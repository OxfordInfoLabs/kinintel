<?php


namespace Kinintel\Services\Alert\MatchRule;


use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\ValueObjects\Alert\MatchRule\RowCountAlertMatchRuleConfiguration;

class RowCountAlertMatchRule implements AlertMatchRule {

    /**
     * Return the appropriate config class
     *
     * @return string
     */
    public function getConfigClass() {
        return RowCountAlertMatchRuleConfiguration::class;
    }

    /**
     * Evaluate the passed dataset according to the passed configuration
     *
     * @param TabularDataset $dataset
     * @param RowCountAlertMatchRuleConfiguration $configuration
     *
     * @return bool
     */
    public function matchesRule($dataset, $configuration) {

        // Get row count
        $rowCount = sizeof($dataset->getAllData());

        switch ($configuration->getMatchType()) {
            case RowCountAlertMatchRuleConfiguration::MATCH_TYPE_EQUALS:
                return $rowCount == $configuration->getValue();
            case RowCountAlertMatchRuleConfiguration::MATCH_TYPE_GREATER_THAN:
                return $rowCount > $configuration->getValue();
            case RowCountAlertMatchRuleConfiguration::MATCH_TYPE_LESS_THAN:
                return $rowCount < $configuration->getValue();
        }

    }
}