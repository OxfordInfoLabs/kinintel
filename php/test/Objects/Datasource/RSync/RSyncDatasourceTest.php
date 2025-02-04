<?php

namespace Kinintel\Test\Objects\Datasource\RSync;

use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\RSync\RSyncDatasource;
use Kinintel\ValueObjects\Datasource\Configuration\RSync\RSyncDatasourceConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class RSyncDatasourceTest extends TestCase {

    public function testCanRSyncFile() {

        $config = new RSyncDatasourceConfig(__DIR__ . "/testSource.txt", "", "sv");

        $datasource = new RSyncDatasource();
        $datasource->setConfig($config);
        $datasource->setInstanceInfo(new DatasourceInstance("test-my-instance", "Test My Instance", "rsync"));

        $dataset = $datasource->materialise();

        $this->assertInstanceOf(SVStreamTabularDataSet::class, $dataset);

        $resource = $dataset->returnStream()->getResource();
        $streamMeta = stream_get_meta_data($resource);
        $filename = $streamMeta["uri"];
        $this->assertEquals("Files/rsync/test-my-instance", $filename);

        // Check data as well
        $this->assertEquals(["column1" => "A Test File"], $dataset->nextRawDataItem());
        $this->assertEquals(["column1" => "RSync"], $dataset->nextRawDataItem());
    }

}