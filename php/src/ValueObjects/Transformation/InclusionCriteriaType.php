<?php

namespace Kinintel\ValueObjects\Transformation;


enum InclusionCriteriaType: string {
    case Always = "always";
    case ParameterPresent = "parameterpresent";
    case ParameterValue = "parametervalue";
    case ParameterNotPresent = "parameternotpresent";
}