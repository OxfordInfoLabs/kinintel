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
     * @var string
     */
    private $captchaSecretKey = null;

    /**
     * @var float
     */
    private $captchaScoreThreshold = null;


    /**
     * @param string[] $referringDomains
     * @param bool $requiresCaptcha
     * @param string $captchaSecretKey
     * @param float $captchaScoreThreshold
     */
    public function __construct($referringDomains = [], $requiresCaptcha = false, $captchaSecretKey = null, $captchaScoreThreshold = null) {
        $this->referringDomains = $referringDomains;
        $this->requiresCaptcha = $requiresCaptcha;
        $this->captchaSecretKey = $captchaSecretKey;
        $this->captchaScoreThreshold = $captchaScoreThreshold;
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
    public function getCaptchaSecretKey() {
        return $this->captchaSecretKey;
    }

    /**
     * @param mixed $captchaSecretKey
     */
    public function setCaptchaSecretKey($captchaSecretKey) {
        $this->captchaSecretKey = $captchaSecretKey;
    }

    /**
     * @return float
     */
    public function getCaptchaScoreThreshold() {
        return $this->captchaScoreThreshold;
    }

    /**
     * @param float $captchaScoreThreshold
     */
    public function setCaptchaScoreThreshold($captchaScoreThreshold) {
        $this->captchaScoreThreshold = $captchaScoreThreshold;
    }


}