<?php


namespace Kinintel\Services\DataProcessor;


use Kiniauth\Objects\Account\PublicAccountSummary;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;

include_once "autoloader.php";

class DataProcessorDAOTest extends TestBase {

    /**
     * @var DataProcessorDAO
     */
    private $dao;

    // Set up
    public function setUp(): void {
        $this->dao = Container::instance()->get(DataProcessorDAO::class);
    }


    public function testObjectNotFoundExceptionRaisedIfNonExistentKeySupplied() {

        try {
            $this->dao->getDataProcessorInstanceByKey("bad-one");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }

    }

    public function testCanGetDataProcessorInstanceFromFileSystemByKey() {

        $filesystemInstance = $this->dao->getDataProcessorInstanceByKey("test-import");

        $this->assertEquals(new DataProcessorInstance("test-import", "Test import data processor", "tabulardatasourceimport"),
            $filesystemInstance);

    }


    public function testCanStoreRetrieveAndRemoveDatabaseStoredDataProcessorInstances() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newInstance = new DataProcessorInstance("new-test", "New Test One", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "test-datasource",
            "targetDatasources" => [[
                "key" => "test-target"
            ]]
        ]);

        // Save the processor instance
        $this->dao->saveProcessorInstance($newInstance);

        // Grab it back again and compare
        $reInstance = $this->dao->getDataProcessorInstanceByKey("new-test");
        $this->assertEquals($newInstance, $reInstance);

        // Remove it
        $this->dao->removeProcessorInstance("new-test");

        try {
            $this->dao->getDataProcessorInstanceByKey("new-test");
            $this->fail("Should have thrown here");
        } catch (ObjectNotFoundException $e) {
            $this->assertTrue(true);
        }


    }

    public function testCanGetFilteredDataProcessorInstances() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $instance1 = new DataProcessorInstance("new-test", "New Big One", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "test-datasource",
            "targetDatasources" => [[
                "key" => "test-target"
            ]]
        ], DataProcessorInstance::TRIGGER_ADHOC, null, null, null, null, 2,  PublicAccountSummary::fetch(2));
        $instance1->save();

        $instance2 = new DataProcessorInstance("another-test", "Another Big One", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "test-datasource",
            "targetDatasources" => [[
                "key" => "test-target"
            ]]
        ], DataProcessorInstance::TRIGGER_ADHOC, null, null, null, "BINGO", 1,  PublicAccountSummary::fetch(1));
        $instance2->save();

        $instance3 = new DataProcessorInstance("specific-test", "Specific One", "tabulardatasourceimport", [
            "sourceDatasourceKey" => "test-datasource",
            "targetDatasources" => [[
                "key" => "test-target"
            ]]
        ], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 22, null, 1,
        PublicAccountSummary::fetch(1));
        $instance3->save();

        $instance4 = new DataProcessorInstance("another-specific-test", "Specific One Again", "sqlquery", [
            "query" => "SELECT * from test",
            "authenticationCredentialsKey" => "test"
        ], DataProcessorInstance::TRIGGER_ADHOC, null, "DatasetInstance", 25, null, 1,  PublicAccountSummary::fetch(1));
        $instance4->save();

        // Check search ones
        $this->assertEquals([$instance2, $instance1 ], $this->dao->filterDataProcessorInstances(["search" => "big"], null, 0, 10, null));
        $this->assertEquals([$instance3, $instance4], $this->dao->filterDataProcessorInstances(["search" => "specific"], null, 0, 10, null));

        // Check type restrictions
        $this->assertEquals([ $instance4], $this->dao->filterDataProcessorInstances(["type" => "sqlquery"], null, 0, 10, null));

        // Check related object filters
        $this->assertEquals([ $instance3], $this->dao->filterDataProcessorInstances(["relatedObjectType" => "DatasetInstance", "relatedObjectKey" => 22], null, 0, 10, null));


        // Check limits and offsets
        $this->assertEquals([$instance2 ], $this->dao->filterDataProcessorInstances(["search" => "big"], null, 0, 1, null));
        $this->assertEquals([$instance1 ], $this->dao->filterDataProcessorInstances(["search" => "big"], null, 1, 10, null));

        // Check account and project restrictions
        $this->assertEquals([$instance1 ], $this->dao->filterDataProcessorInstances([], null, 0, 10, 2));
        $this->assertEquals([$instance2 ], $this->dao->filterDataProcessorInstances([], "BINGO", 0, 10, 1));


    }

}