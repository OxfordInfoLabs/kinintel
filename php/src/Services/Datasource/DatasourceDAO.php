<?php


namespace Kinintel\Services\Datasource;


use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;

class DatasourceDAO {

    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;


    /**
     * Cached file system data sources
     *
     * @var DataSourceInstance[]
     */
    private $fileSystemDataSources = null;

    /**
     * Datasource DAO constructor.
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
        } catch (ObjectNotFoundException $e) {

            // Ensure we have loaded any built in credentials
            if ($this->fileSystemDataSources === null) {
                $this->loadFileSystemDatasources();
            }

            if (isset($this->fileSystemDataSources[$key])) {
                return $this->fileSystemDataSources[$key];
            } else {
                throw new ObjectNotFoundException(DatasourceInstance::class, $key);
            }
        }
    }


    /**
     * Get an array of filtered datasources using passed filter string to limit on
     * name of data source.  This checks both local datasources and database ones.
     *
     * If account id and/or project key is passed the returned instances are restricted accordingly
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     * @param string $projectKey
     * @param int $accountId
     * @param boolean $strictMode
     *
     * @return DatasourceInstanceSearchResult[]
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0, $projectKey = null, $accountId = null) {
        $this->loadFileSystemDatasources();

        if ($accountId || $projectKey) {

            $sql = "WHERE title LIKE ? AND type <> 'snapshot'";
            $params = ["%$filterString%"];

            if ($accountId) {
                $sql .= " AND account_id = ?";
                $params[] = $accountId;
            }
            if ($projectKey) {
                $sql .= " AND project_key = ?";
                $params[] = $projectKey;
            }

            $matches = array_map(function ($dbMatch) {
                return new DatasourceInstanceSearchResult($dbMatch->getKey(), $dbMatch->getTitle(), $dbMatch->getType());
            }, DatasourceInstance::filter($sql, $params));

        } else {
            // Firstly loop through the file system ones and gather any matches
            $matches = [];
            foreach ($this->fileSystemDataSources as $dataSource) {
                if (!$filterString || is_numeric(strpos(strtolower($dataSource->getTitle()), strtolower($filterString)))) {
                    $matches[] = new DatasourceInstanceSearchResult($dataSource->getKey(), $dataSource->getTitle(), $dataSource->getType());
                }
            }

            // If still more to get, search the db.
            $dbMatches = DatasourceInstance::filter("WHERE title LIKE ? AND type <> 'snapshot' AND account_id IS NULL",
                "%$filterString%");

            $newMatches = array_map(function ($dbMatch) {
                return new DatasourceInstanceSearchResult($dbMatch->getKey(), $dbMatch->getTitle(), $dbMatch->getType());
            }, $dbMatches);

            $matches = array_merge($matches, $newMatches);
        }

        usort($matches, function ($x, $y) {
            return ($x->getTitle() > $y->getTitle() ? 1 : -1);
        });


        return array_slice($matches, $offset, $limit);


    }


    /**
     * Save a datasource instance
     *
     * @param DatasourceInstance $dataSourceInstance
     */
    public function saveDataSourceInstance($dataSourceInstance) {
        $dataSourceInstance->save();
        return $dataSourceInstance;
    }


    /**
     * Remove a datasource instance by key
     *
     * @param $dataSourceInstanceKey
     */
    public function removeDatasourceInstance($dataSourceInstanceKey) {
        try {
            $dbDatasource = DatasourceInstance::fetch($dataSourceInstanceKey);
            $dbDatasource->remove();
        } catch (ObjectNotFoundException $e) {
        }
    }


    // Load file system credentials
    private function loadFileSystemDatasources() {
        $this->fileSystemDataSources = [];

        $searchPaths = $this->fileResolver->getSearchPaths();
        foreach ($searchPaths as $searchPath) {
            $dataSourceDir = $searchPath . "/Config/datasource";
            if (file_exists($dataSourceDir)) {
                $this->loadDatasourcesFromDirectory($dataSourceDir);
            }
        }

    }

    private function loadDatasourcesFromDirectory($directory) {
        $dataSources = scandir($directory);
        foreach ($dataSources as $dataSource) {
            if (strpos($dataSource, ".json")) {
                $splitFilename = explode(".", $dataSource);
                $instance = $this->jsonToObjectConverter->convert(file_get_contents($directory . "/" . $dataSource), DataSourceInstance::class);
                $instance->setKey($splitFilename[0]);
                $this->fileSystemDataSources[$splitFilename[0]] = $instance;
            } else if (substr($dataSource, 0, 1) !== "." && is_dir($directory . "/" . $dataSource)) {
                $this->loadDatasourcesFromDirectory($directory . "/" . $dataSource);
            }
        }
    }


}