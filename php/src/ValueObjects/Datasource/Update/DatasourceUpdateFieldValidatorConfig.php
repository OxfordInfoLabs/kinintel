<?php

namespace Kinintel\ValueObjects\Datasource\Update;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\FieldValidator\FieldValidator;

class DatasourceUpdateFieldValidatorConfig {

    /**
     * Validator config, stored with a field
     *
     * @param string $validatorKey
     * @param mixed $config
     */
    public function __construct(private string $validatorKey, private mixed $config = []) {
    }

    /**
     * @return string
     */
    public function getValidatorKey(): string {
        return $this->validatorKey;
    }

    /**
     * @param string $validatorKey
     */
    public function setValidatorKey(string $validatorKey): void {
        $this->validatorKey = $validatorKey;
    }

    /**
     * @return mixed
     */
    public function getConfig(): mixed {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig(mixed $config): void {
        $this->config = $config;
    }

    /**
     * Return a validator instance from this config
     *
     * @return FieldValidator
     */
    public function returnFieldValidator() {
        $matchingValidatorClass = Container::instance()->getInterfaceImplementationClass(FieldValidator::class, $this->validatorKey);
        if ($matchingValidatorClass) {
            $objectBinder = Container::instance()->get(ObjectBinder::class);
            return $objectBinder->bindFromArray($this->config, $matchingValidatorClass);
        }
    }


}