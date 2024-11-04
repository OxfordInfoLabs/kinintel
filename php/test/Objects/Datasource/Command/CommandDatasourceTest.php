<?php

namespace Kinintel\Test\Objects\Datasource\Command;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Datasource\Command\CommandDatasource;
use Kinintel\TestBase;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\Command\CommandDatasourceConfig;

include_once "autoloader.php";

class CommandDatasourceTest extends TestBase {

    public function testCommandDatasourceDoesntRunIfFileIsFresh(){
        $outDir = "/tmp/test_command";
        passthru("mkdir -p $outDir");
        passthru("rm $outDir/*");
        passthru("echo bob > $outDir/out.csv");
        $date = date_create()->format("Y-m-d-H-i-s");
        $config = new CommandDatasourceConfig([
            "echo hi > $outDir/$date.txt",
            "echo bob > $outDir/out.csv"
        ], $outDir, [new Field("name")], 0);
        $datasource = new CommandDatasource($config);
        $this->assertFalse(file_exists("$date.txt"));
        $data = $datasource->materialiseDataset()->getAllData();
        $this->assertEquals([["name" => "bob"]], $data);
        $this->assertFalse(file_exists("/tmp/$date.txt"));
    }

    public function testCommandDatasourceRunsIfFileIsNotFresh(){
        $outDir = "/tmp/test_command2";
        passthru("mkdir -p $outDir");
        passthru("rm $outDir/*");
        $date = date_create()->format("Y-m-d-H-i-s");
        $config = new CommandDatasourceConfig([
            "echo hi > $outDir/$date.txt",
            "echo pete,100 > $outDir/out.csv"
        ], $outDir, [new Field("name"), new Field("clout")], 0);
        $datasource = new CommandDatasource($config);
        $this->assertFalse(file_exists("$outDir/$date.txt"));
        $data = $datasource->materialiseDataset()->getAllData();
        $this->assertEquals([["name" => "pete", "clout" => 100]], $data);
        $this->assertFalse(file_exists("/tmp/$date.txt"));
    }


}