<?php


namespace Kinintel\Objects\Feed;


use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Core\Validation\FieldValidationError;


/**
 * @table ki_feed
 * @generate
 */
class Feed extends FeedSummary {

    use AccountProject;

    /**
     * Feed constructor.
     * @param FeedSummary $feedSummary
     * @param string $projectKey
     * @param integer $accountId
     */
    public function __construct($feedSummary, $projectKey, $accountId) {
        if ($feedSummary) {
            parent::__construct($feedSummary->getPath(),
                $feedSummary->getDatasetInstanceId(),
                $feedSummary->getExposedParameterNames(), $feedSummary->getExporterKey(),
                $feedSummary->getExporterConfiguration(), $feedSummary->getCacheTimeSeconds(),
                $feedSummary->getId());
        }
        $this->projectKey = $projectKey;
        $this->accountId = $accountId;
    }


    /**
     * Validate feed - ensure no overlap of feed url at account level
     */
    public function validate() {
        $validationErrors = [];

        $duplicateFeeds = self::values("COUNT(*)", "WHERE path = ? AND account_id = ? AND id <> ?",
            $this->path, $this->accountId, $this->id ? $this->id : -1);

        if ($duplicateFeeds[0] > 0)
            $validationErrors["path"] = [
                "duplicatePath" => new FieldValidationError("path", "duplicatePath", "A feed already exists in your account with the supplied path")];

        return $validationErrors;
    }


    /**
     * Return a summary object
     */
    public function returnSummary() {
        $summary = new FeedSummary($this->getPath(), $this->getDatasetInstanceId(), $this->getExposedParameterNames(),
            $this->getExporterKey(), $this->getExporterConfiguration(), $this->getCacheTimeSeconds(), $this->getId());
        $summary->setDatasetLabel($this->getDatasetLabel());
        return $summary;
    }

}