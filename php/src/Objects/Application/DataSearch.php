<?php

namespace Kinintel\Objects\Application;

/**
 * @table ki_data_search
 * @readOnly
 */
class DataSearch {

    /**
     * @primaryKey
     * @var string
     */
    private ?string $type;

    /**
     * @primaryKey
     * @var string
     */
    private ?string $identifier;


    /**
     * @var mixed
     * @json
     */
    private mixed $configuration;

    /**
     * Construct data search result with constituent parts
     *
     * @param string $type
     * @param string $identifier
     * @param string $title
     * @param string $description
     * @param string $owningAccountName
     * @param string $owningAccountLogo
     * @param mixed $configuration
     */
    public function __construct($type,
                                private ?string $typeClass,
        $identifier,
                                private ?string $title = null,
                                private ?string $description = null,
                                private ?string $owningAccountName = null,
                                private ?string $owningAccountLogo = null,
        $configuration = null

    ) {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeClass(): ?string {
        return $this->typeClass;
    }


    /**
     * @return string
     */
    public function getIdentifier(): string {
        return $this->identifier;
    }


    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getOwningAccountName(): ?string {
        return $this->owningAccountName;
    }

    /**
     * @return string|null
     */
    public function getOwningAccountLogo(): ?string {
        return $this->owningAccountLogo;
    }


    /**
     * @return mixed
     */
    public function getConfiguration(): mixed {
        return $this->configuration;
    }


}