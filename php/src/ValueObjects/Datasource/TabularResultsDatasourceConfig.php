<?php


namespace Kinintel\ValueObjects\Datasource;


use Kinikit\Core\DependencyInjection\Container;
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
        $templateParser = Container::instance()->get(TemplateParser::class);
        $evaluatedColumns = [];
        foreach ($this->columns ?? [] as $column) {
            $evaluatedColumns[] = new Field($column->getName(), $column->getTitle(),
                $templateParser->parseTemplateText($column->getValueExpression(), $parameterValues));
        }
        return $evaluatedColumns;
    }


}