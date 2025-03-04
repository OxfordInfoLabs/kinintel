<?php

namespace Kinintel\ValueObjects\ImportExport\ExportObjects;

use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;

class ExportedDatasource extends DatasourceInstanceSearchResult {


    /**
     * @param string|null $importKey
     * @param mixed $config
     */
    public function __construct(string          $key, string $title, string $type, ?string $description, private ?string $importKey, private mixed $config, private array $data = [],
                                private ?string $associatedDataProcessorKey = null,
                                private ?string $dataProcessorTitle = null,
                                private ?string $dataProcessorKeyPrefix = null,
                                private ?string $dataProcessorKeySuffix = null) {
        parent::__construct($key, $title, $type, $description);

    }

    /**
     * @return string
     */
    public function getImportKey(): ?string {
        return $this->importKey;
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

    /**
     * @return string|null
     */
    public function getAssociatedDataProcessorKey(): ?string {
        return $this->associatedDataProcessorKey;
    }

    /**
     * @return string|null
     */
    public function getDataProcessorTitle(): ?string {
        return $this->dataProcessorTitle;
    }


    /**
     * @return string|null
     */
    public function getDataProcessorKeyPrefix(): ?string {
        return $this->dataProcessorKeyPrefix;
    }

    /**
     * @return string|null
     */
    public function getDataProcessorKeySuffix(): ?string {
        return $this->dataProcessorKeySuffix;
    }


}