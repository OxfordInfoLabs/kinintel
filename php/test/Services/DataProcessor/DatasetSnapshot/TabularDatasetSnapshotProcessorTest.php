<?php


namespace Kinintel\Test\Services\DataProcessor\DatasetSnapshot;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Controllers\API\Dataset;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Services\DataProcessor\DatasetSnapshot\TabularDatasetSnapshotProcessor;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\TestBase;
use Kinintel\ValueObjects\DataProcessor\Configuration\DatasetSnapshot\TabularDatasetSnapshotProcessorConfiguration;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;

include_once "autoloader.php";

class TabularDatasetSnapshotProcessorTest extends TestBase {

    /**
     * @var TabularDatasetSnapshotProcessor
     */
    private $processor;


    /**
     * @var MockObject
     */
    private $datasetService;


    /**
     * @var MockObject
     */
    private $datasourceService;


    public function setUp(): void {
        $this->datasetService = MockObjectProvider::instance()->getMockInstance(DatasetService::class);
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->processor = new TabularDatasetSnapshotProcessor($this->datasetService, $this->datasourceService);
    }

    public function testOnProcessUnderlyingTableAndDatasourceCreatedForAccountWithSnapshotKeyIfNotYetCreated() {

        $this->datasetService->returnValue("getEvaluatedDataSetForDataSetInstanceById",
            new ArrayTabularDataset([
                new Field("title"),
                new Field("metric"),
                new Field("score")
            ], [
            ]), [
                25
            ]);

        $this->datasetService->returnValue("getFullDataSetInstance", new DatasetInstance(null, 1, null), [25]);


        // Ensure we get object not found when obtaining existing datasource
        $this->datasourceService->throwException("getDataSourceInstanceByKey", new ObjectNotFoundException(DatasourceInstance::class, "mytestsnapshot"), [
            "mytestsnapshot"
        ]);


        $expectedDatasourceInstance = new DatasourceInstance("mytestsnapshot", "mytestsnapshot", "sqldatabase", [
            "source" => SQLDatabaseDatasourceConfig::SOURCE_TABLE,
            "tableName" => "mytestsnapshot"
        ], "dataset_snapshot");
        $expectedDatasourceInstance->setAccountId(1);


        $config = new TabularDatasetSnapshotProcessorConfiguration([], [], 25, "mytestsnapshot");
        $this->processor->process($config);



        // Check that the datasource instance was saved
        $this->assertTrue($this->datasourceService->methodWasCalled("saveDataSourceInstance", [
            $expectedDatasourceInstance
        ]));


        // Check that the table was created



    }

}