<?php


namespace Kinintel\ValueObjects\Datasource;


use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\ValueObjects\Parameter\Parameter;

class DatasourceInstanceInfo {

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $importKey;

    /**
     * @var Parameter[]
     */
    private $parameters;

    /**
     * @var string
     */
    private $projectKey;


    /**
     * @var integer
     */
    private $accountId;


    /**
     * DatasourceInstanceInfo constructor.
     *
     * @param DatasourceInstance $datasourceInstance
     */
    public function __construct($datasourceInstance) {

        // If an instance, set params
        if ($datasourceInstance) {
            $this->title = $datasourceInstance->getTitle();
            $this->importKey = $datasourceInstance->getImportKey();
            $this->key = $datasourceInstance->getKey();
            $this->parameters = $datasourceInstance->getParameters();
            $this->projectKey = $datasourceInstance->getProjectKey();
            $this->accountId = $datasourceInstance->getAccountId();
        }
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }


    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getImportKey() {
        return $this->importKey;
    }


    /**
     * @return Parameter[]
     */
    public function getParameters() {
        return $this->parameters;
    }


    /**
     * @return string
     */
    public function getProjectKey() {
        return $this->projectKey;
    }


    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }


}