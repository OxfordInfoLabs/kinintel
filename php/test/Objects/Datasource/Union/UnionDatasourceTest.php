<?php

namespace Kinintel\Test\Objects\Datasource\Union;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\TestDatasource;
use Kinintel\Objects\Datasource\Union\UnionDatasource;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Union\DatasourceMapping;
use Kinintel\ValueObjects\Datasource\Configuration\Union\UnionDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class UnionDatasourceTest extends TestCase {

    /**
     * @var MockObject
     */
    private $datasourceService;

    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
    }

    public function testCanUnionTwoDatasources() {

        $sourceDatasourceMapping1 = new DatasourceMapping("source1", ["name"]);
        $sourceDatasourceMapping2 = new DatasourceMapping("source2", ["name"]);

        $sourceDatasourceInstance1 = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $sourceDatasource1 = MockObjectProvider::instance()->getMockInstance(Datasource::class);

        $sourceDatasourceInstance1->returnValue("returnDataSource", $sourceDatasource1, []);
        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $sourceDatasourceInstance1, ["source1"]);

        $config = new UnionDatasourceConfig([$sourceDatasourceMapping1, $sourceDatasourceMapping2], [new Field("name")]);


        $datasource = new UnionDatasource($config);
        $datasource->setDatasourceService($this->datasourceService);

        $datasource->materialise();

        $this->assertTrue($sourceDatasource1->methodWasCalled("applyTransformation", [
            new CombineTransformation("source2", null, CombineTransformation::COMBINE_TYPE_UNION, ["name" => new Field("name")])
        ]));
        $this->assertTrue($sourceDatasource1->methodWasCalled("materialise", [[]]));

    }

}