<?php

namespace Kinintel\ValueObjects\Transformation\Filter;


enum InclusionCriteriaType: string {
    case Always = "always";
    case ParameterPresent = "parameterpresent";
    case ParameterValue = "parametervalue";
}