<?php

namespace Kinintel\Objects\Datasource\Document;

use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Application\SettingsService;
use Kiniauth\Services\Attachment\AttachmentService;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Datasource\Document\CustomDocumentParser;
use Kinintel\Services\Util\Analysis\TextAnalysis\DocumentTextExtractor;
use Kinintel\Services\Util\Analysis\TextAnalysis\PhraseExtractor;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Document\DocumentDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\UpdatableMappedField;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\Phrase;

/**
 * Built in document datasource
 */
class DocumentDatasource extends SQLDatabaseDatasource {

    /**
     * Return our config class
     *
     * @return string
     */
    public function getConfigClass() {
        return DocumentDatasourceConfig::class;
    }

    public function getUpdateConfig() {

        $updatableMappedFields = $this->getConfig()->isIndexContent() || $this->getConfig()->getCustomDocumentParser() ? [
            new UpdatableMappedField("phrases", "index_" . $this->getInstanceInfo()->getKey(), [
                "filename" => "document_file_name"
            ])
        ] : [];

        if ($this->getConfig()->getCustomDocumentParser()) {
            $customDocumentParser = Container::instance()->getInterfaceImplementation(CustomDocumentParser::class, $this->getConfig()->getCustomDocumentParser());
            $updatableMappedFields = array_merge($updatableMappedFields, $customDocumentParser->getAdditionalDocumentUpdatableMappedFields($this->getConfig(), $this->getInstanceInfo()) ?? []);
        }

        return new DatasourceUpdateConfig([], $updatableMappedFields);
    }


    /**
     * Update with new data
     *
     * @param TabularDataset $dataset
     * @param $updateMode
     * @return mixed|void
     * @throws \Kinintel\Exception\DatasourceNotUpdatableException
     * @throws \Kinintel\Exception\DatasourceUpdateException
     */
    public function update($dataset, $updateMode = UpdatableDatasource::UPDATE_MODE_ADD) {

        /** @var DocumentDatasourceConfig $config */
        $config = $this->getConfig();


        $newRows = [];
        $fields = [
            new Field("filename"),
            new Field("imported_date"),
            new Field("file_size"),
            new Field("file_type")
        ];

        // Grab custom document parser and merge fields if necessary

        /**
         * @var CustomDocumentParser $customDocumentParser
         */
        $customDocumentParser = null;
        if ($config->getCustomDocumentParser()) {
            $customDocumentParser = Container::instance()->getInterfaceImplementation(CustomDocumentParser::class, $config->getCustomDocumentParser());
            $fields = array_merge($fields, $customDocumentParser->getAdditionalDocumentFields());

            $additionalUpdatableMappedFields = $customDocumentParser->getAdditionalDocumentUpdatableMappedFields($this->getConfig(), $this->getInstanceInfo());
            $fields = array_merge($fields, array_map(function ($updatableField) {
                return new Field($updatableField->getFieldName());
            }, $additionalUpdatableMappedFields ?? []));
        }


        if ($config->isStoreText()) {
            $fields[] = new Field("original_text");
        }

        if ($config->isStoreOriginal()) {
            $fields[] = new Field("original_link");
        }

        if ($config->isIndexContent() || $config->getCustomDocumentParser()) {
            $fields[] = new Field("phrases");
        }

        /**
         * @var SettingsService $settingsService
         */
        $settingsService = Container::instance()->get(SettingsService::class);
        $settings = $settingsService->getParentAccountSettingValues();

        while ($row = $dataset->nextDataItem()) {
            $newRow = null;
            if (($row["filename"] ?? null) &&
                ($row["documentSource"] ?? null) &&
                ($row["file_type"] ?? null)
            ) {
                $newRow = [
                    "filename" => $row["filename"],
                    "imported_date" => date("Y-m-d H:i:s"),
                    "file_size" => strlen($row["documentSource"]),
                    "file_type" => $row["file_type"],
                ];

            } else if (($row["filename"] ?? null) &&
                ($row["documentFilePath"] ?? null) &&
                file_exists($row["documentFilePath"])) {

                $newRow = [
                    "filename" => $row["filename"],
                    "imported_date" => date("Y-m-d H:i:s"),
                    "file_size" => filesize(($row["documentFilePath"])),
                    "file_type" => $row["file_type"] ?? mime_content_type(($row["documentFilePath"]))
                ];

            }

            if ($newRow) {
                $fileType = $newRow["file_type"];
                $text = '';

                // If storing original, call the attachment service
                if ($config->isStoreOriginal()) {

                    $instanceInfo = $this->getInstanceInfo();

                    /**
                     * @var AttachmentService $attachmentService
                     */
                    $attachmentService = Container::instance()->get(AttachmentService::class);

                    // Create an attachment summary
                    $attachmentSummary = new AttachmentSummary($newRow["filename"], $newRow["file_type"], "DocumentDatasourceInstance",
                        $instanceInfo->getKey(), Configuration::readParameter("document.datasource.attachment.storage.key") ?? null,
                        $instanceInfo->getProjectKey(), $instanceInfo->getAccountId());

                    $stream = isset($row["documentSource"]) ? new ReadOnlyStringStream($row["documentSource"]) :
                        new ReadOnlyFileStream($row["documentFilePath"]);

                    // Save the attachment and return an id
                    $attachmentId = $attachmentService->saveAttachment($attachmentSummary, $stream);

                    // Store the original document link
                    $newRow["original_link"] = str_replace("\$id", $attachmentId, $settings["attachmentDownloadURLPattern"] ?? "");

                }

                if ($config->isStoreText() || $config->isIndexContent()) {
                    try {
                        /** @var DocumentTextExtractor $extractor */
                        $extractor = Container::instance()->getInterfaceImplementation(DocumentTextExtractor::class, $fileType);

                        $text = isset($row["documentSource"]) ? $extractor->extractTextFromString($row["documentSource"]) : $extractor->extractTextFromFile($row["documentFilePath"]);

                        if ($config->isStoreText())
                            $newRow["original_text"] = $text;
                    } catch (MissingInterfaceImplementationException $e) {
                        // Missing implemented file type
                    }
                }

                // If we have a custom document parser, use this instead of the built in.
                if ($customDocumentParser) {
                    $customDocumentData = $customDocumentParser->parseDocument($config, $this->getInstanceInfo(), $row["documentSource"] ?? null, $row["documentFilePath"] ?? null);

                    if (!$customDocumentData) {
                        return;
                    }

                    // Merge in any additional document data
                    $mergeRowData = $customDocumentData->getAdditionalDocumentData() ?? [];
                    $newRow = array_merge($newRow, $mergeRowData);

                    // Add any phrases
                    $indexedPhrases = $customDocumentData->getAllPhrasesIndexedBySection();
                    $phrases = [];
                    foreach ($indexedPhrases as $section => $sectionPhrases) {

                        $sectionPhraseData = array_map(function ($phrase) use ($section) {
                            return ["section" => $section, "phrase" => $phrase->getPhrase(), "frequency" => $phrase->getFrequency(), "phrase_length" => $phrase->getLength()];
                        }, $sectionPhrases ?? []);

                        $phrases = array_merge($phrases, $sectionPhraseData);
                    }

                    if (sizeof($phrases) > 0) {
                        $newRow["phrases"] = $phrases;
                    }


                } else if ($config->isIndexContent()) {
                    /** @var PhraseExtractor $phraseExtractor */
                    $phraseExtractor = Container::instance()->get(PhraseExtractor::class);

                    $phrases = $phraseExtractor->extractPhrases($text, $config->getMaxPhraseLength(), $config->getMinPhraseLength(), $config->getStopWords(), 'EN');

                    /** @var Phrase $phrase */
                    $newRow["phrases"] = array_map(function ($phrase) {
                        return ["section" => "Main", "phrase" => $phrase->getPhrase(), "frequency" => $phrase->getFrequency(), "phrase_length" => $phrase->getLength()];
                    }, $phrases ?? []);

                }


                $newRows[] = $newRow;
            }

        }


        // Call with updated dataset
        parent::update(new ArrayTabularDataset($fields, $newRows), $updateMode); // TODO: Change the autogenerated stub
    }


