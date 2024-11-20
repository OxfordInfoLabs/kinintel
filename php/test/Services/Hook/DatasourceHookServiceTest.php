<?php

namespace Kinintel\Test\Services\Hook;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Hook\DatasourceHookInstance;
use Kinintel\Services\DataProcessor\DataProcessorService;
use Kinintel\Services\Hook\DatasourceHookService;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class DatasourceHookServiceTest extends TestCase {

    private $dataProcessorService;

    private $hookService;

    public function setUp(): void {
        $this->dataProcessorService = MockObjectProvider::mock(DataProcessorService::class);
        $this->hookService = new DatasourceHookService($this->dataProcessorService);
    }

    public function testCanCreateNewHookAndRelatedDataProcessor() {

        $config = [];
        $this->hookService->createHook("mySource", DatasourceHookInstance::HOOK_MODE_ADD, "processorType", $config);

    }
}