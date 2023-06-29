<?php


namespace Kinintel\Test\Objects\Datasource;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Exception\ImportKeyAlreadyExistsException;
use Kinintel\Exception\ItemInUseException;
use Kinintel\Objects\Dataset\DatasetInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceInterceptor;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

include_once "autoloader.php";

class DatasourceInstanceInterceptorTest extends \PHPUnit\Framework\TestCase {


    /**
     * @var DatasourceInstanceInterceptor
     */
    private $interceptor;


    public function setUp(): void {
        $this->interceptor = Container::instance()->get(DatasourceInstanceInterceptor::class);

        Container::instance()->get(DatabaseConnection::class)->execute("DELETE FROM ki_dataset_instance WHERE datasource_instance_key = ?", "test-dep-ds");

        $testDs = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);

        Container::instance()->addInterfaceImplementation(Datasource::class, "test", get_class($testDs));

    }


    public function testIfDatasourceInstanceReferencedByDatasetItCannotBeDeleted() {

        // Check no references initially
        $datasourceInstance = new DatasourceInstance("test-dep-ds", "Test Instance", "test");
        $datasourceInstance->save();

        $this->interceptor->preDelete($datasourceInstance);

        // Save a dataset with dependent datasource
        $dataset = new DatasetInstance(new DatasetInstanceSummary("Test Dependent", "test-dep-ds"));
        $dataset->save();

        try {
            $this->interceptor->preDelete($datasourceInstance);
            $this->fail("Should have thrown here");
        } catch (ItemInUseException $e) {
            $this->assertTrue(true);
        }
    }

    public function testIfDatasourceInstanceReferencedInJoinTransformationByDatasetItCannotBeDeleted() {


        // Check no references initially
        $referencedDatasource = new DatasourceInstance("test-referenced", "Test Instance", "test");
        $referencedDatasource->save();


        // Save a dataset with dependent datasource
        $dataset = new DatasetInstance(new DatasetInstanceSummary("Test Dependent", "test-json", null, [
            new TransformationInstance("join", new JoinTransformation("test-referenced"))
        ]));
        $dataset->save();

        try {
            $this->interceptor->preDelete($referencedDatasource);
            $this->fail("Should have thrown here");
        } catch (ItemInUseException $e) {
            $this->assertTrue(true);
        }


    }


    public function testIfDatasourceInstanceRepresentsAnUpdatableDatasourceOnInstanceDeleteIsCalledPostDelete() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $dataSourceInstance->returnValue("returnDataSource", $dataSource);

        $this->interceptor->postDelete($dataSourceInstance);

        $this->assertTrue($dataSource->methodWasCalled("onInstanceDelete"));

    }


    public function testIfDatasourceInstanceRepresentsAnUpdatableDatasourceOnInstanceSaveIsCalledPostSave() {

        // Program expected return values
        $dataSourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $dataSource = MockObjectProvider::instance()->getMockInstance(UpdatableDatasource::class);
        $dataSourceInstance->returnValue("returnDataSource", $dataSource);

        $this->interceptor->postSave($dataSourceInstance);

        $this->assertTrue($dataSource->methodWasCalled("onInstanceSave"));

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testIfDatasourceExistsOnAccountWithSameImportKeyExceptionRaisedOnPreSave() {

        // Create one from scratch - should be fine
        $datasourceInstance = new DatasourceInstance("existing-import", "Existing Import", "test");
        $datasourceInstance->setAccountId(1);
        $datasourceInstance->setImportKey("existing-key");
        $datasourceInstance->save();


        // Now check for account duplicate
        $newInstance = new DatasourceInstance("new-import", "New Import", "test");
        $newInstance->setAccountId(1);
        $newInstance->setImportKey("existing-key");

        try {
            $this->interceptor->preSave($newInstance);
            $this->fail("Should have thrown here");
        } catch (ImportKeyAlreadyExistsException $e) {
        }

        // Now create a project one from scratch
        $datasourceInstance = new DatasourceInstance("existing-project", "Existing Import", "test");
        $datasourceInstance->setAccountId(1);
        $datasourceInstance->setProjectKey("project1");
        $datasourceInstance->setImportKey("project-key");
        $datasourceInstance->save();

        // Now create an overlapping one
        $newInstance = new DatasourceInstance("new-project", "New Project key", "test");
        $newInstance->setAccountId(1);
        $newInstance->setProjectKey("project1");
        $newInstance->setImportKey("project-key");

        try {
            $this->interceptor->preSave($newInstance);
            $this->fail("Should have thrown here");
        } catch (ImportKeyAlreadyExistsException $e) {
        }


    }


}