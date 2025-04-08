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
     * @var string
     */
    private $rsyncFlags;

    /**
     * @param string $source
     * @param string $rsyncFlags
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     * @param Field[] $columns
     */
    public function __construct($source, $rsyncFlags = "", $resultFormat = "json", $resultFormatConfig = [], $columns = []) {
        $this->source = $source;
        $this->rsyncFlags = $rsyncFlags;
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

    /**
     * @return string
     */
    public function getRsyncFlags() {
        return $this->rsyncFlags;
    }

    /**
     * @param string $rsyncFlags
     */
    public function setRsyncFlags( $rsyncFlags) {
        $this->rsyncFlags = $rsyncFlags;
    }


}