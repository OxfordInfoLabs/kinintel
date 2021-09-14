<?php


namespace Kinintel\Services\DataProcessor;


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

        $this->assertEquals(new DataProcessorInstance("test-import", "Test import data processor", "datasourceimport"),
            $filesystemInstance);

    }


    public function testCanStoreRetrieveAndRemoveDatabaseStoredDataProcessorInstances() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $newInstance = new DataProcessorInstance("new-test", "New Test One", "datasourceimport");

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

}