<?php

namespace Kinintel\Services\ImportExport\ImportExporters;

use Kiniauth\Services\ImportExport\ImportExporter;

class DatasetImportExporter extends ImportExporter {

    public function getObjectTypeCollectionIdentifier() {
        // TODO: Implement getObjectTypeCollectionIdentifier() method.
    }

    public function getObjectTypeCollectionTitle() {
        // TODO: Implement getObjectTypeCollectionTitle() method.
    }

    public function getObjectTypeImportClassName() {
        // TODO: Implement getObjectTypeImportClassName() method.
    }

    public function getObjectTypeExportConfigClassName() {
        // TODO: Implement getObjectTypeExportConfigClassName() method.
    }

    public function getExportableProjectResources(int $accountId, string $projectKey) {
        // TODO: Implement getExportableProjectResources() method.
    }

    public function createExportObjects(int $accountId, string $projectKey, mixed $exportProjectConfig) {
        // TODO: Implement createExportObjects() method.
    }

    public function analyseImportObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {
        // TODO: Implement analyseImportObjects() method.
    }

    public function importObjects(int $accountId, string $projectKey, array $exportObjects, mixed $exportProjectConfig) {
        // TODO: Implement importObjects() method.
    }
}