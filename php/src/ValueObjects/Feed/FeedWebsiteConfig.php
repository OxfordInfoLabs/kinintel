<?php

namespace Kinintel\ValueObjects\Feed;

/**
 * Config in use when calling from a website
 */
class FeedWebsiteConfig {

    /**
     * @var string[]
     */
    private $referringDomains = [];

    /**
     * @var boolean
     */
    private $requiresCaptcha = false;


    /**
     * @var mixed
     */
    private $captchaConfig = null;

    /**
     * @param string[] $referringDomains
     * @param bool $requiresCaptcha
     */
    public function __construct($referringDomains = [], $requiresCaptcha = false, $captchaConfig = null) {
        $this->referringDomains = $referringDomains;
        $this->requiresCaptcha = $requiresCaptcha;
        $this->captchaConfig = $captchaConfig;
    }


    /**
     * @return string[]
     */
    public function getReferringDomains() {
        return $this->referringDomains;
    }

    /**
     * @param string[] $referringDomains
     */
    public function setReferringDomains($referringDomains) {
        $this->referringDomains = $referringDomains;
    }

    /**
     * @return bool
     */
    public function isRequiresCaptcha() {
        return $this->requiresCaptcha;
    }

    /**
     * @param bool $requiresCaptcha
     */
    public function setRequiresCaptcha($requiresCaptcha) {
        $this->requiresCaptcha = $requiresCaptcha;
    }

    /**
     * @return mixed
     */
    public function getCaptchaConfig() {
        return $this->captchaConfig;
    }

    /**
     * @param mixed $captchaConfig
     */
    public function setCaptchaConfig($captchaConfig) {
        $this->captchaConfig = $captchaConfig;
    }


}