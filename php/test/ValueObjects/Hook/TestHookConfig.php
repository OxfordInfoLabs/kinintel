<?php

namespace Kinintel\Test\ValueObjects\Hook;

class TestHookConfig {

    public function __construct(private string $testProp) {
    }

    /**
     * @return string
     */
    public function getTestProp(): string {
        return $this->testProp;
    }

    /**
     * @param string $testProp
     */
    public function setTestProp(string $testProp): void {
        $this->testProp = $testProp;
    }


}