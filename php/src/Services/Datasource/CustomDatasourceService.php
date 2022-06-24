<?php


namespace Kinintel\Services\Datasource;


use Kiniauth\Objects\Account\Account;
use Kiniauth\ValueObjects\Upload\FileUpload;
use Kiniauth\ValueObjects\Upload\UploadedFile;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Logging\Logger;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateWithStructure;

class CustomDatasourceService {

    /**
     * @var DatasourceService
     */
    private $datasourceService;


    /**
     * @param DatasourceService $datasourceService
     */
    public function __construct($datasourceService) {
        $this->datasourceService = $datasourceService;
    }


    /**
     * Create a new custom datasource instance
     *
     * @param DatasourceUpdateWithStructure $datasourceUpdate
     * @param string $projectKey
     * @param integer $accountId
     */
    public function createCustomDatasourceInstance($datasourceUpdate, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Create a new data source key
        $newDatasourceKey = "custom_data_set_$accountId" . "_" . date("U");
        $credentialsKey = Configuration::readParameter("custom.datasource.credentials.key");
        $tableName = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;

        $datasourceInstance = new DatasourceInstance($newDatasourceKey, $datasourceUpdate->getTitle(), "custom", [
            "source" => "table",
            "tableName" => $tableName,
            "columns" => $datasourceUpdate->getFields()
        ], $credentialsKey);

        // Set account id and project key
        $datasourceInstance->setAccountId($accountId);
        $datasourceInstance->setProjectKey($projectKey);

        $instance = $this->datasourceService->saveDataSourceInstance($datasourceInstance);
        $datasource = $instance->returnDataSource();

        // Update fields
        $datasource->updateFields($datasourceUpdate->getFields());

        if ($datasourceUpdate->getAdds()) {
            $fields = $datasource->getConfig()->getColumns() ?? array_map(function ($columnName) {
                    return new Field($columnName);
                }, array_keys($datasourceUpdate->getAdds()[0]));
            $datasource->update(new ArrayTabularDataset($fields, $datasourceUpdate->getAdds()), UpdatableDatasource::UPDATE_MODE_ADD);
        }

        return $newDatasourceKey;

    }


    /**
     * Create new document datasource instance
     *
     * @param $documentDatasourceConfig
     * @param $projectKey
     * @param $accountId
     * @return string
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function createDocumentDatasourceInstance($documentDatasourceConfig, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $newDatasourceKey = "document_data_set_$accountId" . "_" . date("U");
        $config = $documentDatasourceConfig->getConfig();
        $config["tableName"] = Configuration::readParameter("custom.datasource.table.prefix") . $newDatasourceKey;
        $datasourceInstance = new DatasourceInstance($newDatasourceKey, $documentDatasourceConfig->getTitle(), "document", $config, Configuration::readParameter("custom.datasource.credentials.key"));

        // Set account id and project key
        $datasourceInstance->setAccountId($accountId);
        $datasourceInstance->setProjectKey($projectKey);

        $this->datasourceService->saveDataSourceInstance($datasourceInstance);

        $fields = [
            new Field("document_file_name", "Document File Name", null, Field::TYPE_STRING, true),
            new Field("phrase", "Phrase", null, Field::TYPE_STRING, true),
            new Field("frequency", "Frequency", null, Field::TYPE_INTEGER)
        ];
        $indexInstanceKey = "index_" . $newDatasourceKey;
        $indexDatasourceInstance = new DatasourceInstance($indexInstanceKey, $documentDatasourceConfig->getTitle() . " Index", "sqldatabase", [
            "source" => "table",
            "tableName" => Configuration::readParameter("custom.datasource.table.prefix") . $indexInstanceKey
        ], Configuration::readParameter("custom.datasource.credentials.key"));

        $indexDatasourceInstance->setAccountId($accountId);
        $indexDatasourceInstance->setProjectKey($projectKey);

        $this->datasourceService->saveDataSourceInstance($indexDatasourceInstance);

        $indexDatasourceInstance->returnDataSource()->updateFields($fields);

        return $newDatasourceKey;
    }


    /**
     * Special uploader function for uploading documents to a document datasource
     *
     * @param $datasourceInstanceKey
     * @param $uploadedFiles
     * @return void
     * @throws \Kinikit\Core\Validation\ValidationException
     */
    public function uploadDocumentsToDocumentDatasource($datasourceInstanceKey, $uploadedFiles) {

        $datasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($datasourceInstanceKey);

        $datasource = $datasourceInstance->returnDataSource();

        $fileData = [];

        /** @var \Kinikit\MVC\Request\FileUpload $file */
        foreach ($uploadedFiles ?? [] as $file) {
            $fileParts = explode(".", $file->getClientFilename());
            $fileExtension = end($fileParts);
            if ($file->getMimeType() === 'application/zip' && $fileExtension == 'zip') {

                $zip = new \ZipArchive();
                $zip->open($file->getTemporaryFilePath());
                $entries = $zip->count();
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
                    $datasource->update(new ArrayTabularDataset(
                        [new Field("filename"), new Field("documentSource"), new Field("file_type"), new Field("file_size")],
                        [["filename" => $stat["name"], "documentSource" => $content, "file_type" => $mimeType, "file_size" => $stat["size"]]]
                    ), UpdatableDatasource::UPDATE_MODE_REPLACE);
                }

                $zip->close();

            } else {
                $mimeType = $fileExtension == 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : $file->getMimeType();

                $fileData[] = ["filename" => $file->getClientFilename(), "documentFilePath" => $file->getTemporaryFilePath(), "file_type" => $mimeType];
            }
        }

        $datasource->update(new ArrayTabularDataset([new Field("filename"), new Field("documentFilePath"), new Field("file_type")], $fileData), UpdatableDatasource::UPDATE_MODE_REPLACE);


    }


}
