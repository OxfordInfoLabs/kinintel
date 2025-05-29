<?php

namespace Kinintel;

use Kiniauth\Services\Attachment\AttachmentStorage;
use Kiniauth\Services\ImportExport\ProjectImporterExporter;
use Kiniauth\Services\Workflow\Task\Task;
use Kinikit\Core\ApplicationBootstrap;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\ValueFunction\ValueFunctionEvaluator;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Services\Alert\AlertGroupTask;
use Kinintel\Services\DataProcessor\DataProcessorTask;
use Kinintel\Services\ImportExport\ImportExporters\AlertGroupImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\DashboardImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\DataProcessorImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\DatasetImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\DatasourceHookImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\DatasourceImportExporter;
use Kinintel\Services\ImportExport\ImportExporters\FeedImportExporter;
use Kinintel\Services\Util\AttachmentStorage\GoogleCloudAttachmentStorage;
use Kinintel\Services\Util\SQLiteFunctions\DotProduct;
use Kinintel\Services\Util\SQLiteFunctions\Levenshtein;
use Kinintel\Services\Util\SQLiteFunctions\Regexp;
use Kinintel\Services\Util\SQLiteFunctions\Reverse;
use Kinintel\Services\Util\ValueFunction\AuthenticatedItemsValueFunction;

class Bootstrap implements ApplicationBootstrap {

    /**
     * Wire up value function
     *
     * @param ValueFunctionEvaluator $valueFunctionEvaluator
     * @param AuthenticatedItemsValueFunction $authenticatedItemsValueFunction
     */
    public function __construct(
        private ValueFunctionEvaluator          $valueFunctionEvaluator,
        private AuthenticatedItemsValueFunction $authenticatedItemsValueFunction) {
        $this->valueFunctionEvaluator->addValueFunction($this->authenticatedItemsValueFunction);
    }

    public function setup() {

        SQLite3DatabaseConnection::addCustomFunction(new DotProduct());
        SQLite3DatabaseConnection::addCustomFunction(new Levenshtein());
        SQLite3DatabaseConnection::addCustomFunction(new Regexp());
        SQLite3DatabaseConnection::addCustomFunction(new Reverse());

        // Inject task implementations specific to kinintel
        Container::instance()->addInterfaceImplementation(Task::class, "alertgroup", AlertGroupTask::class);
        Container::instance()->addInterfaceImplementation(Task::class, "dataprocessor", DataProcessorTask::class);

        // Add attachment storage for google
        Container::instance()->addInterfaceImplementation(AttachmentStorage::class, "google-cloud", GoogleCloudAttachmentStorage::class);

        // Inject importer exporters
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(AlertGroupImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(DatasourceImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(DatasetImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(FeedImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(DataProcessorImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(DashboardImportExporter::class));
        Container::instance()->get(ProjectImporterExporter::class)->addImportExporter(Container::instance()->get(DatasourceHookImportExporter::class));

    }
}