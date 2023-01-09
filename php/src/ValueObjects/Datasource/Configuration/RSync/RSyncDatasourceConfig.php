<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\RSync;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\DatasourceCompressionConfig;
use Kinintel\ValueObjects\Datasource\Configuration\FormattedResultDatasourceConfig;

class RSyncDatasourceConfig extends FormattedResultDatasourceConfig {

    // Use compression config
    use DatasourceCompressionConfig;

    /**
     * @var string
     */
    private $source;

    /**
     * @param string $source
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     * @param Field[] $columns
     */
    public function __construct($source, $resultFormat = "json", $resultFormatConfig = [], $columns = []) {
        $this->source = $source;
        parent::__construct($resultFormat, $resultFormatConfig, $columns);
    }

    /**
     * @return string
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source) {
        $this->source = $source;
    }


}