<?php

namespace Kinintel\Services\Util\ValueFunction;


use Kiniauth\Services\Application\Session;
use Kinikit\Core\Template\ValueFunction\ValueFunctionWithArguments;

class AuthenticatedItemsValueFunction extends ValueFunctionWithArguments {

    /**
     * @var string
     */
    private $activeProjectKey;

    /**
     * Construct with session
     *
     * @param Session $session
     */
    public function __construct(public Session $session) {
    }


    /**
     * Get functions supported by this value function
     *
     * @return string[]
     */
    protected function getSupportedFunctionNames() {
        return [
            "loggedInAccountId",
            "loggedInAccountName",
            "activeProjectKey"
        ];
    }

    /**
     * @param string $activeProjectKey
     */
    public function setActiveProjectKey(string $activeProjectKey): void {
        $this->activeProjectKey = $activeProjectKey;
    }


    /**
     * Apply the specified function
     *
     * @param $functionName
     * @param $functionArgs
     * @param $value
     * @param $model
     *
     * @return string
     */
    protected function applyFunctionWithArgs($functionName, $functionArgs, $value, $model) {

        switch ($functionName) {
            case "loggedInAccountId":
                return $this->session->__getLoggedInAccount()?->getAccountId();
            case "loggedInAccountName":
                return $this->session->__getLoggedInAccount()?->getName();
            case "activeProjectKey":
                return $this->activeProjectKey;
        }
    }
}