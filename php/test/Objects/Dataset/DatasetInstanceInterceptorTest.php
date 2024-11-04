<?php

namespace Kinintel\Test\Objects\Dataset;

use Kiniauth\Objects\MetaData\ObjectStructuredData;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Exception\ItemInUseException;
use Kinintel\Exception\ManagementKeyAlreadyExistsException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceInterceptor;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Feed\Feed;
use Kinintel\Objects\Feed\FeedSummary;
use Kinintel\Services\DataProcessor\DataProcessor;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasetInstanceInterceptorTest extends TestCase {

    /**
     * @var DatasetInstanceInterceptor
     */
    private $interceptor;

    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;

    public function setUp(): void {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->dataProcessorService = MockObjectProvider::instance()->getMockInstance(DataProcessorService::class);

        $this->interceptor = new DatasetInstanceInterceptor(Container::instance()->get(DatabaseConnection::class),
            Container::instance()->get(MetaDataService::class), Container::instance()->get(DatasetService::class),
            $this->dataProcessorService);

        Container::instance()->get(DatabaseConnection::class)->execute("DELETE FROM ki_dataset_instance WHERE title = ?", "Test Dep Dataset");

    }


    /**
     * @doesNotPerformAssertions
     *
     */
    public function testIfManagementKeyDefinedItIsCheckedInPresave() {
        $masterDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json"));
        $masterDatasetInstance->setManagementKey("badger");
        $masterDatasetInstance->save();

        $newDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset2", "test-json"));
        $newDatasetInstance->setManagementKey("badger");

        try {
            $this->interceptor->preSave($newDatasetInstance);
            $this->fail("Should have thrown here");
        } catch (ManagementKeyAlreadyExistsException $e) {
            // Success
        }
    }


    public function testIfDatasetInstanceReferencedByOtherDatasetItCannotBeDeleted() {

        $masterDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json"));
        $masterDatasetInstance->save();

        // Check deletion is possible with no references
        $this->interceptor->preDelete($masterDatasetInstance);


        $childDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", null, $masterDatasetInstance->getId()));
        $childDatasetInstance->save();

        try {
            $this->interceptor->preDelete($masterDatasetInstance);
            $this->fail("Should have thrown here");
        } catch (ItemInUseException $e) {
            $this->assertTrue(true);
        }

    }


    public function testStructuredDataUpdatedOnPostSaveWithReferencedDatasetsAndDatasources() {

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json", null, [
            new TransformationInstance("join", new JoinTransformation("test-json-explicit-creds"))
        ]));
        $datasetInstance->setId(5000);
        $this->interceptor->postSave($datasetInstance);


        $structuredData = ObjectStructuredData::filter("WHERE object_type = ? AND object_id = ? AND data_type = ?",
            DatasetInstance::class, 5000, "referencedDataSource");


        $this->assertEquals(1, sizeof($structuredData));
        $this->assertEquals("test-json-explicit-creds", $structuredData[0]->getPrimaryKey());


        // Check a full replace occurs when updating transformations
        $datasetInstance->setTransformationInstances([
            new TransformationInstance("join", new JoinTransformation("test-json")),
            new TransformationInstance("join", new JoinTransformation("test-json-invalid-config"))
        ]);

        $this->interceptor->postSave($datasetInstance);


        $structuredData = ObjectStructuredData::filter("WHERE object_type = ? AND object_id = ? AND data_type = ?",
            DatasetInstance::class, 5000, "referencedDataSource");


        $this->assertEquals(2, sizeof($structuredData));
        $this->assertEquals("test-json", $structuredData[0]->getPrimaryKey());
        $this->assertEquals("test-json-invalid-config", $structuredData[1]->getPrimaryKey());


        // Set to blank array and confirm that all entries have been removed
        $datasetInstance->setTransformationInstances([]);

        $this->interceptor->postSave($datasetInstance);


        $structuredData = ObjectStructuredData::filter("WHERE object_type = ? AND object_id = ? AND data_type = ?",
            DatasetInstance::class, 5000, "referencedDataSource");
        $this->assertEquals(0, sizeof($structuredData));

        // Now try a data set situation
        // Check a full replace occurs when updating transformations
        $datasetInstance->setTransformationInstances([
            new TransformationInstance("join", new JoinTransformation(null, 55)),
            new TransformationInstance("join", new JoinTransformation(null, 66))
        ]);

        $this->interceptor->postSave($datasetInstance);


        $structuredData = ObjectStructuredData::filter("WHERE object_type = ? AND object_id = ? AND data_type = ?",
            DatasetInstance::class, 5000, "referencedDataSet");


        $this->assertEquals(2, sizeof($structuredData));
        $this->assertEquals(55, $structuredData[0]->getPrimaryKey());
        $this->assertEquals(66, $structuredData[1]->getPrimaryKey());


        // Set to blank array and confirm that all entries have been removed
        $datasetInstance->setTransformationInstances([]);

        $this->interceptor->postSave($datasetInstance);

        $structuredData = ObjectStructuredData::filter("WHERE object_type = ? AND object_id = ? AND data_type = ?",
            DatasetInstance::class, 5000, "referencedDataSet");
        $this->assertEquals(0, sizeof($structuredData));


    }


    public function testIfDataProcessorExistsWithRelatedObjectKeyMatchingDatasetOnReferencedObjectSaveIsCalled() {


        $instance1 = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);
        $instance2 = MockObjectProvider::instance()->getMockInstance(DataProcessorInstance::class);

        $processor1 = MockObjectProvider::instance()->getMockInstance(DataProcessor::class);
        $processor2 = MockObjectProvider::instance()->getMockInstance(DataProcessor::class);

        $instance1->returnValue("returnProcessor", $processor1, []);
        $instance2->returnValue("returnProcessor", $processor2, []);

        $dataProcessorInstances = [
            $instance1, $instance2
        ];

        $this->dataProcessorService->returnValue("filterDataProcessorInstances",
            $dataProcessorInstances, [
                ["relatedObjectType" => "DatasetInstance", "relatedObjectKey" => 5000],
                null,
                0,
                1000000
            ]);

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test Dataset With Processor", "test-json", null, [
            new TransformationInstance("join", new JoinTransformation("test-json-explicit-creds"))
        ]));
        $datasetInstance->setId(5000);
        $this->interceptor->postSave($datasetInstance);

        // Check on related object save was called
        $this->assertTrue($processor1->methodWasCalled("onRelatedObjectSave", [$instance1, $datasetInstance]));
        $this->assertTrue($processor2->methodWasCalled("onRelatedObjectSave", [$instance2, $datasetInstance]));


    }


    public function testIfDatasetReferencedInJoinTransformationInOtherDatasetItCannotBeDeleted() {


        $referencedDataSet = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json"));
        $referencedDataSet->save();

        // Check deletion is possible with no references
        $this->interceptor->preDelete($referencedDataSet);

        $mainDataset = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json", null, [
            new TransformationInstance("join", new JoinTransformation(null, $referencedDataSet->getId()))
        ]));
        $mainDataset->save();


        // Now attempt delete omn the referenced set and check we caught it
        try {
            $this->interceptor->preDelete($referencedDataSet);
            $this->fail("Should have thrown here");
        } catch (ItemInUseException $e) {
            $this->assertTrue(true);
        }


    }


    public function testIfFeedExistsForDatasetItIsCheckedForOnDeleteOfDataset() {

        $referencedDataSet = new DatasetInstance(new DatasetInstanceSummary("Test Dep Dataset", "test-json"));
        $referencedDataSet->save();

        $feed = new Feed(new FeedSummary("bingo", $referencedDataSet->getId(), [], "", []), null, null);
        $feed->save();

        // Now attempt delete omn the referenced set and check we caught it
        try {
            $this->interceptor->preDelete($referencedDataSet);
            $this->fail("Should have thrown here");
        } catch (ItemInUseException $e) {
            $this->assertTrue(true);
        }


    }


}