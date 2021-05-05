<?php


namespace Kinintel\Services\Datasource;


use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\Datasource\DatasourceInstance;

class DatasourceService {


    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;


    /**
     * Cached file system credentials
     *
     * @var DataSourceInstance[]
     */
    private $fileSystemDataSources = null;

    /**
     * AuthenticationCredentialsService constructor.
     *
     * @param FileResolver $fileResolver
     * @param JSONToObjectConverter $jsonToObjectConverter
     */
    public function __construct($fileResolver, $jsonToObjectConverter) {
        $this->fileResolver = $fileResolver;
        $this->jsonToObjectConverter = $jsonToObjectConverter;
    }


    /**
     * Get a datasource instance by key
     *
     * @param $key
     * @return DatasourceInstance
     */
    public function getDataSourceInstanceByKey($key) {

        try {
            return DatasourceInstance::fetch($key);
        } catch (ItemNotFoundException $e) {

            // Ensure we have loaded any built in credentials
            if ($this->fileSystemDataSources === null) {
                $this->loadFileSystemDatasources();
            }

            if (isset($this->fileSystemDataSources[$key])) {
                return $this->fileSystemDataSources[$key];
            }
        }
    }


    /**
     * Save a datasource instance
     *
     * @param DatasourceInstance $dataSourceInstance
     */
    public function saveDataSourceInstance($dataSourceInstance) {
        $dataSourceInstance->save();
    }


    // Load file system credentials
    private function loadFileSystemDatasources() {
        $this->fileSystemDataSources = [];

        $searchPaths = $this->fileResolver->getSearchPaths();
        foreach ($searchPaths as $searchPath) {
            $dataSourceDir = $searchPath . "/Config/datasource";
            if (file_exists($dataSourceDir)) {
                $dataSources = scandir($dataSourceDir);
                foreach ($dataSources as $dataSource) {
                    if (strpos($dataSource, ".json")) {
                        $splitFilename = explode(".", $dataSource);
                        $instance = $this->jsonToObjectConverter->convert(file_get_contents($dataSourceDir . "/" . $dataSource), DataSourceInstance::class);
                        $instance->setKey($splitFilename[0]);
                        $this->fileSystemDataSources[$splitFilename[0]] = $instance;
                    }
                }
            }
        }


    }


}