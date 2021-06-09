<?php


namespace Kinintel\Services\DataProcessor;


use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
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

}