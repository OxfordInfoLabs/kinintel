<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Objects\Account\Account;
use KiniCRM\Objects\CRM\Contact;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Util\ArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinikit\Persistence\ORM\Query\Filter\LikeFilter;
use Kinikit\Persistence\ORM\Query\Query;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

class DataProcessorDAO {


    /**
     * @var FileResolver
     */
    private $fileResolver;


    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;


    /**
     * @var DataProcessorInstance[]
     */
    private $fileSystemDataProcessors = null;


    const FILTER_MAP = [
        "type" => "type",
        "relatedObjectType" => "relatedObjectType",
        "relatedObjectPrimaryKey" => "relatedObjectPrimaryKey",
        "search" => "search"
    ];


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
     * Get a processor instance by key - this will check
     * the database and the filesystem in that order
     *
     * @param $string
     *
     * @return DataProcessorInstance
     */
    public function getDataProcessorInstanceByKey($key) {

        try {
            return DataProcessorInstance::fetch($key);
        } catch (ObjectNotFoundException $e) {

            // Ensure file system data processors
            $this->ensureFileSystemDataProcessors();

            if (isset($this->fileSystemDataProcessors[$key])) {
                return $this->fileSystemDataProcessors[$key];
            } else {
                throw new ObjectNotFoundException(DataProcessorInstance::class, $key);
            }
        }

    }


    /**
     * Filter data processor instances.  This is designed initially for filtering
     * database driven entries only but could be extended to file ones as well.
     *
     * @param $filterString
     * @param $relatedObjectType
     * @param $relatedObjectPrimaryKey
     * @param $projectKey
     * @param $offset
     * @param $limit
     * @param $accountId
     * @return array
     */
    public function filterDataProcessorInstances($filters = [], $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $query = new Query(DataProcessorInstance::class);

        // Process filters
        $filters = $this->processQueryFilters($filters);

        if (is_numeric($accountId)) {
            $filters["account_id"] = $accountId;
        }
        if ($projectKey) {
            $filters["project_key"] = $projectKey;
        }

        return $query->query($filters, "title ASC", $limit, $offset);
    }


    /**
     * Save a processor instance
     *
     * @param DataProcessorInstance $instance
     */
    public function saveProcessorInstance($instance) {
        $instance->save();
    }


    /**
     * Remove a processor instance by key
     *
     * @param string $instanceKey
     */
    public function removeProcessorInstance($instanceKey) {
        $existing = DataProcessorInstance::fetch($instanceKey);
        $existing->remove();
    }


    // Load file system credentials
    private function ensureFileSystemDataProcessors() {

        if ($this->fileSystemDataProcessors === null) {

            $this->fileSystemDataProcessors = [];

            $searchPaths = $this->fileResolver->getSearchPaths();
            foreach ($searchPaths as $searchPath) {
                $dataProcessorDir = $searchPath . "/Config/dataprocessor";
                if (file_exists($dataProcessorDir)) {
                    $this->loadDataProcessorsFromDirectory($dataProcessorDir);
                }
            }
        }

    }

    private function loadDataProcessorsFromDirectory($directory) {
        $dataProcessors = scandir($directory);
        foreach ($dataProcessors as $dataProcessor) {
            if (strpos($dataProcessor, ".json")) {
                $splitFilename = explode(".", $dataProcessor);
                $instance = $this->jsonToObjectConverter->convert(file_get_contents($directory . "/" . $dataProcessor), DataProcessorInstance::class);
                $instance->setKey($splitFilename[0]);
                $this->fileSystemDataProcessors[$splitFilename[0]] = $instance;
            } else if (substr($dataProcessor, 0, 1) !== "." && is_dir($directory . "/" . $dataProcessor)) {
                $this->loadDataProcessorsFromDirectory($directory . "/" . $dataProcessor);
            }
        }
    }

    /**
     * @param array $filters
     * @return array
     */
    private function processQueryFilters(array $filters): array {
        $filters = ArrayUtils::mapArrayKeys($filters, self::FILTER_MAP);

        if (isset($filters["search"])) {
            $filters["search"] = new LikeFilter(["title"], "%" . $filters["search"] . "%");
        }
        return $filters;
    }


}