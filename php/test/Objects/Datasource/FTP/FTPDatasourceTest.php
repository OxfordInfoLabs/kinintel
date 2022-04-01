<?php


namespace Kinintel\Test\Objects\Datasource\FTP;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\FTP\FTPDataSource;
use Kinintel\Objects\ResultFormatter\SVResultFormatter;
use Kinintel\Services\Datasource\Processing\Compression\Compressor;
use Kinintel\ValueObjects\Authentication\FTP\FTPAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Exporter\SVDatasetExporterConfiguration;
use Kinintel\ValueObjects\Datasource\Configuration\FTP\FTPDatasourceConfig;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;

include_once "autoloader.php";

class FTPDatasourceTest extends \PHPUnit\Framework\TestCase {


    public function testCanMaterialiseDataSetForFTPDatasource() {

        $ftpDatasource = new FTPDataSource(new FTPDatasourceConfig("test.rebex.net", "readme.txt", true, "sv"),
            new FTPAuthenticationCredentials("demo", "password"));

        $dataSet = $ftpDatasource->materialise([]);
        $this->assertTrue($dataSet instanceof SVStreamTabularDataSet);

        $allData = $dataSet->getAllData();
        $this->assertEquals(6, sizeof($allData));
        $this->assertEquals("Welcome", $allData[0]["column1"]);

    }


    public function testCanApplyPagingTransformationToFTPDatasource() {


        $ftpDatasource = new FTPDataSource(new FTPDatasourceConfig("test.rebex.net", "readme.txt", true, "sv"),
            new FTPAuthenticationCredentials("demo", "password"));

        $transformedDatasource = $ftpDatasource->applyTransformation(new PagingTransformation(3, 1));
        $this->assertInstanceOf(FTPDataSource::class, $transformedDatasource);

        $dataSet = $transformedDatasource->materialise([]);
        $this->assertTrue($dataSet instanceof SVStreamTabularDataSet);

        $allData = $dataSet->getAllData();

        $this->assertEquals(2, sizeof($allData));
        $this->assertEquals("You are connected to an FTP or SFTP server used for testing purposes by Rebex FTP/SSL or Rebex SFTP sample code.", $allData[0]["column1"]);


    }


    public function testCanApplyCompressionConfigToFTPDatasource() {


        Container::instance()->addInterfaceImplementation(Compressor::class, "test", "TestCompressor");
        $mockCompressor = MockObjectProvider::instance()->getMockInstance(Compressor::class);
        $mockCompressor->returnValue("getConfigClass", TestCompressorConfig::class);
        $mockCompressor->returnValue("uncompress", "BINGO");
        Container::instance()->set("TestCompressor", $mockCompressor);


        $ftpDatasourceConfig = new FTPDatasourceConfig("test.rebex.net", "readme.txt", true, "sv");
        $ftpDatasourceConfig->setCompressionType("test");
        $ftpDatasourceConfig->setCompressionConfig([]);

        $ftpDatasource = new FTPDataSource($ftpDatasourceConfig,
            new FTPAuthenticationCredentials("demo", "password"));

        $dataSet = $ftpDatasource->materialise([]);
        $this->assertTrue($dataSet instanceof SVStreamTabularDataSet);
        $this->assertEquals("BINGO", $dataSet->returnStream());


    }


}