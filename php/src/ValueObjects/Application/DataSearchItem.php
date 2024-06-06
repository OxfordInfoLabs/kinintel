<?php

namespace Kinintel\ValueObjects\Application;

/**
 * Search item value object with action items.
 */
class DataSearchItem {

    public function __construct(
        private string $type,
        private string $identifier,
        private string $title,
        private ?string $description = null,
        private ?string $owningAccountName = null,
        private ?string $owningAccountLogo = null,
        private ?array  $actionItems = []
    ) {
        $this->type = str_contains($type, "snapshot") ? "snapshot" : $type;
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
    public function getDescription(): string {
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
     * @return array
     */
    public function getActionItems(): array {
        return $this->actionItems;
    }


}