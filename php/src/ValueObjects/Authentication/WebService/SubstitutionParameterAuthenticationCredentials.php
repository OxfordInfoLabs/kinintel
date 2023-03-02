<?php


namespace Kinintel\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Request;

class SubstitutionParameterAuthenticationCredentials implements WebServiceCredentials {

    /**
     * @var string[]
     * @required
     */
    private $authParams;


    /**
     * QueryParameterAuthenticationCredentials constructor.
     *
     * @param string[] $authParams
     */
    public function __construct($authParams = []) {
        $this->authParams = $authParams;
    }

    /**
     * @return string[]
     */
    public function getAuthParams() {
        return $this->authParams;
    }

    /**
     * @param string[] $authParams
     */
    public function setAuthParams($authParams) {
        $this->authParams = $authParams;
    }


    /**
     * @param Request $request
     *
     * @return Request|void
     */
    public function processRequest($request) {

        // Substitute in URL
        $request->setUrl($this->substituteParams($request->getUrl()));
        $request->setPayload($this->substituteParams($request->getPayload()));

        if ($request->getHeaders()) {
            foreach ($request->getHeaders()->getHeaders() as $key => $value) {
                $request->getHeaders()->set($key, $this->substituteParams($value));
            }
        }

        return $request;
    }


    // Return text with param substitutions
    private function substituteParams($text) {
        foreach ($this->authParams as $key => $value) {
            $text = str_replace("[[" . $key . "]]", $value, $text ?? "");
        }
        return $text;
    }
}