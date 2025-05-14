<?php

namespace Kinintel\ValueObjects\Transformation\Filter;

trait InclusionCriteria {

    /**
     * Criteria for which this filter is included
     *
     * @var InclusionCriteriaType
     */
    private ?InclusionCriteriaType $inclusionCriteria = InclusionCriteriaType::Always;

    /**
     * @var mixed
     */
    private $inclusionData;


    /**
     * @return InclusionCriteriaType
     */
    public function getInclusionCriteria(): ?InclusionCriteriaType {
        return $this->inclusionCriteria;
    }

    /**
     * @return mixed|string|null
     */
    public function getInclusionData(): mixed {
        return $this->inclusionData;
    }

    /**
     * @param InclusionCriteriaType|null $inclusionCriteria
     */
    public function setInclusionCriteria(?InclusionCriteriaType $inclusionCriteria): void {
        $this->inclusionCriteria = $inclusionCriteria;
    }

    /**
     * @param mixed $inclusionData
     */
    public function setInclusionData(mixed $inclusionData): void {
        $this->inclusionData = $inclusionData;
    }


    /**
     * Return indicator as to whether or not this object meets the inclusion criteria
     *
     * @param $parameters
     * @return boolean
     */
    public function meetsInclusionCriteria($parameters = []) {
        switch ($this->inclusionCriteria) {
            case InclusionCriteriaType::Always:
                return true;
            case InclusionCriteriaType::ParameterPresent:
                return isset($parameters[$this->inclusionData]) && $parameters[$this->inclusionData] !== '';
            case InclusionCriteriaType::ParameterValue:
                $data = explode("=", $this->inclusionData);
                return sizeof($data) == 2 && (($parameters[$data[0]] ?? null) == $data[1]);
        }
    }


}