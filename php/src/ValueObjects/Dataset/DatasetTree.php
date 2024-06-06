<?php

namespace Kinintel\ValueObjects\Dataset;

use Kinintel\ValueObjects\Application\DataSearchItem;


class DatasetTree {

    /**
     * Construct a new dataset tree.
     *
     * @param DataSearchItem $dataItem
     */
    public function __construct(private DataSearchItem $dataItem,
                                private ?DatasetTree   $parentTree = null,
                                private array          $joinedTrees = []) {
    }


    /**
     * Get the type of item
     *
     * @return string
     */
    public function getType() {
        return $this->dataItem->getType();
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string {
        return $this->dataItem->getTitle();
    }


    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->dataItem->getDescription();
    }

    /**
     * @return string
     */
    public function getOwningAccountName(): string {
        return $this->dataItem->getOwningAccountName();
    }


    /**
     * Get the parent tree if applicable
     *
     * @return DatasetTree|null
     */
    public function getParentTree(): ?DatasetTree {
        return $this->parentTree;
    }

    /**
     * Get joined trees if applicable
     *
     * @return DatasetTree[]
     */
    public function getJoinedTrees(): array {
        return $this->joinedTrees;
    }


}