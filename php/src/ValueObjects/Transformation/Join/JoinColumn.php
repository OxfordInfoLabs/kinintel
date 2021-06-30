<?php


namespace Kinintel\ValueObjects\Transformation\Join;


use Kinintel\ValueObjects\Dataset\Field;

/**
 * Join column object adds an alias member to a field to work around duplicate column issues.
 *
 * Class JoinColumn
 * @package Kinintel\ValueObjects\Transformation\Join
 */
class JoinColumn extends Field {

    /**
     * @var string
     */
    private $alias;

    /**
     * JoinColumn constructor.
     *
     * @param string $alias
     */
    public function __construct($name, $alias = null, $title = null) {
        parent::__construct($name, $title);
        $this->alias = $alias ?? $name;
    }


    /**
     * @return string
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias) {
        $this->alias = $alias;
    }


}