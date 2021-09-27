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

        // Parse out any template params using standard template parser
        $value = $this->templateParser->parseTemplateText($value, $templateParameters);

        // Evaluate time offset parameters for days ago and hours ago
        $value = preg_replace_callback("/([0-9]+)_DAYS_AGO/", function ($matches) {
            return (new \DateTime())->sub(new \DateInterval("P" . $matches[1] . "D"))->format("Y-m-d H:i:s");
        }, $value);

        $value = preg_replace_callback("/([0-9]+)_HOURS_AGO/", function ($matches) {
            return (new \DateTime())->sub(new \DateInterval("PT" . $matches[1] . "H"))->format("Y-m-d H:i:s");
        }, $value);

        return $value;

    }


}