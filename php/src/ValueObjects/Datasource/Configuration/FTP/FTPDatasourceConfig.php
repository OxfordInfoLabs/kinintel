<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\FTP;


use Kinintel\ValueObjects\Datasource\Configuration\DatasourceCompressionConfig;
use Kinintel\ValueObjects\Datasource\Configuration\FormattedResultDatasourceConfig;

class FTPDatasourceConfig extends FormattedResultDatasourceConfig {


    // Use compression config
    use DatasourceCompressionConfig;


    /**
     * The hostname to connect to via FTP
     *
     * @var string
     * @required
     */
    protected $hostname;

    /**
     * @var string
     * @required
     */
    protected $filePath;

    /**
     * Whether to connect securely via SFTP or not
     *
     * @var boolean
     */
    protected $secure;

    /**
     * FTPDatasourceConfig constructor.
     *
     * @param string $hostname
     * @param string $filePath
     * @param bool $secure
     * @param string $resultFormat
     * @param mixed $resultFormatConfig
     * @param Field[] $columns
     */
    public function __construct($hostname, $filePath, $secure = true, $resultFormat = "json", $resultFormatConfig = [], $columns = []) {
        $this->hostname = $hostname;
        $this->filePath = $filePath;
        $this->secure = $secure;
        parent::__construct($resultFormat, $resultFormatConfig, $columns);
    }


    /**
     * @return string
     */
    public function getHostname() {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname($hostname) {
        $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getFilePath() {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }


    /**
     * @return bool
     */
    public function isSecure() {
        return $this->secure;
    }

    /**
     * @param bool $secure
     */
    public function setSecure($secure) {
        $this->secure = $secure;
    }


}