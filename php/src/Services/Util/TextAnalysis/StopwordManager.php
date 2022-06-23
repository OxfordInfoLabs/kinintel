<?php

namespace Kinintel\Services\Util\TextAnalysis;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;

class StopwordManager {

    /**
     * @var FileResolver
     */
    private $fileResolver;

    private $stopwords = [];

    /**
     * @param FileResolver $fileResolver
     */
    public function __construct($fileResolver) {
        $this->fileResolver = $fileResolver;
    }

    public function getStopwordsByLanguage($languageKey) {
        if (isset($this->stopwords[$languageKey])) {
            return $this->stopwords[$languageKey];
        }

        $stopwords = file($this->fileResolver->resolveFile( "Config/stopwords/{$languageKey}.txt"), FILE_IGNORE_NEW_LINES);

        $this->stopwords[$languageKey] = $stopwords;
        return $stopwords;
    }

}
