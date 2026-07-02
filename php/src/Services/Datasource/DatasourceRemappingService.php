<?php

namespace Kinintel\Services\Datasource;

use Kiniauth\Objects\Account\AccountCSVProfile;
use Kiniauth\Services\Account\AccountService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

class DatasourceRemappingService {

    public function __construct(
        private readonly AccountService $accountService
    ) {
    }

    /**
     * Retrieve a CSV profile object to use to remap the datasourceUpdate fields.
     *
     * @param int $csvProfileId
     *
     * @return AccountCSVProfile
     */
    public function getCSVProfile($csvProfileId): AccountCSVProfile {
        return $this->accountService->getAccountCSVProfileById($csvProfileId);
    }

    /**
     * Apply a field mapping to a DatasourceUpdateWithStructure
     *
     * @param DatasourceUpdateWithStructure|DatasourceUpdate $datasourceUpdate
     * @param AccountCSVProfile $csvProfile
     *
     * @return DatasourceUpdateWithStructure|DatasourceUpdate
     */
    public function applyFieldMapping($datasourceUpdate, $csvProfile) {

        $mapping = $csvProfile->returnSummary()->getMapping();
        $extraDataFlags = $csvProfile->returnSummary()->getExtraDataFlags();

        if ($datasourceUpdate instanceof DatasourceUpdateWithStructure) {

            // Re-map fields
            $mappedFields = [];

            foreach ($datasourceUpdate->getFields() as $field) {

                // Skip if not in mapping
                if (!isset($mapping[$field->getName()])) continue;

                $mappedFields[] = new Field(
                    $mapping[$field->getName()],
                    null,
                    $field->getValueExpression(),
                    $field->getType(),
                    $field->isKeyField()
                );
            }

            if (!empty($extraDataFlags)) {
                $mappedFields[] = new Field("extra_data");
            }

            return new DatasourceUpdateWithStructure(
                $datasourceUpdate->getTitle(),
                $datasourceUpdate->getImportKey(),
                $mappedFields,
                $datasourceUpdate->getIndexes(),
                $this->remapRows($datasourceUpdate->getAdds(), $mapping, $extraDataFlags),
                $this->remapRows($datasourceUpdate->getUpdates(), $mapping, $extraDataFlags),
                $this->remapRows($datasourceUpdate->getDeletes(), $mapping, $extraDataFlags),
                $this->remapRows($datasourceUpdate->getReplaces(), $mapping, $extraDataFlags),
            );
        }

        return new DatasourceUpdate(
            $this->remapRows($datasourceUpdate->getAdds(), $mapping, $extraDataFlags),
            $this->remapRows($datasourceUpdate->getUpdates(), $mapping, $extraDataFlags),
            $this->remapRows($datasourceUpdate->getDeletes(), $mapping, $extraDataFlags),
            $this->remapRows($datasourceUpdate->getReplaces(), $mapping, $extraDataFlags),
        );
    }

    /**
     * Helper method to remap rows in a dataset.
     *
     * @param array $rows
     * @param array $mapping
     * @param array $extraDataFlags
     *
     * @return array
     */
    private function remapRows(array $rows, array $mapping, array $extraDataFlags = []): array {

        // remap rows
        $mappedRows = [];

        foreach ($rows as $row) {

            // collect extra data flagged values
            $extraData = array_filter($row, function ($column) use ($extraDataFlags) {
                return $extraDataFlags[$column] ?? false;
            }, ARRAY_FILTER_USE_KEY);

            // remap row
            $mappedRow = [];

            foreach ($row as $column => $value) {

                // skip if not in mapping
                if (!isset($mapping[$column])) continue;

                $mappedRow[$mapping[$column]] = $value;
            }

            // check if we have extra data
            if (!empty($extraData)) {
                $mappedRow["extra_data"] = json_encode($extraData);
            }

            $mappedRows[] = $mappedRow;
        }

        return $mappedRows;
    }

}