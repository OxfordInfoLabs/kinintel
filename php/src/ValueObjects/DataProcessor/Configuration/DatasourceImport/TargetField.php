<?php


namespace Kinintel\ValueObjects\DataProcessor\Configuration\DatasourceImport;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\FieldMapper\FieldMapper;
use Kinintel\ValueObjects\Dataset\Field;

class TargetField extends Field {

    /**
     * The target name to map to if different from the source name
     *
     * @var string
     */
    private $targetName;


    /**
     * @var string
     */
    private $mapper;


    /**
     * @var mixed
     */
    private $mapperConfig;

    /**
     * @var FieldMapper
     */
    private $mapperInstance;


    /**
     * TargetField constructor.
     * @param string $targetName
     */
    public function __construct($name, $targetName = null, $mapper = null, $mapperConfig = null) {
        parent::__construct($name);
        $this->targetName = $targetName;
        $this->mapper = $mapper;
        $this->mapperConfig = $mapperConfig;
    }


    /**
     * @return string
     */
    public function getTargetName() {
        return $this->targetName;
    }

    /**
     * @param string $targetName
     */
    public function setTargetName($targetName) {
        $this->targetName = $targetName;
    }

    /**
     * @return string
     */
    public function getMapper() {
        return $this->mapper;
    }

    /**
     * @param string $mapper
     */
    public function setMapper($mapper) {
        $this->mapper = $mapper;
    }

    /**
     * @return mixed
     */
    public function getMapperConfig() {
        return $this->mapperConfig;
    }

    /**
     * @param mixed $mapperConfig
     */
    public function setMapperConfig($mapperConfig) {
        $this->mapperConfig = $mapperConfig;
    }


    /**
     * Return mapped value
     */
    public function returnMappedValue($unmappedValue) {
        if ($this->mapper) {
            if (!$this->mapperInstance) {
                $mapperClass = Container::instance()->getInterfaceImplementationClass(FieldMapper::class, $this->mapper);

                /**
                 * @var ObjectBinder $objectBinder
                 */
                $objectBinder = Container::instance()->get(ObjectBinder::class);
                $this->mapperInstance = $objectBinder->bindFromArray($this->mapperConfig, $mapperClass);

            }
            return $this->mapperInstance->mapValue($unmappedValue);
        } else {
            return $unmappedValue;
        }
    }


}