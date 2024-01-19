<?php

namespace Kinintel\Services\DataProcessor\Analysis\AI;

use JetBrains\PhpStorm\Pure;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\ValueObjects\Dataset\Field;

class EmbeddingDatasourceProcessor {
    public function process(DataProcessorInstance $instance){

    }

    /**
     * @param $keyFields
     * @param Field $textField
     * @param int $dims
     * @return DatasourceInstance
     */
    #[Pure]
    private function createEmbeddingDatasourceInstance($keyFields, Field $textField, int $dims = 1536) : DatasourceInstance{

    }

    /**
     * @param array $row
     * @param string ...$keyFields The fields which can be hashed to produce a unique identifier
     * @return string
     */
    #[Pure]
    private function hashField(array $row, string ...$keyFields): string {
        $infoToHash = [];
        foreach ($keyFields as $keyField){
            if (in_array($keyField, $row)){
                $infoToHash[] = "$keyField=" . $row[$keyField];
            }
        }
        return md5(join(";", $infoToHash));
    }
}