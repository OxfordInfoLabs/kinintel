<?php

namespace Kinintel\Objects\Datasource;

use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\ValueObjects\Dataset\Field;

class StubDatasource implements Datasource {


    public function getConfigClass() {
        // TODO: Implement getConfigClass() method.
    }

    public function getSupportedCredentialClasses() {
        // TODO: Implement getSupportedCredentialClasses() method.
    }

    public function isAuthenticationRequired() {
        return false;
    }

    public function getAuthenticationCredentials() {
        // TODO: Implement getAuthenticationCredentials() method.
    }

    public function setAuthenticationCredentials($authenticationCredentials) {
        // TODO: Implement setAuthenticationCredentials() method.
    }

    public function setInstanceInfo($instance) {
        // TODO: Implement setInstanceInfo() method.
    }

    public function getConfig() {
        // TODO: Implement getConfig() method.
    }

    public function setConfig($config) {
        // TODO: Implement setConfig() method.
    }

    public function getSupportedTransformationClasses() {
        // TODO: Implement getSupportedTransformationClasses() method.
    }

    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {
        // TODO: Implement applyTransformation() method.
    }

    public function materialise($parameterValues = []) {
        return new ArrayTabularDataset([
            new Field("value")
        ],[
            ["value"=> "Value 1"],
            ["value"=> "Value 2"],
            ["value"=> "Value 3"],
            ["value"=> "Value 4"],
            ["value"=> "Value 5"],
            ["value"=> "Value 6"],
            ["value"=> "Value 7"],
            ["value"=> "Value 8"],
            ["value"=> "Value 9"],
            ["value"=> "Value 10"]
        ]);
    }
}