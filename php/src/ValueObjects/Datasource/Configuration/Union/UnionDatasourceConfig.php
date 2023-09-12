<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Union;

use Kinintel\ValueObjects\Dataset\Field;

class UnionDatasourceConfig {

    /**
     * @var DatasourceMapping[]
     */
    private $sourceDatasources;

    /**
     * @var Field[]
     */
    private $targetColumns;

    /**
     * @param DatasourceMapping[] $sourceDatasources
     * @param Field[] $targetColumns
     */
    public function __construct($sourceDatasources, $targetColumns) {
        $this->sourceDatasources = $sourceDatasources;
        $this->targetColumns = $targetColumns;
    }

    /**
     * @return DatasourceMapping[]
     */
    public function getSourceDatasources() {
        return $this->sourceDatasources;
    }

    /**
     * @param DatasourceMapping[] $sourceDatasources
     */
    public function setSourceDatasources($sourceDatasources) {
        $this->sourceDatasources = $sourceDatasources;
    }

    /**
     * @return Field[]
     */
    public function getTargetColumns() {
        return $this->targetColumns;
    }

    /**
     * @param Field[] $targetColumns
     */
    public function setTargetColumns($targetColumns) {
        $this->targetColumns = $targetColumns;
    }

}