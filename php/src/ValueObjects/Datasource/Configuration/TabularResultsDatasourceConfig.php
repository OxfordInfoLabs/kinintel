<?php


namespace Kinintel\ValueObjects\Datasource\Configuration;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinintel\ValueObjects\Dataset\Field;

class TabularResultsDatasourceConfig implements DatasourceConfig {

    /**
     * @var Field[]
     */
    private $columns;

    /**
     * TabularResultsDatasourceConfig constructor.
     *
     * @param Field[] $columns
     */
    public function __construct($columns = []) {
        $this->columns = $columns;
    }

    /**
     * @return Field[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * @param Field[] $columns
     */
    public function setColumns($columns) {
        $this->columns = $columns;
    }


    /**
     * Return evaluated columns - substitute parameter values where applicable
     *
     * @param array $parameterValues
     */
    public function returnEvaluatedColumns($parameterValues = []) {
        $evaluatedColumns = [];
        foreach ($this->columns ?? [] as $column) {

            $valueExpression = preg_replace_callback("/{{(.*?)}}/", function ($matches) use ($parameterValues) {
                return $parameterValues[$matches[1]] ?? "";
            }, $column->getValueExpression());

            $evaluatedColumns[] = new Field($column->getName(), $column->getTitle(),
                $valueExpression, $column->getType(), $column->isKeyField());

        }

        return $evaluatedColumns;
    }


}