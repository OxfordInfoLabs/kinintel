<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Test\ValueObjects\Transformation\AnotherTestTransformation;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasetServiceTest extends TestBase {

    /**
     * @var MockObject
     */
    private $datasourceService;

    /**
     * @var MockObject
     */
    private $metaDataService;

    /**
     * @var DatasetService
     */
    private $datasetService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->metaDataService = MockObjectProvider::instance()->getMockInstance(MetaDataService::class);
        $this->datasetService = new DatasetService($this->datasourceService, $this->metaDataService);
    }


    public function testDataSourceReturnedIfDataSetWithNoTransformationsPassedToEvaluateFunction() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test");
        $this->assertEquals($dataSource, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance));


    }


    public function testTransformationsAppliedInSequenceIfDataSetWithTransformationsPassedToEvaluateFunctionWithSupportedTransformations() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(Transformation::class);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance2 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance3 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $transformationInstance1->returnValue("returnTransformation", $transformation1);
        $transformationInstance2->returnValue("returnTransformation", $transformation2);
        $transformationInstance3->returnValue("returnTransformation", $transformation3);


        $transformed1 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformed2 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed2->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformed3 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1
        ]);

        $transformed1->returnValue("applyTransformation", $transformed2, [
            $transformation2
        ]);

        $transformed2->returnValue("applyTransformation", $transformed3, [
            $transformation3
        ]);


        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test", [
            $transformationInstance1, $transformationInstance2, $transformationInstance3
        ]);

        $this->assertEquals($transformed3, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance));


    }


    public function testAdditionalTransformationsAppliedInSequenceIfSuppliedToEvaluateAndTransformationSupported() {


        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation2 = MockObjectProvider::instance()->getMockInstance(Transformation::class);
        $transformation3 = MockObjectProvider::instance()->getMockInstance(Transformation::class);


        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance2 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance3 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);

        $transformationInstance1->returnValue("returnTransformation", $transformation1);
        $transformationInstance2->returnValue("returnTransformation", $transformation2);
        $transformationInstance3->returnValue("returnTransformation", $transformation3);


        $transformed1 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed1->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);
        $transformed2 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);
        $transformed2->returnValue("getSupportedTransformationClasses", [
            Transformation::class
        ]);
        $transformed3 = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);


        $dataSource->returnValue("applyTransformation", $transformed1, [
            $transformation1
        ]);

        $transformed1->returnValue("applyTransformation", $transformed2, [
            $transformation2
        ]);

        $transformed2->returnValue("applyTransformation", $transformed3, [
            $transformation3
        ]);


        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test");

        $this->assertEquals($transformed3, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance, [
            $transformationInstance1, $transformationInstance2, $transformationInstance3
        ]));


    }


    public function testDefaultDatasourceReturnedIfUnsupportedTransformationSuppliedAsPartOfDatasetOrAdditionalTransformations() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            TestTransformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(FilterTransformation::class);
        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        // Try as part of data set
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test", [
            $transformationInstance1
        ]);

        $expected = new DefaultDatasource($dataSource);
        $expected->applyTransformation($transformation1);

        $this->assertEquals($expected, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance));


        // Try as additional
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test");

        $this->assertEquals($expected, $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance, [
            $transformationInstance1
        ]));

    }


    public function testExceptionRaisedIfDefaultDatasourceCannotHandleUnsupportedTransformationSuppliedAsPartOfDatasetOrAdditionalTransformations() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        // Ensure that transformation classes supported by the datasource
        $dataSource->returnValue("getSupportedTransformationClasses", [
            TestTransformation::class
        ]);

        $transformation1 = MockObjectProvider::instance()->getMockInstance(AnotherTestTransformation::class);
        $transformationInstance1 = MockObjectProvider::instance()->getMockInstance(TransformationInstance::class);
        $transformationInstance1->returnValue("returnTransformation", $transformation1);

        $dataSourceInstance->returnValue("returnDataSource", $dataSource);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $dataSourceInstance, [
            "test"
        ]);

        // Try as part of data set
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test", [
            $transformationInstance1
        ]);

        try {
            $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance);
            $this->fail("Should have thrown here");
        } catch (UnsupportedDatasourceTransformationException $e) {
            $this->assertTrue(true);
        }

        // Try as additional
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test");

        try {
            $this->datasetService->getEvaluatedDataSourceForDataSetInstance($dataSetInstance, [
                $transformationInstance1
            ]);
            $this->fail("Should have thrown here");
        } catch (UnsupportedDatasourceTransformationException $e) {
            $this->assertTrue(true);
        }
    }


    public function testDataSourceAndTransformationsAreValidatedOnDataSetSave() {

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "badsource");

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasourceInstanceKey"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", [
            new TransformationInstance("badtrans")
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstances"][0]["type"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", [
            new TransformationInstance("Kinintel\ValueObjects\Transformation\TestTransformation", new TestTransformation())
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstances"][0]["config"]["property"]));
        }

    }


    public function testCanSaveRetrieveAndRemoveValidDataSetInstanceForLoggedInUserAndProject() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, 5, 1);

        // Check saved correctly in db
        $dataset = DatasetInstance::fetch($id);
        $this->assertEquals(1, $dataset->getAccountId());
        $this->assertEquals(5, $dataset->getProjectKey());


        $reSet = $this->datasetService->getDataSetInstance($id);
        $this->assertEquals("Test Dataset", $reSet->getTitle());
        $this->assertEquals("test-json", $reSet->getDatasourceInstanceKey());
        $transformationInstance = $reSet->getTransformationInstances()[0];
        $this->assertEquals(new TransformationInstance("filter",
            [
                "filters" => [["fieldName" => "property",
                    "value" => "foobar",
                    "filterType" => "eq"]],
                "logic" => "AND",
                "filterJunctions" => [],
                "sQLTransformationProcessorKey" => "filter"
            ]
        ), $transformationInstance);

        // Check unserialisation works for transformation instance
        $this->assertEquals(new FilterTransformation([
            new Filter("property", "foobar")
        ]), $transformationInstance->returnTransformation());


        // Remove the data set instance
        $this->datasetService->removeDataSetInstance($id);

        try {
            $this->datasetService->getDataSetInstance($id);
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

    }


    public function testCanSaveValidDatasetInstancesForProjectsAndTags() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ]);


        $tags = [new TagSummary("Project", "My Project", "project"),
            new TagSummary("Account2", "My Account", "account2")];

        $dataSetInstance->setTags($tags);


        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Project", "My Project", "project"), 2, "soapSuds")),
            new ObjectTag(new Tag(new TagSummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $tags, 2, "soapSuds"
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, "soapSuds", 2);

        $dataset = DatasetInstance::fetch($id);
        $this->assertEquals(2, $dataset->getAccountId());
        $this->assertEquals("soapSuds", $dataset->getProjectKey());

        $tags = $dataset->getTags();
        $this->assertEquals(2, sizeof($tags));

        $this->assertEquals("account2", $tags[0]->getTag()->getKey());
        $this->assertEquals("project", $tags[1]->getTag()->getKey());


    }


    public function testCanGetFilteredDatasetsForAccountsOptionallyFilteredByProjectAndTag() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountDataSet = new DatasetInstanceSummary("Account Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);

        $accountDataSet = new DatasetInstanceSummary("Second Account Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);


        $datasetProject = new Project("Dataset Project", 1, "datasetProject");
        $datasetProject->save();

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("Special", "Special Tag", "special"), 1, "datasetProject")),
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("Special", "", "special"),
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);

        $this->metaDataService->returnValue("getObjectTagsFromSummaries", [
            new ObjectTag(new Tag(new TagSummary("General", "General Tag", "general"), 1, "datasetProject"))
        ], [
            [
                new TagSummary("General", "", "general")
            ], 1, "datasetProject"
        ]);


        $projectDataSet = new DatasetInstanceSummary("Project Dataset", "test-json");
        $projectDataSet->setTags([
            new TagSummary("Special", "", "special"),
            new TagSummary("General", "", "general")
        ]);
        $this->datasetService->saveDataSetInstance($projectDataSet, "datasetProject", 1);

        $projectDataSet = new DatasetInstanceSummary("Second Project Dataset", "test-json");
        $projectDataSet->setTags([
            new TagSummary("General", "", "general")
        ]);
        $this->datasetService->saveDataSetInstance($projectDataSet, "datasetProject", 1);


        $filtered = $this->datasetService->filterDataSetInstances("", [], null, 0, 10, 1);
        $this->assertEquals(4, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Project Dataset", $filtered[1]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[2]);
        $this->assertEquals("Second Account Dataset", $filtered[2]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[3]);
        $this->assertEquals("Second Project Dataset", $filtered[3]->getTitle());


        // Filter on title
        $filtered = $this->datasetService->filterDataSetInstances("econd", [], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());


        // Filter on project key
        $filtered = $this->datasetService->filterDataSetInstances("", [], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        // Filter on tags
        $filtered = $this->datasetService->filterDataSetInstances("", ["general"], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        $filtered = $this->datasetService->filterDataSetInstances("", ["special"], "datasetProject", 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());


        // Offsets and limits
        $filtered = $this->datasetService->filterDataSetInstances("", ["general"], "datasetProject", 0, 1, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());


        $filtered = $this->datasetService->filterDataSetInstances("", ["general"], "datasetProject", 1, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Project Dataset", $filtered[0]->getTitle());


    }

}