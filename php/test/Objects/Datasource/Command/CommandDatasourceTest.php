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
        $outDir = "/tmp/command";
        passthru("rm $outDir/*");
        passthru("mkdir -p $outDir");
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
        $outDir = "/tmp/command2";
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

    public function testWasModifiedRecently() {
        passthru("touch -c ~/.bashrc");

        passthru("mkdir -p ~/tmp");
        passthru("touch ~/tmp/example.txt");

        $out = CommandDatasource::wasUpdatedInTheLast(
            \DateInterval::createFromDateString("+1 hour"),
            "~/.bashrc"
        );

        $this->assertFalse($out);


        // PHP is in a different timezone from Linux so we need 2 hours
        $out = CommandDatasource::wasUpdatedInTheLast(
            \DateInterval::createFromDateString("+2 hour"),
            "~/tmp/example.txt"
        );

        $this->assertTrue($out);
    }
}