    /**
     * Intercept save method to make table changes as required.
     *
     * @return void
     */
    public function onInstanceSave() {
        $fields = [
            new Field('filename', 'File Name', null, Field::TYPE_STRING, true),
            new Field('imported_date', 'Imported Date', null, Field::TYPE_DATE_TIME),
            new Field('file_size', 'File Size', null, Field::TYPE_INTEGER),
            new Field('file_type', 'File Type', null, Field::TYPE_STRING)
        ];

        /** @var DocumentDatasourceConfig $config */
        $config = $this->getConfig();
        if ($config->isStoreOriginal()) {
            $fields[] = new Field('original_link', 'Original Link', null, Field::TYPE_STRING);
        }

        if ($config->isStoreText()) {
            $fields[] = new Field('original_text', 'Original Text', null, Field::TYPE_LONG_STRING);
        }

        if ($config->getCustomDocumentParser()) {
            $customDocumentParser = Container::instance()->getInterfaceImplementation(CustomDocumentParser::class, $config->getCustomDocumentParser());
            $fields = array_merge($fields, $customDocumentParser->getAdditionalDocumentFields());
        }

        // Update fields with new set.
        parent::updateFields($fields);
    }


    /**
     * Ensure we clear up the index table as a minimum as well as the main table
     *
     * @return mixed|void
     */
    public function onInstanceDelete() {
        parent::onInstanceDelete();

        /** @var DocumentDatasourceConfig $config */
        $config = $this->getConfig();

        // If custom document parser in play we delegate to that for the instance delete
        if ($config->getCustomDocumentParser()) {
            /**
             * @var CustomDocumentParser $customDocumentParser
             */
            $customDocumentParser = Container::instance()->getInterfaceImplementation(CustomDocumentParser::class, $config->getCustomDocumentParser());
            $customDocumentParser->onDocumentDatasourceDelete($config, $this->getInstanceInfo());
        }

        // Drop the index table if it exists
        /** @var DatasourceService $datasourceService */
        $datasourceService = Container::instance()->get(DatasourceService::class);

        try {
            $datasourceService->removeDatasourceInstance("index_" . $this->getInstanceInfo()->getKey());
        } catch (\Exception $e) {
            // Success
        }
    }


}
