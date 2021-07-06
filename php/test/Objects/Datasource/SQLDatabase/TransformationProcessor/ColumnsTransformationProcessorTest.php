<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor;


use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;

include_once "autoloader.php";

class ColumnsTransformationProcessorTest extends \PHPUnit\Framework\TestCase {

    public function testApplyTransformationUpdatesColumnListForDatasource() {

        $datasource = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasource::class);
        $config = MockObjectProvider::instance()->getMockInstance(SQLDatabaseDatasourceConfig::class);
        $datasource->returnValue("getConfig", $config);

        $columnsTransformation = new ColumnsTransformation([
            new Field("field1"), new Field("field2")
        ]);

        $transformationProcessor = new ColumnsTransformationProcessor();
        $transformationProcessor->applyTransformation($columnsTransformation, $datasource, []);

        $this->assertTrue($config->methodWasCalled("setColumns", [[
            new Field("field1"), new Field("field2")
        ]]));


    }

}