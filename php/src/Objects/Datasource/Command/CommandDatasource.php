<?php

namespace Kinintel\Objects\Datasource\Command;

use DateInterval;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\ExternalCommands\ExternalCommandException;
use Kinikit\Core\ExternalCommands\ExternalCommandProcessor;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\ValueObjects\Datasource\Configuration\Command\CommandDatasourceConfig;

class CommandDatasource extends BaseDatasource {


    public function getConfigClass() {
        return CommandDatasourceConfig::class;
    }

    public static function wasEditedInTheLast(DateInterval $dateInterval, string $file) : bool {
        $commandProcessor = Container::instance()->get(ExternalCommandProcessor::class);
        if (!file_exists($file)) return true;
        $lastModifiedDateString = $commandProcessor->process("date -r $file -u +\"%Y-%m-%d %H:%M:%S\"");
        $lastModifiedDate = date_create_from_format("Y-m-d H:i:s", $lastModifiedDateString);
        return $lastModifiedDate > date_create()->sub($dateInterval);
    }

    /**
     * @throws ExternalCommandException
     */
    public function materialiseDataset($parameterValues = []) {
        $commandProcessor = Container::instance()->get(ExternalCommandProcessor::class);
        /** @var CommandDatasourceConfig $config */
        $config = $this->getConfig();

        // Create outDir if not exists
        $commandProcessor->process("mkdir -p $config->outDir");

        // Hit the file to see if it was updated recently
        if (self::wasEditedInTheLast(DateInterval::createFromDateString("+".$config->cacheResultFileDateInterval), "$config->outDir/out.csv")) {
            foreach ($config->commands as $command) {
                $commandProcessor->process($command);
            }
        }

        return new SVStreamTabularDataSet(
            $config->columns,
            new ReadOnlyFileStream("$config->outDir/out.csv"),
            firstRowOffset: $config->firstRowOffset
        );
    }

    public function getSupportedTransformationClasses() {
        return [];
    }

    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        return $this;
    }

    public function isAuthenticationRequired() {
        return false;
    }
}