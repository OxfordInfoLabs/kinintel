<?php


namespace Kinintel\Objects\Datasource\SQLDatabase\Util;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\TemplateParser;

class SQLFilterValueEvaluator {

    /**
     * @var TemplateParser
     */
    private $templateParser;


    /**
     * SQLValueEvaluator constructor.
     *
     * @param TemplateParser $templateParser
     */
    public function __construct($templateParser = null) {
        $this->templateParser = $templateParser ?? Container::instance()->get(TemplateParser::class);
    }


    /**
     * Evaluate a filter value using all required rules
     *
     * @param $value
     * @param array $templateParameters
     */
    public function evaluateFilterValue($value, $templateParameters = []) {

    }


}