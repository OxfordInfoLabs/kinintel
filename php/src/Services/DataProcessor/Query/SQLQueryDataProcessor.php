<?php

namespace Kinintel\Services\DataProcessor\Query;

use Exception;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Exception\InvalidDataProcessorConfigException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\DataProcessor\DataProcessorInstance;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\Services\DataProcessor\BaseDataProcessor;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;
use Kinintel\ValueObjects\DataProcessor\Configuration\Query\SQLQueryDataProcessorConfiguration;

class SQLQueryDataProcessor extends BaseDataProcessor {

    public function __construct(
        private AuthenticationCredentialsService $authenticationService) {
    }

    public function getConfigClass() : string {
        return SQLQueryDataProcessorConfiguration::class;
    }

    /**
     * Be careful using this class with user inputted data, as it is a potential SQL injection point!
     *
     * @param DataProcessorInstance $instance
     * @return void
     * @throws InvalidDataProcessorConfigException
     * @throws \Kinikit\Persistence\Database\Exception\SQLException
     * @throws \Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException
     */
    public function process($instance) : void {

        /** @var SQLQueryDataProcessorConfiguration $config */
        $config = $instance->returnConfig();

        $credentialsInstance = $this->authenticationService->getCredentialsInstanceByKey($config->getAuthenticationCredentialsKey());

        // Get the credentials object and confirm it is a SQL database object
        $credentials = $credentialsInstance->returnCredentials();

        if (!($credentials instanceof SQLDatabaseCredentials)) {
            throw new InvalidDataProcessorConfigException(["authenticationCredentialsKey" => [
                "wrongType" => new FieldValidationError("authenticationCredentialsKey", "wrongType", "The credentials supplied were of the wrong type - must be SQL Database Credentials")
            ]]);
        }

        $databaseConnection = $credentials->returnDatabaseConnection();

        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        $queries = match (true) {
            (bool)$config->getQuery() => [$config->getQuery()],
            (bool)$config->getQueries() => $config->getQueries(),
            (bool)$config->getScriptFilepath() => $this->scriptToStatements(file_get_contents($config->getScriptFilepath())),
            default => throw new InvalidDataProcessorConfigException(
                ["noSQLProvided" => new FieldValidationError(null, null, "No SQL code provided to be run in SQLQueryDataProcessor.")]
            )
        };

        foreach ($queries as $query) {
            $query = $parameterisedStringEvaluator->evaluateString($query, [], []);

            $databaseConnection->execute($query);
        }

    }

    public function onInstanceDelete($instance) {

    }

    public static function scriptToStatements(string $script) {
        // Delete comments
        // NOTE: We are allowed to have "#" or " -- hello" in strings!
        //       This means we need to work out when something is in a quote or comment.
        $inSingleLineComment = false;
        $inMultilineComment = false;
        $inSingleQuotes = false;
        $inDoubleQuotes = false;
        $out = "";
        $i = 0;
        while ($i < strlen($script)) {
            $curr = $script[$i];
            $next = $script[$i+1] ?? null;
            $next2 = $next ? $curr.$next : null;

            if ($curr == "\"" && !$inSingleLineComment && !$inMultilineComment) {
                $inDoubleQuotes = !$inDoubleQuotes;
            }

            if ($curr == "'" && !$inSingleLineComment && !$inMultilineComment) {
                $inSingleQuotes = !$inSingleQuotes;
            }

            $singleLineCommentStart = $curr == "#" || ($next && $next2 == "--");
            if ($singleLineCommentStart && !$inSingleQuotes && !$inDoubleQuotes) {
                $inSingleLineComment = true;
            }

            if ($curr == "\n" && $inSingleLineComment){
                $inSingleLineComment = false;
            }

            if ($next && $next2 == "/*" && !$inSingleLineComment && !$inSingleQuotes && !$inDoubleQuotes) {
                $inMultilineComment = true;
            }

            if ($next && $next2 == "*/" && $inMultilineComment) {
                if ($inDoubleQuotes || $inSingleQuotes) {
                    throw new \AssertionError("Can't be in quotes in a multi-line comment");
                }
                $inMultilineComment = false;
                $i += 2; // Skip the end of the multiline comment.
                continue;
            }

            if (!$inSingleLineComment && !$inMultilineComment){
                $out .= $curr;
            }

            $i++;

        }

        $statements = explode(";", $out);
        $statements = array_map(fn($x) => trim($x), $statements);

        return array_filter($statements, fn($st) => !empty($st));
    }

}