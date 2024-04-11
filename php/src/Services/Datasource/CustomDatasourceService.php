<?php


namespace Kinintel\Services\Datasource;


use Exception;
use Google\Client;
use Google\Service\CloudSearch\UpdateBody;
use Google\Service\Drive;
use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Workflow\Task\LongRunning\LongRunningTask;
use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\Validation\ValidationException;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\Document\CustomDocumentParser;
use Kinintel\Services\Util\GoogleDriveService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceConfigUpdate;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

class CustomDatasourceService {

    private DatasourceService $datasourceService;
    private HttpRequestDispatcher $requestDispatcher;
    private GoogleDriveService $googleDriveService;

    public function __construct(DatasourceService $datasourceService, HttpRequestDispatcher $requestDispatcher, GoogleDriveService $googleDriveService) {
        $this->datasourceService = $datasourceService;
        $this->requestDispatcher = $requestDispatcher;
        $this->googleDriveService = $googleDriveService;
    }


    /**
     * Create a new custom datasource instance
     *
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     * @param string $projectKey
     * @param integer $accountId
     * @throws Exception
     */
    public function createCustomDatasourceInstance($datasourceUpdate, $datasourceKey = null, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT, $type = "custom") {

        // Create a new data source key
        $newDatasourceKey = $datasourceKey ?? "custom_data_set_" . ($accountId ? $accountId . "_" : "") . date("U");

        try {

            $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");
            $tableName = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;

            $datasourceInstance = new DatasourceInstance($newDatasourceKey, $datasourceUpdate->getTitle(), $type, [
                "source" => "table",
                "tableName" => $tableName,
                "columns" => $datasourceUpdate->getFields()
            ], $credentialsKey);

            // Set account id and project key
            $datasourceInstance->setAccountId($accountId);
            $datasourceInstance->setProjectKey($projectKey);
            $this->datasourceService->saveDataSourceInstance($datasourceInstance);

            // If adds to deal with, call main update function
            if ($datasourceUpdate->getAdds()) {
                $this->datasourceService->updateDatasourceInstanceByKey($newDatasourceKey, $datasourceUpdate);
            }

            return $newDatasourceKey;
        } catch (Exception $e) {

            try {
                $this->datasourceService->removeDatasourceInstance($newDatasourceKey);
            } catch (Exception $e) {
            }

            throw $e;
        }

    }

