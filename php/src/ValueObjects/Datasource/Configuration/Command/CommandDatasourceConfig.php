<?php

namespace Kinintel\ValueObjects\Datasource\Configuration\Command;

use Kinintel\ValueObjects\Dataset\Field;

class CommandDatasourceConfig {
    /**
     * @param string[] $commands
     * @param string $outDir
     * @param Field[] $columns
     * @param int $firstRowOffset
     * @param string $cacheResultFileDateInterval
     */
    public function __construct(
        public array $commands,
        public string $outDir,
        public array $columns,
        public int $firstRowOffset,
        public string $cacheResultFileDateInterval = "1 day"
    ) {
    }
}