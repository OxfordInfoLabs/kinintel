<?php


namespace Kinintel\Services\Datasource\Document;

use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceInstanceInfo;
use Kinintel\ValueObjects\Datasource\Document\CustomDocumentData;

/**
 * Custom document parser, used to parse custom document structures for specific document types
 *
 *
 * Interface CustomDocumentParser
 * @package Kinintel\Services\Datasource\Document
 */
abstract class CustomDocumentParser {

    /**
     * Return an array of additional fields to be added to the main document datasource if required.
     *
     * @return Field[]
     */
    public function getAdditionalDocumentFields() {
        return [];
    }


    /**
     * Return an array of additional updatable mapped fields to be merged into the update config for the
     * document datasource.
     *
     * @param $datasourceConfig
     * @param $documentDatasourceInfo
     * @return array
     */
    public function getAdditionalDocumentUpdatableMappedFields($datasourceConfig, $documentDatasourceInfo) {
        return [];
    }


    /**
     * Function called when document datasource is created to perform additional logic as required.
     *
     * @param DocumentDatasourceConfig $datasourceConfig
     * @param $documentDatasourceInfo
     * @param $accountId
     * @param $projectKey
     */
    public function onDocumentDatasourceCreate($datasourceConfig, $documentDatasourceInfo, $accountId, $projectKey) {
    }


    /**
     * Function called when document datasource is deleted to perform additional logic as required.
     *
     * @param DocumentDatasourceConfig $datasourceConfig
     * @param DatasourceInstanceInfo $datasourceInstanceInfo
     */
    public function onDocumentDatasourceDelete($datasourceConfig, $datasourceInstanceInfo) {
    }


    /**
     * Parse the document using the supplied config and sourced either directly from the document source string
     * or from the file pointed to by the document source filename.
     *
     * This returns a custom document data structure which should contain additional document field values matching the
     * additional fields defined above as well as phrases keyed in by document section.
     *
     * @param DocumentDatasourceConfig $datasourceConfig
     * @param string $documentSource
     * @param string $documentSourceFilename
     *
     * @return CustomDocumentData
     */
    public function parseDocument($datasourceConfig, $documentSource = null, $documentSourceFilename = null) {
        return new CustomDocumentData();
    }


}