    /**
     * Create new snapshot datasource instance and return the snapshot instance.
     * The datasource type is SQLDatabaseDatasource
     *
     * @param Field[] $fields
     * @param string $projectKey
     * @param integer $accountId
     *
     * @return DatasourceInstance
     */
    public function createTabularSnapshotDatasourceInstance($title, $fields = [], $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {
        $newInstanceKey = "snapshot_data_set_$accountId" . "_" . date("U");

        // Create a new data source instance with underlying datasource type SQLDatabaseDatasource and save it.
        $dataSourceInstance = new DatasourceInstance($newInstanceKey, $title, "snapshot",
            [
                "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
                "tableName" => $newInstanceKey,
                "columns" => $fields
            ], Configuration::readParameter("snapshot.datasource.credentials.key"));
        $dataSourceInstance->setAccountId($accountId);
        $dataSourceInstance->setProjectKey($projectKey);

        // Save the datasource instance and return a new one
        $this->datasourceService->saveDataSourceInstance($dataSourceInstance);

        return $dataSourceInstance;
    }

    /**
     * Create new document datasource instance (neglects custom tablenames)
     *
     * @param DatasourceConfigUpdate $datasourceConfigUpdate
     * @param $projectKey
     * @param $accountId
     * @return string
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function createDocumentDatasourceInstance($datasourceConfigUpdate, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $newDatasourceKey = "document_data_set_$accountId" . "_" . date("U");
        $config = $datasourceConfigUpdate->getConfig();
        $config["tableName"] = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;
        $datasourceInstance = new DatasourceInstance(
            $newDatasourceKey,
            $datasourceConfigUpdate->getTitle(),
            "document",
            $config,
            Configuration::readParameter("custom.datasource.credentials.key")
        );

        // Set account id and project key
        $datasourceInstance->setAccountId($accountId);
        $datasourceInstance->setProjectKey($projectKey);

        $this->datasourceService->saveDataSourceInstance($datasourceInstance);

        // Phrase index table
        $phraseFields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("section", "Section", null, Field::TYPE_STRING, true),
            new Field("phrase", "Phrase", null, Field::TYPE_STRING, true),
            new Field("phrase_length", "Phrase Length", null, Field::TYPE_INTEGER),
            new Field("frequency", "Frequency", null, Field::TYPE_INTEGER)
        ];

        $indexInstanceKey = "index_" . $newDatasourceKey;
        $indexDatasourceInstance = new DatasourceInstance($indexInstanceKey, $datasourceConfigUpdate->getTitle() . " Index", "sqldatabase", [
            "source" => "table",
            "tableName" => Configuration::readParameter("custom.datasource.table.prefix") . $indexInstanceKey,
            "columns" => $phraseFields,
            "manageTableStructure" => true
        ], Configuration::readParameter("custom.datasource.credentials.key"));

        $indexDatasourceInstance->setAccountId($accountId);
        $indexDatasourceInstance->setProjectKey($projectKey);

        $this->datasourceService->saveDataSourceInstance($indexDatasourceInstance);

        //Chunk Embedding table
        $chunksFields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("chunk_text", "Chunk Text", null, Field::TYPE_LONG_STRING),
            new Field("chunk_number", "Chunk Number", null, Field::TYPE_INTEGER, true),
            new Field("chunk_pointer", "Chunk Pointer", null, Field::TYPE_INTEGER),
            new Field("chunk_length", "Chunk Length", null, Field::TYPE_INTEGER),
            new Field("embedding", "Embedding", null, Field::TYPE_LONG_STRING)
        ];

        $embeddingInstanceKey = "chunks_" . $newDatasourceKey;
        $embeddingInstance = new DatasourceInstance($embeddingInstanceKey, $datasourceConfigUpdate->getTitle() . " Chunks", "sqldatabase", [
            "source" => "table",
            "tableName" => Configuration::readParameter("custom.datasource.table.prefix") . $embeddingInstanceKey,
            "columns" => $chunksFields,
            "manageTableStructure" => true
        ], Configuration::readParameter("custom.datasource.credentials.key"));

        $embeddingInstance->setAccountId($accountId);
        $embeddingInstance->setProjectKey($projectKey);

        $this->datasourceService->saveDataSourceInstance($embeddingInstance);

        if (is_array($config)) {
            /**
             * @var ObjectBinder $binder
             */
            $binder = Container::instance()->get(ObjectBinder::class);
            $config = $binder->bindFromArray($config, DocumentDatasourceConfig::class);
        }

        if ($config->getCustomDocumentParser()) {
            $parser = Container::instance()->getInterfaceImplementation(CustomDocumentParser::class, $config->getCustomDocumentParser());
            $parser->onDocumentDatasourceCreate($config, $datasourceInstance, $accountId, $projectKey);
        }

