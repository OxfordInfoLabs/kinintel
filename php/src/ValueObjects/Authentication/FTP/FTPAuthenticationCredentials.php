<?php


namespace Kinintel\ValueObjects\Authentication\FTP;


use Kinintel\ValueObjects\Authentication\Generic\UsernameAndPasswordAuthenticationCredentials;

/**
 * FTP Authentication credentials can also include a private key where cert auth is in use
 *
 * Class FTPAuthenticationCredentials
 * @package Kinintel\ValueObjects\Authentication\FTP
 */
class FTPAuthenticationCredentials extends UsernameAndPasswordAuthenticationCredentials {

    /**
     * @var string
     */
    private $privateKey;

    /**
     * SingleKeyAuthenticationCredentials constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password = null, $privateKey = null) {
        parent::__construct($username, $password);
        $this->privateKey = $privateKey;
    }


    /**
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey($privateKey) {
        $this->privateKey = $privateKey;
    }


}