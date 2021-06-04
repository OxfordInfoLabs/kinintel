<?php


namespace Kinintel\Test\Services\DataProcessor;

use Kiniauth\Test\TestBase;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\DataProcessor\DataProcessorDAO;
use Kinintel\Services\DataProcessor\DataProcessorService;

include_once "autoloader.php";

class DataProcessorServiceTest extends TestBase {


    /**
     * @var DataProcessorService
     */
    private $dataProcessorService;


    /**
     * @var MockObject
     */
    private $dataProcessorDao;


    /**
     * Set up method
     */
    public function setUp(): void {
        $this->dataProcessorDao = MockObjectProvider::instance()->getMockInstance(DataProcessorDAO::class);
        $this->dataProcessorService = new DataProcessorService($this->dataProcessorDao);
    }

    public function testCanProcessADataProcessorInstance() {

        $this->dataProcessorDao->returnValue("getDataProcessorInstanceByKey",
            new DataProcessorInstance("bigone", "Big One", "datasourceimport"), [
                "bigone"
            ]);

        $this->dataProcessorService->processDataProcessorInstance("bigone");

        $this->assertTrue(true);


    }
}