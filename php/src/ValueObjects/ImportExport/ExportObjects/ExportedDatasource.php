<?php

namespace Kinintel\ValueObjects\ImportExport\ExportObjects;

use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;

class ExportedDatasource extends DatasourceInstanceSearchResult {

    /**
     * @param mixed $config
     */
    public function __construct($key, $title, $type, $description, private mixed $config, private array $data = []) {
        parent::__construct($key, $title, $type, $description);
    }


    /**
     * @return mixed
     */
    public function getConfig(): mixed {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }


}