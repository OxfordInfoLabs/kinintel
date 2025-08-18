<?php

namespace Kinintel\Services\Dataset;

use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\Account\Project;
use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Objects\Security\ObjectScopeAccess;
use Kiniauth\Objects\Security\Role;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\MVC\ContentSource\StringContentSource;
use Kinikit\MVC\Response\Download;
use Kinikit\MVC\Response\SimpleResponse;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSearchResult;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Dataset\Exporter\DatasetExporter;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Test\Services\Dataset\Exporter\TestExporterConfig;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Application\DataSearchItem;
use Kinintel\ValueObjects\Dataset\DatasetTree;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\ProcessedTabularDataSet;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Filter\Filter;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterType;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TestTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;
use Kiniauth\Services\Security\ActiveRecordInterceptor;

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
        $this->datasetService = new DatasetService($this->datasourceService, $this->metaDataService, Container::instance()->get(ActiveRecordInterceptor::class));

    }


    public function testDataSourceDatasetAndTransformationsAreValidatedOnDataSetSave() {

        // Bad datasource
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "badsource");

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasourceInstanceKey"]));
        }


        // Bad dataset
        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", null, 500);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["datasetInstanceId"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("badtrans")
        ]);

        try {
            $this->datasetService->saveDataSetInstance($dataSetInstance, null, Account::LOGGED_IN_ACCOUNT);
            $this->fail("Should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->getValidationErrors()["transformationInstance"]["type"]));
        }


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
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

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
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
                "filters" => [["lhsExpression" => "property",
                    "rhsExpression" => "foobar",
                    "filterType" => "eq",
                    "inclusionCriteria" => "Always",
                    "inclusionData" => null]],
                "logic" => "AND",
                "filterJunctions" => [],
                "sQLTransformationProcessorKey" => "filter",
                "inclusionCriteria" => "Always",
                "inclusionData" => null
            ]
        ), $transformationInstance);


        // Check unserialisation works for transformation instance
        $this->assertEquals(new FilterTransformation([
            new Filter("property", "foobar")
        ]), $transformationInstance->returnTransformation());

        $this->assertEquals([
            new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)
        ], $reSet->getParameters());

        $this->assertEquals([
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ], $reSet->getParameterValues());

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

        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
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


    public function testCanGetFilteredDatasetsForAccountsOptionallyFilteredByProjectAndTagAndCategories() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $categories = [
            new CategorySummary("Account1", "An account wide category available to account 1", "account1")
        ];
        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 1", "Account 1", "account1"), 1)),
        ], [
            $categories, 1, null
        ]);


        $accountDataSet = new DatasetInstanceSummary("Account Dataset", "test-json", null, [], [], [], null, null, $categories);
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


        $filtered = $this->datasetService->filterDataSetInstances("", [], [], null, 0, 10, 1);
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
        $filtered = $this->datasetService->filterDataSetInstances("econd", [], [], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());


        // Filter on categories
        $filtered = $this->datasetService->filterDataSetInstances("", ["account1"], [], null, 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());

        // Filter on project key
        $filtered = $this->datasetService->filterDataSetInstances("", [], [], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        // Filter on tags
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Project Dataset", $filtered[1]->getTitle());

        $filtered = $this->datasetService->filterDataSetInstances("", [], ["special"], "datasetProject", 0, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());

        // Filter on special NONE tags
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["NONE"], null, 0, 10, 1);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Account Dataset", $filtered[0]->getTitle());
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[1]);
        $this->assertEquals("Second Account Dataset", $filtered[1]->getTitle());

        // Offsets and limits
        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 0, 1, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Project Dataset", $filtered[0]->getTitle());


        $filtered = $this->datasetService->filterDataSetInstances("", [], ["general"], "datasetProject", 1, 10, 1);
        $this->assertEquals(1, sizeof($filtered));
        $this->assertInstanceOf(DatasetInstanceSearchResult::class, $filtered[0]);
        $this->assertEquals("Second Project Dataset", $filtered[0]->getTitle());


    }


    public function testCanGetFilteredDatasetInstancesSharedWithAccount() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $accountDataSet = new DatasetInstanceSummary("Shared Dataset 1", "test-json");
        $datasetId = $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);
        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "SHAREDDS1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $datasetId))->save();
        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "SHAREDDS2", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $datasetId))->save();

        $accountDataSet = new DatasetInstanceSummary("Shared Dataset 2", "test-json");
        $datasetId2 = $this->datasetService->saveDataSetInstance($accountDataSet, null, 1);
        (new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "SHAREDDS3", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $datasetId2))->save();

        // Grab datasets
        $datasets = $this->datasetService->filterDatasetInstancesSharedWithAccount("", 0, 10, 2);
        $this->assertEquals([new DatasetInstanceSearchResult($datasetId, "Shared Dataset 1", null, null, [], null, null, "Sam Davis Design")], $datasets);

        $datasets = $this->datasetService->filterDatasetInstancesSharedWithAccount("", 0, 10, 3);
        $this->assertEquals([new DatasetInstanceSearchResult($datasetId, "Shared Dataset 1", null, null, [], null, null, "Sam Davis Design"),
            new DatasetInstanceSearchResult($datasetId2, "Shared Dataset 2", null, null, [], null, null, "Sam Davis Design")], $datasets);

        // Filtered on title
        $datasets = $this->datasetService->filterDatasetInstancesSharedWithAccount("2", 0, 10, 3);
        $this->assertEquals([
            new DatasetInstanceSearchResult($datasetId2, "Shared Dataset 2", null, null, [], null, null, "Sam Davis Design")], $datasets);

        // Offset
        $datasets = $this->datasetService->filterDatasetInstancesSharedWithAccount("", 1, 10, 3);
        $this->assertEquals([
            new DatasetInstanceSearchResult($datasetId2, "Shared Dataset 2", null, null, [], null, null, "Sam Davis Design")], $datasets);

        // Limit
        $datasets = $this->datasetService->filterDatasetInstancesSharedWithAccount("", 0, 1, 3);
        $this->assertEquals([
            new DatasetInstanceSearchResult($datasetId, "Shared Dataset 1", null, null, [], null, null, "Sam Davis Design")], $datasets);

    }


    public function testCanGetDatasetInstanceByManagementKey() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSetInstanceSummary1 = new DatasetInstanceSummary("Test dataset 1", "test-json");
        $dataSetInstanceSummary1->setManagementKey("bingo");
        $id = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary1, null, 1);
        $dataSetInstanceSummary1 = $this->datasetService->getDataSetInstance($id);
        $fullDatasetInstance1 = $this->datasetService->getFullDataSetInstance($id);

        $dataSetInstanceSummary2 = new DatasetInstanceSummary("Test dataset 2", "test-json");
        $dataSetInstanceSummary2->setManagementKey("bongo");
        $id = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary2, null, 1);
        $dataSetInstanceSummary2 = $this->datasetService->getDataSetInstance($id);
        $fullDatasetInstance2 = $this->datasetService->getFullDataSetInstance($id);


        $dataSetInstanceSummary3 = new DatasetInstanceSummary("Test dataset 3", "test-json");
        $dataSetInstanceSummary3->setManagementKey("bingo");
        $id = $this->datasetService->saveDataSetInstance($dataSetInstanceSummary3, null, 2);
        $dataSetInstanceSummary3 = $this->datasetService->getDataSetInstance($id);
        $fullDatasetInstance3 = $this->datasetService->getFullDataSetInstance($id);


        $this->assertEquals($dataSetInstanceSummary1, $this->datasetService->getDatasetInstanceByManagementKey("bingo", 1));
        $this->assertEquals($dataSetInstanceSummary2, $this->datasetService->getDatasetInstanceByManagementKey("bongo", 1));
        $this->assertEquals($dataSetInstanceSummary3, $this->datasetService->getDatasetInstanceByManagementKey("bingo", 2));


        $this->assertEquals($fullDatasetInstance1, $this->datasetService->getFullDataSetInstanceByManagementKey("bingo", 1));
        $this->assertEquals($fullDatasetInstance2, $this->datasetService->getFullDataSetInstanceByManagementKey("bongo", 1));
        $this->assertEquals($fullDatasetInstance3, $this->datasetService->getFullDataSetInstanceByManagementKey("bingo", 2));


    }


    public function testCanGetFilteredSharedDatasetsWithAccountIdOfNull() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");


        $sharedDataset = new DatasetInstanceSummary("Shared First Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($sharedDataset, null, null);

        $sharedDataset = new DatasetInstanceSummary("Shared Second Dataset", "test-json");
        $this->datasetService->saveDataSetInstance($sharedDataset, null, null);

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $filtered = $this->datasetService->filterDataSetInstances("", [], [], [], 0, 10, null);
        $this->assertEquals(2, sizeof($filtered));
        $this->assertEquals("Shared First Dataset", $filtered[0]->getTitle());
        $this->assertEquals("Shared Second Dataset", $filtered[1]->getTitle());


    }


    public function testCanGetEvaluatedParametersContainingBothDatasourceAndDatasetParams() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $datasetSummary = new DatasetInstanceSummary("My Test", "test-json", null, [], [
            new Parameter("datasetParam1", "Dataset Param 1"), new Parameter("datasetParam2", "Dataset Param 2")
        ]);

        $this->datasourceService->returnValue("getEvaluatedParameters", [
            new Parameter("datasourceParam1", "Datasource Param 1"), new Parameter("datasourceParam2", "Datasource Param 2")
        ], [
            "test-json"
        ]);


        $parameters = $this->datasetService->getEvaluatedParameters($datasetSummary);

        $this->assertEquals([
            new Parameter("datasourceParam1", "Datasource Param 1"), new Parameter("datasourceParam2", "Datasource Param 2"),
            new Parameter("datasetParam1", "Dataset Param 1"), new Parameter("datasetParam2", "Dataset Param 2")
        ], $parameters);


    }


    public function testCanEvaluateDatasourceBasedDatasetUsingSuppliedParamsAndAdditionalTransformations() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $this->datasetService->getEvaluatedDataSetForDataSetInstance($dataSetInstance, ["customParam" => "Hello"], [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "bingo")
            ]))
        ], 10, 30);


        // Check data is merged together and evaluated on data source
        $this->assertTrue($this->datasourceService->methodWasCalled("getEvaluatedDataSourceByInstanceKey", [
            "test-json",
            ["param1" => "Test",
                "param2" => 44,
                "param3" => true, "customParam" => "Hello"], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "foobar")
                ])),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "bingo")
                ]))
            ], 10, 30
        ]));

    }


    public function testCanEvaluateDatasetBasedDatasetUsingSuppliedParametersAndAdditionalTransformations() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);
        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstance, null, null);


        $extendedDataSetInstance = new DatasetInstanceSummary("Extended Dataset", null, $instanceId, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")
            ]))
        ], [
            new Parameter("extendedParam", "Extended Parameter")
        ], [
            "extendedParam" => 33
        ]);


        $this->datasetService->getEvaluatedDataSetForDataSetInstance($extendedDataSetInstance, ["customParam" => "Hello"], [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "bingo")
            ]))
        ], 10, 30);


        $converter = Container::instance()->get(ObjectToJSONConverter::class);
        $unconverter = Container::instance()->get(JSONToObjectConverter::class);

        // Check data is merged together and evaluated on data source
        $this->assertTrue($this->datasourceService->methodWasCalled("getEvaluatedDataSourceByInstanceKey", [
            "test-json",
            ["param1" => "Test",
                "param2" => 44,
                "param3" => true, "extendedParam" => 33, "customParam" => "Hello"],
            [
                new TransformationInstance("filter", $unconverter->convert($converter->convert(new FilterTransformation([
                    new Filter("property", "foobar")
                ])))),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "pickle")
                ])),
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "bingo")
                ]))
            ], 10, 30
        ]));

    }


    public function testSummaryReturnedReferencingOriginalDatasetIdIfAccountIdNullAndLoggedInAsRegularUser() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], 25), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], 25), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [], [], [], "Test Summary", "Test Description", [], 25), 1, null);
        $summary = $dataSet->returnSummary();
        $this->assertEquals("test", $summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getId());


        $dataSet = new DatasetInstance(new DatasetInstanceSummary("Hello", "test", null, [new TransformationInstance("test", ["bingo" => "hello"])], [new Parameter("customParam", "Custom Parameter")], ["customParam" => "Bob"], "Test Summary", "Test Description", [], 25), null, null);
        $summary = $dataSet->returnSummary();
        $this->assertNull($summary->getDatasourceInstanceKey());
        $this->assertEquals(25, $summary->getDatasetInstanceId());
        $this->assertEquals(null, $summary->getId());
        $this->assertEquals([], $summary->getTransformationInstances());
        $this->assertEquals([], $summary->getParameters());
        $this->assertEquals(["customParam" => null], $summary->getParameterValues());

    }


    public function testCanGetExtendedDatasetInstanceBasedUponOriginalDatasetInstance() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("Original Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $id = $this->datasetService->saveDataSetInstance($dataSetInstance, 5, 1);

        $extended = $this->datasetService->getExtendedDatasetInstance($id);
        $this->assertInstanceOf(DatasetInstanceSummary::class, $extended);
        $this->assertNull($extended->getId());
        $this->assertEquals("Original Dataset Extended", $extended->getTitle());
        $this->assertNull($extended->getDatasourceInstanceKey());
        $this->assertEquals($id, $extended->getDatasetInstanceId());
        $this->assertEquals([], $extended->getParameters());
        $this->assertEquals([], $extended->getTransformationInstances());
        $this->assertEquals([], $extended->getParameterValues());

    }


    public function testCanUpdateMetaDataForADatasetInstance() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $dataSetInstance = new DatasetInstanceSummary("New Dataset For Snapshot", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ])),
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)], [
            "param1" => "Test",
            "param2" => 44,
            "param3" => true
        ]);

        $instanceId = $this->datasetService->saveDataSetInstance($dataSetInstance, null, 1);


        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 1", "Account 1", "account1"), 1)),
        ], [
            [new CategorySummary("Account 1", "Account 1", "account1")], 1, null
        ]);


        $this->datasetService->updateDataSetMetaData(new DatasetInstanceSearchResult($instanceId, "Updated Dataset", "New Summary", "New description", [
            new CategorySummary("Account 1", "Account 1", "account1")
        ]));


        $dashboard = $this->datasetService->getDataSetInstance($instanceId);
        $this->assertEquals("Updated Dataset", $dashboard->getTitle());
        $this->assertEquals("New Summary", $dashboard->getSummary());
        $this->assertEquals("New description", $dashboard->getDescription());
        $this->assertEquals([new CategorySummary("Account1", "An account wide category available to account 1", "account1")], $dashboard->getCategories());


    }


    public function testCanGetInUseDashboardCategories() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $accountCategories = [
            new CategorySummary("Account2", "An account wide category available to account 2", "account2")
        ];

        $projectCategories = [
            new CategorySummary("Project", "A project level category available to just one project", "project"),
        ];


        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Account 2", "Account 2", "account2"), 2)),
        ], [
            $accountCategories, 2, null
        ]);

        $this->metaDataService->returnValue("getObjectCategoriesFromSummaries", [
            new ObjectCategory(new Category(new CategorySummary("Project", "Project", "project"), 2, "soapSuds")),
        ], [
            $projectCategories, 2, "soapSuds"
        ]);


        $accountDatasetInstance = new DatasetInstanceSummary("Account Dashboard", "test-json", null, [], [], [], "Account Dashboard Summary", "Account Dashboard Description", $accountCategories);
        $this->datasetService->saveDataSetInstance($accountDatasetInstance, null, 2);

        $projectDatasetInstance = new DatasetInstanceSummary("Project Dashboard", "test-json", null, [], [], [], "Project Dashboard Summary", "Project Dashboard Description", $projectCategories);
        $this->datasetService->saveDataSetInstance($projectDatasetInstance, "soapSuds", 2);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", array_merge($accountCategories, $projectCategories), [
            ["", "account2", "project"], null, 2
        ]);

        $this->metaDataService->returnValue("getMultipleCategoriesByKey", $projectCategories, [
            ["", "project"], "soapSuds", 2
        ]);


        $this->assertEquals(array_merge($accountCategories, $projectCategories), $this->datasetService->getInUseDatasetInstanceCategories([], null, 2));
        $this->assertEquals($projectCategories, $this->datasetService->getInUseDatasetInstanceCategories([], "soapSuds", 2));

    }


    public function testCanGetDatasetByTitleAndOptionallyByAccountAndProject() {

        // Log in as a person with projects and tags
        AuthenticationHelper::login("admin@kinicart.com", "password");

        $topLevel = new DatasetInstanceSummary("Top Level Dataset", "test-json");
        $topLevelId = $this->datasetService->saveDataSetInstance($topLevel, null, null);

        $account1 = new DatasetInstanceSummary("Account Dataset 1", "test-json");
        $account1Id = $this->datasetService->saveDataSetInstance($account1, null, 1);

        $account2 = new DatasetInstanceSummary("Account Dataset 2", "test-json");
        $account2Id = $this->datasetService->saveDataSetInstance($account2, null, 2);

        $project1 = new DatasetInstanceSummary("Project Dataset 1", "test-json");
        $project1Id = $this->datasetService->saveDataSetInstance($project1, "soapSuds", 2);

        $project2 = new DatasetInstanceSummary("Project Dataset 2", "test-json");
        $project2Id = $this->datasetService->saveDataSetInstance($project2, "wiperBlades", 2);

        $this->assertEquals($this->datasetService->getDataSetInstance($topLevelId), $this->datasetService->getDataSetInstanceByTitle("Top Level Dataset", null, null));
        $this->assertEquals($this->datasetService->getDataSetInstance($account1Id), $this->datasetService->getDataSetInstanceByTitle("Account Dataset 1", null, 1));
        $this->assertEquals($this->datasetService->getDataSetInstance($account2Id), $this->datasetService->getDataSetInstanceByTitle("Account Dataset 2", null, 2));
        $this->assertEquals($this->datasetService->getDataSetInstance($project1Id), $this->datasetService->getDataSetInstanceByTitle("Project Dataset 1", "soapSuds", 2));
        $this->assertEquals($this->datasetService->getDataSetInstance($project2Id), $this->datasetService->getDataSetInstanceByTitle("Project Dataset 2", "wiperBlades", 2));


    }


    public function testCanExportDatasetUsingSuppliedExporterKeyAndValidConfiguration() {

        AuthenticationHelper::login("admin@kinicart.com", "password");


        $dataSetInstance = new DatasetInstanceSummary("Test Dataset", "test-json", null, [
            new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "foobar")
            ]))
        ], [new Parameter("customParam", "Custom Parameter"),
            new Parameter("customOtherParam", "Custom Other Param", Parameter::TYPE_NUMERIC)]);
        $this->datasetService->saveDataSetInstance($dataSetInstance, null, null);


        $dataset = new ArrayTabularDataset([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Bob", "age" => 22],
            ["name" => "Mary", "age" => 32],
            ["name" => "Jane", "age" => 45]
        ]);


        $this->datasourceService->returnValue("getEvaluatedDataSourceByInstanceKey",
            $dataset,
            [
                "test-json",
                ["param1" => "Test",
                    "param2" => 44,
                    "param3" => true],
                [
                    new TransformationInstance("filter", new FilterTransformation([
                        new Filter("property", "foobar")
                    ])),
                    new TransformationInstance("filter", new FilterTransformation([
                        new Filter("property", "pickle")
                    ]))
                ], 10, 30
            ]);

        $exportConfig = new TestExporterConfig("Test", 11);
        $mockExporter = MockObjectProvider::instance()->getMockInstance(DatasetExporter::class);
        Container::instance()->addInterfaceImplementation(DatasetExporter::class, "test", get_class($mockExporter));
        Container::instance()->set(get_class($mockExporter), $mockExporter);

        $mockExporter->returnValue("getConfigClass", TestExporterConfig::class);
        $mockExporter->returnValue("getDownloadFileExtension", "test", [$exportConfig]);
        $mockExporter->returnValue("validateConfig", $exportConfig, [$exportConfig]);

        $mockExporter->returnValue("exportDataset", new StringContentSource("HELLO WORLD"), [$dataset, $exportConfig]);


        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [
                new TransformationInstance("filter", new FilterTransformation([
                    new Filter("property", "pickle")
                ]))
            ], 10, 30
        );

        $this->assertEquals(new Download(new StringContentSource("HELLO WORLD"), "test_dataset-" . date("U") . ".test", 200), $response);


        // Check none download one.
        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, false);


        $this->assertEquals(new SimpleResponse(new StringContentSource("HELLO WORLD"), 200), $response);

        // Check different caching time
        $processedDataset = new ProcessedTabularDataSet([
            new Field("name"),
            new Field("age")
        ], [
            ["name" => "Bob", "age" => 22],
            ["name" => "Mary", "age" => 32],
            ["name" => "Jane", "age" => 45]
        ]);
        $mockExporter->returnValue("exportDataset", new StringContentSource("HELLO WORLD"), [$processedDataset, $exportConfig]);

        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, true, 200);


        $expected = new Download(new StringContentSource("HELLO WORLD"), "test_dataset-" . date("U") . ".test", 200);
        $this->assertEquals($expected, $response);

        $response = $this->datasetService->exportDatasetInstance($dataSetInstance,
            "test",
            $exportConfig,
            [
                "param1" => "Test",
                "param2" => 44,
                "param3" => true
            ], [new TransformationInstance("filter", new FilterTransformation([
                new Filter("property", "pickle")]))], 10, 30, false, 120);


        $expected = new SimpleResponse(new StringContentSource("HELLO WORLD"), 200);

        $this->assertEquals($expected, $response);


    }


    public function testIfInvalidExportConfigurationSuppliedExceptionRaised() {

        $mockExporter = MockObjectProvider::instance()->getMockInstance(DatasetExporter::class);
        Container::instance()->addInterfaceImplementation(DatasetExporter::class, "test", get_class($mockExporter));
        Container::instance()->set(get_class($mockExporter), $mockExporter);

        $mockExporter->returnValue("getConfigClass", TestExporterConfig::class);


        $mockExporter->throwException("validateConfig", new ValidationException([]), [new TestExporterConfig()]);

        try {
            $this->datasetService->exportDatasetInstance(null, "test", new TestExporterConfig());
            $this->fail("should have thrown here");
        } catch (ValidationException $e) {
            $this->assertTrue(true);
        }


    }


    public function testCanCheckWhetherManagementKeyIsAvailableForDatasetInstance() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        // Create one from scratch - should be fine
        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Test", "test-json"));
        $datasetInstance->setAccountId(1);
        $datasetInstance->setManagementKey("existing-key");
        $datasetInstance->save();


        // Now check for account duplicate
        $newInstance = new DatasetInstance(new DatasetInstanceSummary("Test", "test-json"));
        $newInstance->setAccountId(1);
        $this->assertFalse($this->datasetService->managementKeyAvailableForDatasetInstance($newInstance, "existing-key"));

        // Now create a project one from scratch
        $datasourceInstance = new DatasetInstance(new DatasetInstanceSummary("Test", "test-json"));
        $datasourceInstance->setAccountId(1);
        $datasourceInstance->setProjectKey("project1");
        $datasourceInstance->setManagementKey("project-key");
        $datasourceInstance->save();

        // Now create an overlapping one
        $newInstance = new DatasetInstance(new DatasetInstanceSummary("Test", "test-json"));
        $newInstance->setAccountId(1);
        $newInstance->setProjectKey("project1");
        $this->assertFalse($this->datasetService->managementKeyAvailableForDatasetInstance($newInstance, "project-key"));


    }


    public function testInterceptorCorrectlyInterceptsCrossAccountAccessAndWhitelistsWhereAppropriate() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $datasetService = Container::instance()->get(DatasetService::class);

        // Self owned data set
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("Test1", "test"));
        $dataset1->setAccountId(1);
        $dataset1->save();

        // Non shared data set other account
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Test2", "test"));
        $dataset2->setAccountId(2);
        $dataset2->save();

        // Directly shared dataset other account
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Test3", "test"));
        $dataset3->setAccountId(3);
        $dataset3->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset3->getId());
        $objectScopeAccess->save();

        // Implicit access to dataset in same account as shared dataset
        $dataset4 = new DatasetInstance(new DatasetInstanceSummary("Test4", null, $dataset2->getId()));
        $dataset4->setAccountId(2);
        $dataset4->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST2", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset4->getId());
        $objectScopeAccess->save();

        // Shared with other account for transitive testing
        $dataset5 = new DatasetInstance(new DatasetInstanceSummary("Test5", "test"));
        $dataset5->setAccountId(4);
        $dataset5->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 3, "TEST2", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset5->getId());
        $objectScopeAccess->save();

        // Shared with main account for transitive testing
        $dataset6 = new DatasetInstance(new DatasetInstanceSummary("Test5", null, $dataset5->getId()));
        $dataset6->setAccountId(3);
        $dataset6->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST2", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset6->getId());
        $objectScopeAccess->save();


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Check can return dataset 1
        $result = $datasetService->getEvaluatedDataSetForDataSetInstanceById($dataset1->getId());
        $this->assertEquals(10, sizeof($result->getAllData()));


        // Check can't return dataset 2
        try {
            $datasetService->getEvaluatedDataSetForDataSetInstanceById($dataset2->getId());
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
        }


        // Check can return dataset 3 as shared
        $result = $datasetService->getEvaluatedDataSetForDataSetInstanceById($dataset3->getId());
        $this->assertEquals(10, sizeof($result->getAllData()));


        // Check can return dataset 4 as shared
        $result = $datasetService->getEvaluatedDataSetForDataSetInstanceById($dataset4->getId());
        $this->assertEquals(10, sizeof($result->getAllData()));


        // Transitive example should fail
        try {
            $datasetService->getEvaluatedDataSetForDataSetInstanceById($dataset6->getId());
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
        }
    }


    public function testInterceptorCorrectlyInterceptsJoinedDatasetsForDirectlySharedDatasetsAndWhitelistsAccess() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        /**
         * @var DatasetService $datasetService
         */
        $datasetService = Container::instance()->get(DatasetService::class);

        // Self owned data set
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("My Dataset", "test"));
        $dataset1->setAccountId(1);
        $dataset1->save();

        // Non shared data set other account
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Shared Dataset Parent", "test"));
        $dataset2->setAccountId(2);
        $dataset2->save();

        // Directly shared dataset other account
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Shared Dataset", null, $dataset2->getId()));
        $dataset3->setAccountId(2);
        $dataset3->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset3->getId());
        $objectScopeAccess->save();

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $queryInstance = new DatasetInstance(new DatasetInstanceSummary("Test Query", null, $dataset1->getId(), [
            new TransformationInstance("join", new JoinTransformation(null, $dataset3->getId(), [],
                new FilterJunction([new Filter("[[value]]", "[[value]]", FilterType::eq)])
            ))
        ]));

        $evaluated = $datasetService->getEvaluatedDataSetForDataSetInstance($queryInstance);

        // Check data as expected
        $this->assertEquals(2, sizeof($evaluated->getColumns()));
        $this->assertEquals(10, sizeof($evaluated->getAllData()));

        $this->assertEquals(["value" => "Value 1", "value_2" => "Value 1"], $evaluated->getAllData()[0]);

    }


    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionRaisedIfAttemptToJoinDataWithTransitiveDependency() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        /**
         * @var DatasetService $datasetService
         */
        $datasetService = Container::instance()->get(DatasetService::class);

        // Self owned data set
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("My Dataset", "test"));
        $dataset1->setAccountId(1);
        $dataset1->save();

        // Data set shared with account 2
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Transitive Shared Dataset", "test"));
        $dataset2->setAccountId(3);
        $dataset2->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 2, "TEST1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset2->getId());
        $objectScopeAccess->save();


        // Directly shared dataset other account
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Shared Dataset", null, $dataset2->getId()));
        $dataset3->setAccountId(2);
        $dataset3->save();

        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset3->getId());
        $objectScopeAccess->save();

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $queryInstance = new DatasetInstance(new DatasetInstanceSummary("Test Query", null, $dataset1->getId(), [
            new TransformationInstance("join", new JoinTransformation(null, $dataset3->getId(), [],
                new FilterJunction([new Filter("[[value]]", "[[value]]", FilterType::eq)])
            ))
        ]));

        try {
            $datasetService->getEvaluatedDataSetForDataSetInstance($queryInstance);
            $this->fail("Should have thrown here");
        } catch (ItemNotFoundException $e) {
        }

    }

    public function testCanGetDatasetTreeForSimpleTerminatingDatasetInstance() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            new DatasourceInstance("test", "Test", "test"), ["test"]);


        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Simple Dataset", "test", null, [], [], [], "Simple Dataset Summary"), 1);
        $datasetInstance->save();
        $datasetInstance = $this->datasetService->getFullDataSetInstance($datasetInstance->getId());

        $treeById = $this->datasetService->getDatasetTreeByInstanceId($datasetInstance->getId());
        $treeByInstance = $this->datasetService->getDatasetTree($datasetInstance);
        $this->assertEquals($treeById, $treeByInstance);
        $this->assertEquals(new DatasetTree(new DataSearchItem("datasetinstance", $datasetInstance->getId(), "Simple Dataset", "Simple Dataset Summary", "Sam Davis Design", null)),
            $treeByInstance);
    }


    public function testCanGetDatasetTreeWithParentTreesIntact() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            new DatasourceInstance("test", "Test", "test"), ["test"]);

        $grandparentDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Grandparent Dataset", "test", null, [], [], [], "Grandparent Dataset Summary"), 1);
        $grandparentDatasetInstance->save();
        $grandparentItem = new DataSearchItem("datasetinstance", $grandparentDatasetInstance->getId(), "Grandparent Dataset", "Grandparent Dataset Summary", "Sam Davis Design");

        $parentDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Parent Dataset", null, $grandparentDatasetInstance->getId(), [], [], [], "Parent Dataset Summary"), 1);
        $parentDatasetInstance->save();
        $parentItem = new DataSearchItem("datasetinstance", $parentDatasetInstance->getId(), "Parent Dataset", "Parent Dataset Summary", "Sam Davis Design");

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Child Dataset", null, $parentDatasetInstance->getId(), [], [], [], "Child Dataset Summary"), 1);
        $datasetInstance->save();
        $item = new DataSearchItem("datasetinstance", $datasetInstance->getId(), "Child Dataset", "Child Dataset Summary", "Sam Davis Design");

        $tree = $this->datasetService->getDatasetTreeByInstanceId($datasetInstance->getId());
        $this->assertEquals(new DatasetTree($item, new DatasetTree($parentItem, new DatasetTree($grandparentItem))), $tree);


    }

    public function testCanGetDatasetTreeWithJoinedTreesIntact() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->datasourceService->returnValue("getDataSourceInstanceByKey",
            new DatasourceInstance("test", "Test", "test"), ["test"]);


        $grandparentDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Grandparent Dataset", "test", null, [], [], [], "Grandparent Dataset Summary"), 1);
        $grandparentDatasetInstance->save();
        $grandparentItem = new DataSearchItem("datasetinstance", $grandparentDatasetInstance->getId(), "Grandparent Dataset", "Grandparent Dataset Summary", "Sam Davis Design");

        $parentDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Parent Dataset", null, $grandparentDatasetInstance->getId(), [], [], [], "Parent Dataset Summary"), 1);
        $parentDatasetInstance->save();
        $parentItem = new DataSearchItem("datasetinstance", $parentDatasetInstance->getId(), "Parent Dataset", "Parent Dataset Summary", "Sam Davis Design");

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Child Dataset", "test", null, [
            new TransformationInstance("join", new JoinTransformation(null, $parentDatasetInstance->getId()))
        ], [], [], "Child Dataset Summary"), 1);
        $datasetInstance->save();
        $item = new DataSearchItem("datasetinstance", $datasetInstance->getId(), "Child Dataset", "Child Dataset Summary", "Sam Davis Design");

        $tree = $this->datasetService->getDatasetTreeByInstanceId($datasetInstance->getId());
        $this->assertEquals(new DatasetTree($item, null, [new DatasetTree($parentItem, new DatasetTree($grandparentItem))]), $tree);

    }

    public function testAccountOwnedDatasourcesAreIncludedInTreeWhenInHierarchy() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $datasetService = Container::instance()->get(DatasetService::class);


        $config = new SQLDatabaseDatasourceConfig("table", "test_custom", "", [new Field("value", null, null, Field::TYPE_STRING, true)]);
        $accountDatasource = new DatasourceInstance("test-ds", "Test Datasource", "custom", $config, "sql");
        $accountDatasource->setAccountId(1);
        $accountDatasource->save();

        $accountDSItem = new DataSearchItem("custom", "test-ds", "Test Datasource", "", "Sam Davis Design");

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Dataset",
            $accountDatasource->getKey(), null, [
                new TransformationInstance("join", new JoinTransformation($accountDatasource->getKey()))
            ]), 1);
        $datasetInstance->save();
        $datasetItem = new DataSearchItem("datasetinstance", $datasetInstance->getId(), "Dataset", null, "Sam Davis Design");

        $tree = $datasetService->getDatasetTreeByInstanceId($datasetInstance->getId());
        $this->assertEquals(new DatasetTree($datasetItem,
            new DatasetTree($accountDSItem),
            [new DatasetTree($accountDSItem)]), $tree);


    }

    public function testSnapshotsAreTraversedCorrectlyWithBuildingDatasetsIncludedInTree() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $datasetService = Container::instance()->get(DatasetService::class);

        $derivedDatasetInstance = new DatasetInstance(new DatasetInstanceSummary("Derived Dataset", "test"), 1);
        $derivedDatasetInstance->save();
        $derivedDatasetItem = new DataSearchItem("datasetinstance", $derivedDatasetInstance->getId(), "Derived Dataset", null, "Sam Davis Design");

        $snapshot = new DataProcessorInstance("test-snap", "Test Snapshot", "tabulardatasetsnapshot", [], null, null, "DatasetInstance", $derivedDatasetInstance->getId());
        $snapshot->setAccountId(1);
        $snapshot->save();

        $config = new SQLDatabaseDatasourceConfig("table", "test_snap_latest", "", [new Field("value", null, null, Field::TYPE_STRING, true)]);
        $snapshotDatasource = new DatasourceInstance("test-snap_latest", "Latest Snapshot", "snapshot", $config, "sql");
        $snapshotDatasource->setAccountId(1);
        $snapshotDatasource->save();

        $snapshotItem = new DataSearchItem("snapshot", "test-snap", "Test Snapshot", "", "Sam Davis Design");

        $datasetInstance = new DatasetInstance(new DatasetInstanceSummary("Dataset",
            $snapshotDatasource->getKey(), null, [
                new TransformationInstance("join", new JoinTransformation($snapshotDatasource->getKey()))
            ]), 1);
        $datasetInstance->save();
        $datasetItem = new DataSearchItem("datasetinstance", $datasetInstance->getId(), "Dataset", null, "Sam Davis Design");

        $tree = $datasetService->getDatasetTreeByInstanceId($datasetInstance->getId());
        $this->assertEquals(new DatasetTree($datasetItem,
            new DatasetTree($snapshotItem, new DatasetTree($derivedDatasetItem)),
            [new DatasetTree($snapshotItem, new DatasetTree($derivedDatasetItem))]), $tree);

    }

    public function testSharedDatasetsIncludedInTreeHierarchy() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        /**
         * @var DatasetService $datasetService
         */
        $datasetService = Container::instance()->get(DatasetService::class);


        // Non shared data set other account
        $dataset2 = new DatasetInstance(new DatasetInstanceSummary("Shared Dataset Parent", "test"));
        $dataset2->setAccountId(2);
        $dataset2->save();
        $dataset2Item = new DataSearchItem("datasetinstance", $dataset2->getId(), "Shared Dataset Parent", "", "Peter Jones Car Washing");


        // Directly shared dataset other account
        $dataset3 = new DatasetInstance(new DatasetInstanceSummary("Shared Dataset", null, $dataset2->getId()));
        $dataset3->setAccountId(2);
        $dataset3->save();
        $dataset3Item = new DataSearchItem("datasetinstance", $dataset3->getId(), "Shared Dataset", "", "Peter Jones Car Washing");


        $objectScopeAccess = new ObjectScopeAccess(Role::SCOPE_ACCOUNT, 1, "TEST1", false, false, null, str_replace("\\", "\\\\", DatasetInstance::class), $dataset3->getId());
        $objectScopeAccess->save();


        // Self owned data set
        $dataset1 = new DatasetInstance(new DatasetInstanceSummary("My Dataset", null, $dataset3->getId(),
            [
                new TransformationInstance("join", new JoinTransformation(null, $dataset3->getId()))
            ]));
        $dataset1->setAccountId(1);
        $dataset1->save();
        $dataset1Item = new DataSearchItem("datasetinstance", $dataset1->getId(), "My Dataset", "", "Sam Davis Design");


        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");


        // Grab the tree for dataset1
        $tree = $datasetService->getDatasetTreeByInstanceId($dataset1->getId());

        $this->assertEquals(new DatasetTree($dataset1Item, new DatasetTree($dataset3Item, new DatasetTree($dataset2Item)), [
            new DatasetTree($dataset3Item, new DatasetTree($dataset2Item))
        ]), $tree);

    }


}