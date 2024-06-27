<?php

namespace Kinintel\Services\Datasource;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\TestBase;

include_once "autoloader.php";

class DatasourceServiceInterceptorTest extends TestBase {

    /**
     * @var DatasourceServiceInterceptor
     */
    private DatasourceServiceInterceptor $interceptor;

    /**
     * @var MockObject
     */
    private $datasourceService;


    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->interceptor = new DatasourceServiceInterceptor();
    }


    public function testIfRegularDatasourcePassedToBeforeMethodForNonTargetMethodsItHasNoImpact() {

        $result = $this->interceptor->beforeMethod($this->datasourceService, "getTransformedDataSourceByInstanceKey", ["instanceKey" => "test"], null);
        $this->assertEquals(["instanceKey" => "test"], $result);

        $instance = new DatasourceInstance("test-key", "Test Key", "custom");
        $result = $this->interceptor->beforeMethod($this->datasourceService, "getEvaluatedDataSource", ["datasourceInstance" => $instance], null);
        $this->assertEquals(["datasourceInstance" => $instance], $result);

        $this->assertFalse($this->datasourceService->methodWasCalled("getDatasetTreeForDatasourceKey"));

    }

    public function testIfSnapshotDatasourcePassedToBeforeMethodForTargetMethodACallIsMadeToGetTheDatasetTree() {

        $instance = new DatasourceInstance("test-key", "Test Key", "custom");
        $result = $this->interceptor->beforeMethod($this->datasourceService, "getTransformedDataSource", ["datasourceInstance" => $instance], null);
        $this->assertEquals(["datasourceInstance" => $instance], $result);

        $this->assertTrue($this->datasourceService->methodWasCalled("getDatasetTreeForDatasourceKey", [
            "test-key"
        ]));

    }


}