        return $newDatasourceKey;
    }


    /**
     * Special uploader function for uploading documents to a document datasource
     *
     * @param $datasourceInstanceKey
     * @param $uploadedFiles
     * @param LongRunningTask|null $longRunningTask
     * @return void
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function uploadDocumentsToDocumentDatasource($datasourceInstanceKey, $uploadedFiles, $longRunningTask = null) {

        $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($datasourceInstanceKey);

        $datasource = $datasourceInstance->returnDataSource();

        $totalFiles = sizeof($uploadedFiles);
        $completed = 0;
        $failed = [];

        /** @var \Kinikit\MVC\Request\FileUpload $file */
        foreach ($uploadedFiles ?? [] as $file) {
            $fileParts = explode(".", $file->getClientFilename());
            $fileExtension = end($fileParts);
            if ($file->getMimeType() === 'application/zip' && $fileExtension == 'zip') {

                $zip = new \ZipArchive();
                $zip->open($file->getTemporaryFilePath());
                $entries = $zip->count();
                $totalFiles += $entries - 1;
                for ($i = 0; $i < $entries; $i++) {
                    $stat = $zip->statIndex($i);
                    if (substr($zip->getNameIndex($i), 0, 9) === "__MACOSX/") {
                        continue;
                    }
                    $filename = $stat["name"];
                    $parts = explode(".", $filename);
                    $extension = end($parts);
                    $mimeType = '';

                    if ($extension) {
                        if ($extension === 'docx') {
                            $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        } else if ($extension === 'pdf') {
                            $mimeType = 'application/pdf';
                        } else if ($extension === 'html') {
                            $mimeType = 'text/html';
                        } else if ($extension === 'txt') {
                            $mimeType = 'text/plain';
                        }
                    }

                    $content = $zip->getFromName($stat["name"]);
                    try {
                        $datasource->update(new ArrayTabularDataset(
                            [new Field("filename"), new Field("documentSource"), new Field("file_type"), new Field("file_size")],
                            [["filename" => $stat["name"], "documentSource" => $content, "file_type" => $mimeType, "file_size" => $stat["size"]]]
                        ), UpdatableDatasource::UPDATE_MODE_REPLACE);
                        $completed++;


                    } catch (Exception $e) {
                        $failed[] = [
                            "filename" => $stat["name"],
                            "message" => $e->getMessage()
                        ];
                    }
                    if ($longRunningTask) {
                        $longRunningTask->updateProgress([
                            "completed" => $completed,
                            "total" => $totalFiles,
                            "failed" => $failed
                        ]);
                    }
                }

                $zip->close();

            } else {
                $mimeType = $fileExtension == 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : $file->getMimeType();

                $fileData = ["filename" => $file->getClientFilename(), "documentFilePath" => $file->getTemporaryFilePath(), "file_type" => $mimeType];

                try {
                    $datasource->update(new ArrayTabularDataset([
                        new Field("filename"),
                        new Field("documentFilePath"),
                        new Field("file_type")
                    ],
                        [$fileData]), UpdatableDatasource::UPDATE_MODE_REPLACE);

                    $completed++;

                } catch (Exception $e) {
                    $failed[] = [
                        "filename" => $file->getClientFilename(),
                        "message" => $e->getMessage()
                    ];
                }
                if ($longRunningTask) {
                    $longRunningTask->updateProgress([
                        "completed" => $completed,
                        "total" => $totalFiles,
                        "failed" => $failed
                    ]);
                }

            }
        }


    }

    /**
     * Uploads documents to a datasource from a list of links
     *
     * @param string $datasourceInstanceKey
     * @param string[] $links
     * @param int $limit
     * @param int $offset
     * @return void
     * @throws ValidationException
     */
    public function uploadDocumentsFromUrl(string $datasourceInstanceKey, array $links, $limit = PHP_INT_MAX, $offset = 0): void {
        $datasource = $this->datasourceService->getDataSourceInstanceByKey($datasourceInstanceKey)->returnDataSource();

        $links = array_values($links);

        for ($i = $offset; $i < min($offset + $limit, count($links)); $i++) {
            $link = $links[$i];

//            echo "\ni: $i || memory use: " . memory_get_usage() . " || link: $link";

            [$contents, $filetype, $filesize] = $this->retryOnFail($this->downloadFromLink(...), [$link]);

            $fileData = [
                "filename" => $link,
                "documentSource" => $contents,
                "file_type" => $filetype,
                "file_size" => $filesize
            ];

            $update = new ArrayTabularDataset([
                new Field("filename"),
                new Field("documentSource"),
                new Field("file_type"),
                new Field("file_size")
            ], [$fileData]);

            $datasource->update($update, UpdatableDatasource::UPDATE_MODE_REPLACE);
        }
    }

    /**
     * Turns the content type header into a file extension
     *
     * @param string $contentType
     * @return string
     */
    private function toFileType(string $contentType): string {
        $mimetype = strstr($contentType, ";", true);
        if (!$mimetype) {
            $mimetype = $contentType;
        }
        return $mimetype;
    }

    private function downloadFromLink($link) : array {
        // Process link if it's from a Google Drive share link
        if (str_contains($link, "drive.google")){

            if (!$id = substr(strstr($link, "id="), 3)){
                $matches = [];
                preg_match("/file\/d\/(.*?)\//", $link, $matches);
                $id = $matches[1];
            }
            [$contents, $filetype, $filesize] = $this->googleDriveService->downloadFile($id);
        } else {
            $request = new Request($link, "GET");
            $response = $this->requestDispatcher->dispatch($request);
            $contents = $response->getStream()->getContents();
            $filetype = $this->toFileType($response->getHeaders()->get("content-type"));
            $filesize = $response->getHeaders()->get("content-length");
        }

        return [$contents, $filetype, $filesize];
    }

    private function retryOnFail(callable $downloadFromLink, array $args) {
        try {
            return $downloadFromLink(...$args);
        } catch (Exception $e){
            return $downloadFromLink(...$args);
        }
    }

}
