<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;
use Kiniauth\ValueObjects\ImportExport\ExportConfig\ObjectInclusionExportConfig;
use Kiniauth\ValueObjects\ImportExport\ProjectExportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResource;
use Kiniauth\ValueObjects\ImportExport\ProjectImportResourceStatus;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\Feed\FeedService;

class FeedImportExporter extends ImportExporter {

    /**
     * @param FeedService $feedService
     */
    public function __construct(private FeedService $feedService) {
    }

    public function getObjectTypeCollectionIdentifier() {
        return "feeds";
    }

    public function getObjectTypeCollectionTitle() {
        return "Feeds";
    }

    public function getObjectTypeImportClassName() {
        return FeedSummary::class;
    }

    public function getObjectTypeExportConfigClassName() {
        return ObjectInclusionExportConfig::class;
    }

    /**
     * Return all exportable feeds
     *
     * @param int $accountId
     * @param string $projectKey
     *
     * @return ProjectExportResource[]
     */
    public function getExportableProjectResources(int $accountId, string $projectKey) {
        return array_map(function ($feedSummary) {
            return new ProjectExportResource($feedSummary->getId(), $feedSummary->getPath(), new ObjectInclusionExportConfig(true));
        }, $this->feedService->filterFeeds("", $projectKey, 0, PHP_INT_MAX, $accountId));
    }

    /**
     * Return export objects - remapping keys as required
     *
     * @param int $accountId
     * @param string $projectKey
     * @param mixed $objectExportConfig
     * @param mixed $allProjectExportConfig
     * @return void
     */
    public function createExportObjects(int $accountId, string $projectKey, mixed $objectExportConfig, mixed $allProjectExportConfig) {

        $feeds = $this->feedService->filterFeeds("", $projectKey, 0, PHP_INT_MAX, $accountId);
        $exportObjects = [];
        foreach ($feeds as $feed) {
            $feedConfig = $objectExportConfig[$feed->getId()] ?? null;
            if ($feedConfig?->isIncluded()) {
                $feed->setId(self::getNewExportPK("feeds", $feed->getId()));
                $feed->setDatasetInstanceId(self::remapExportObjectPK("datasets", $feed->getDatasetInstanceId()));
                $feed->setDatasetLabel(null);

                $exportObjects[] = $feed;
            }
        }

        return $exportObjects;
    }

    /**
     * Analyse import for feed objects
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $accountFeeds = ObjectArrayUtils::indexArrayOfObjectsByMember("path", $this->feedService->filterFeeds("", $projectKey, 0, PHP_INT_MAX, $accountId));

        $analysis = [];
        foreach ($exportObjects as $exportObject) {
            $analysis[] = new ProjectImportResource($exportObject->getId(), "/" . $exportObject->getPath(), isset($accountFeeds[$exportObject->getPath()]) ? ProjectImportResourceStatus::Update : ProjectImportResourceStatus::Create,
                isset($accountFeeds[$exportObject->getPath()]) ? $accountFeeds[$exportObject->getPath()]->getId() : null);
        }

        return $analysis;

    }

    /**
     * Import objects from export
     *
     * @param int $accountId
     * @param string $projectKey
     * @param array $exportObjects
     * @param mixed $objectExportConfig
     * @return void
     */
    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $objectExportConfig) {

        $analysis = ObjectArrayUtils::indexArrayOfObjectsByMember("identifier", $this->analyseImportObjects($accountId, $projectKey, $exportObjects, $objectExportConfig));

        foreach ($exportObjects as $exportObject) {
            $analysisObject = $analysis[$exportObject->getId()] ?? null;

            // Update both if and dataset instance id
            $exportObject->setId(($analysisObject?->getImportStatus() == ProjectImportResourceStatus::Update) ? $analysisObject->getExistingProjectIdentifier() : null);
            $exportObject->setDatasetInstanceId(self::remapImportedItemId("datasets", $exportObject->getDatasetInstanceId()));

            // Save feed
            $this->feedService->saveFeed($exportObject, $projectKey, $accountId);
        }

    }
}