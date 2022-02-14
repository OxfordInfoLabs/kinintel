<?php


namespace Kinintel\ValueObjects\Datasource\Update;


use Kinintel\ValueObjects\Dataset\Field;

class DatasourceUpdateField extends Field {

    /**
     * @var string
     */
    private $originalName;

    /**
     * @return string
     */
    public function getOriginalName() {
        return $this->originalName;
    }

    /**
     * @param string $originalName
     */
    public function setOriginalName($originalName) {
        $this->originalName = $originalName;
    }


}