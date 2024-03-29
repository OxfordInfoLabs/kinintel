<?php

namespace Kinintel\Test\Services\Util\Analysis\TextAnalysis;

use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\Services\Util\Analysis\TextAnalysis\StopwordManager;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;

include_once "autoloader.php";

class StopwordManagerTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var StopwordManager
     */
    private $stopwordManager;

    public function setUp(): void {
        $this->datasourceService = MockObjectProvider::instance()->getMockInstance(DatasourceService::class);
        $this->stopwordManager = new StopwordManager(Container::instance()->get(FileResolver::class), $this->datasourceService);

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testCanGetTheListOfStopwordsFromConfigForEnglish() {
        $stopWords = $this->stopwordManager->expandStopwords(new StopWord(true), 'EN')->getList();

        $this->assertTrue(is_array($stopWords));
        $this->assertEquals("a", $stopWords[0]);
        $this->assertEquals("about", $stopWords[1]);
        $this->assertEquals("above", $stopWords[2]);
        $this->assertEquals("after", $stopWords[3]);
        $this->assertEquals("again", $stopWords[4]);
    }

    public function testCanExtractStopWordsFromCustomDatasource() {

        $stopWord = new StopWord(false, true, "testKey", "testColumn");

        $mockDatasourceInstance = MockObjectProvider::instance()->getMockInstance(DatasourceInstance::class);
        $mockDatasource = MockObjectProvider::instance()->getMockInstance(BaseDatasource::class);

        $this->datasourceService->returnValue("getDataSourceInstanceByKey", $mockDatasourceInstance, ["testKey"]);
        $mockDatasourceInstance->returnValue("returnDataSource", $mockDatasource, []);
        $mockDatasource->returnValue("materialise", new ArrayTabularDataset([new Field("testColumn")], [
            [
                "testColumn" => "a"
            ],
            [
                "testColumn" => "the"
            ]
        ]), []);

        $stopWords = $this->stopwordManager->expandStopwords($stopWord, "EN")->getList();

        $this->assertTrue(is_array($stopWords));
        $this->assertEquals("a", $stopWords[0]);
        $this->assertEquals("the", $stopWords[1]);

    }

}
