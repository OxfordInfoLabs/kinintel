<?php

namespace Kinintel\Services\Datasource;

use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTask;
use Kinikit\MVC\Request\FileUpload;

class DocumentUploadLongRunningTask extends LongRunningTask {

    /**
     * @var CustomDatasourceService
     */
    private $datasourceService;

    /**
     * @var string
     */
    private $datasourceInstanceKey;

    /**
     * @var FileUpload[]
     */
    private $uploadedFiles;

    /**
     * @param CustomDatasourceService $datasourceService
     * @param string $datasourceInstanceKey
     * @param FileUpload[] $uploadedFiles
     */
    public function __construct($datasourceService, $datasourceInstanceKey, $uploadedFiles) {
        $this->datasourceService = $datasourceService;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->uploadedFiles = $uploadedFiles;
    }



    public function run() {
        return $this->datasourceService->uploadDocumentsToDocumentDatasource($this->datasourceInstanceKey, $this->uploadedFiles, $this);
    }
}
