<?php


namespace Kinintel\ValueObjects\Transformation\Join;


class JoinParameterMapping {

    /**
     * Name of parameter on the joining object
     *
     * @var string
     * @required
     */
    private $parameterName;


    /**
     * Name of a source parameter to use to fulfil this parameter
     *
     * @var string
     * @requiredEither sourceColumnName
     */
    private $sourceParameterName;

    /**
     * Name of a column on source object to use to fulfil this parameter
     *
     * @var string
     */
    private $sourceColumnName;

    /**
     * JoinParameterMapping constructor.
     *
     * @param string $parameterName
     * @param string $sourceParameterName
     * @param string $sourceColumnName
     */
    public function __construct($parameterName, $sourceParameterName = null, $sourceColumnName = null) {
        $this->parameterName = $parameterName;
        $this->sourceParameterName = $sourceParameterName;
        $this->sourceColumnName = $sourceColumnName;
    }


    /**
     * @return string
     */
    public function getParameterName() {
        return $this->parameterName;
    }

    /**
     * @param string $parameterName
     */
    public function setParameterName($parameterName) {
        $this->parameterName = $parameterName;
    }

    /**
     * @return string
     */
    public function getSourceParameterName() {
        return $this->sourceParameterName;
    }

    /**
     * @param string $sourceParameterName
     */
    public function setSourceParameterName($sourceParameterName) {
        $this->sourceParameterName = $sourceParameterName;
    }

    /**
     * @return string
     */
    public function getSourceColumnName() {
        return $this->sourceColumnName;
    }

    /**
     * @param string $sourceColumnName
     */
    public function setSourceColumnName($sourceColumnName) {
        $this->sourceColumnName = $sourceColumnName;
    }


}