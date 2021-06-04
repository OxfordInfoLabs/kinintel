<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Query\Transformation;

class TestUpdatableDatasource extends BaseDatasource implements UpdatableDatasource {

    use UpdatableDatasourceTrait;


    public function materialiseDataset($parameterValues = []) {
        // TODO: Implement materialiseDataset() method.
    }

    public function getSupportedTransformationClasses() {
        // TODO: Implement getSupportedTransformationClasses() method.
    }

    public function applyTransformation($transformation, $parameterValues = []) {
        // TODO: Implement applyTransformation() method.
    }
}