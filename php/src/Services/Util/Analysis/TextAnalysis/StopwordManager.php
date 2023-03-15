<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis;

use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Util\Analysis\TextAnalysis\StopWord;

class StopwordManager {

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    /**
     * @var FileResolver
     */
    private $fileResolver;

    private $stopwords = [];

    /**
     * @param FileResolver $fileResolver
     * @param DatasourceService $datasourceService
     */
    public function __construct($fileResolver, $datasourceService) {
        $this->fileResolver = $fileResolver;
        $this->datasourceService = $datasourceService;
    }

    public function expandStopwords($stopWord, $languageKey) {

        if ($stopWord->isBuiltIn()) {
            $this->expandBuiltInStopwordsByLanguage($stopWord, $languageKey);

        } elseif ($stopWord->isCustom() && $stopWord->getDatasourceKey() && $stopWord->getDatasourceColumn()) {
            $this->expandCustomStopwords($stopWord);
        }

        return $stopWord;

    }

    /**
     * @param StopWord $stopWord
     * @param string $languageKey
     * @return void
     */
    private function expandBuiltInStopwordsByLanguage($stopWord, $languageKey) {
        if (!isset($this->stopwords[$languageKey])) {
            $stopwords = file($this->fileResolver->resolveFile("Config/stopwords/{$languageKey}.txt"), FILE_IGNORE_NEW_LINES);
            $this->stopwords[$languageKey] = $stopwords;
        }

        $stopWord->setList($this->stopwords[$languageKey]);
    }

    private function expandCustomStopwords($stopWord) {

        $stopwordDatasourceInstance = $this->datasourceService->getDataSourceInstanceByKey($stopWord->getDatasourceKey());
        /** @var TabularDataset $dataset */
        $stopwordDataset = $stopwordDatasourceInstance->returnDataSource()->materialise();

        $stopWord->setList(array_map(function ($data) use ($stopWord) {
            return $data[$stopWord->getDatasourceColumn()];
        }, $stopwordDataset->getAllData()));

    }

}
