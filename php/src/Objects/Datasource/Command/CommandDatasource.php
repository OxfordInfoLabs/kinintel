<?php

namespace Kinintel\Objects\Datasource\Command;

use DateInterval;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\ExternalCommands\ExternalCommandException;
use Kinikit\Core\ExternalCommands\ExternalCommandProcessor;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use Kinikit\Core\Util\DateTimeUtils;
use Kinintel\Objects\Dataset\Tabular\SVStreamTabularDataSet;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\ValueObjects\Datasource\Configuration\Command\CommandDatasourceConfig;

class CommandDatasource extends BaseDatasource {


    public function getConfigClass() {
        return CommandDatasourceConfig::class;
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

        // Only process if the file wasn't updated recently
        if (!DateTimeUtils::wasUpdatedInTheLast(
            DateInterval::createFromDateString("+".$config->cacheResultFileDateInterval),
            "$config->outDir/out.csv")
        ) {
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