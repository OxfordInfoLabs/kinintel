<?php

namespace Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding;

/**
 * @implementation openai Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\OpenAIEmbeddingService
 * @defaultImplementation Kinintel\Services\Util\Analysis\TextAnalysis\VectorEmbedding\OpenAIEmbeddingService
 */
interface TextEmbeddingService {
    public function embedString(string $text) : array;

    /**
     * @param string[] $texts
     * @return array[]
     */
    public function embedStrings(array $texts) : array;
    public function compareEmbedding($embedding1, $embedding2);
}