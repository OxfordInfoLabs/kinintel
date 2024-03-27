<?php

namespace Kinintel\Objects\Datasource\AI;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\TextEmbeddingService;
use Kinintel\ValueObjects\Dataset\Field;


class EmbeddingDatasource extends BaseDatasource {

    /**
     * Returns a single column "embedding" with the embedded vector as a string
     * @param $parameterValues
     * @return ArrayTabularDataset
     */
    public function materialiseDataset($parameterValues = []) {
        /** @var TextEmbeddingService $embeddingService */
        $embeddingService = Container::instance()->get(TextEmbeddingService::class);
        $embedding = $embeddingService->embedString($parameterValues["textToEmbed"]);
        return new ArrayTabularDataset([new Field("embedding")], [["embedding" => json_encode($embedding)]]);
    }

    public function getSupportedTransformationClasses() {
        return [];
    }

    public function isAuthenticationRequired() {
        return false;
    }

    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        return $this;
    }
}