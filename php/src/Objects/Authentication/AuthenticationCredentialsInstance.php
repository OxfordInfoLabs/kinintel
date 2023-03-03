<?php

namespace Kinintel\Objects\Authentication;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

/**
 * Class AuthenticationCredentialsInstance
 *
 * @table ki_authentication_credentials_instance
 * @generate
 */
class AuthenticationCredentialsInstance extends ActiveRecord {

    /**
     * Credentials key - used as primary key
     *
     * @var string
     * @primaryKey
     */
    private $key;


    /**
     * The type of credentials being referenced if not referencing by key.  Can be an implementation key or a
     * direct path to a fully qualified class.
     *
     * @var string
     */
    private $type;


    /**
     * Inline credentials config if not referencing instance by key.  Should be valid
     * config for the supplied type.
     *
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    private $config;

    /**
     * AuthenticationCredentialsInstance constructor.
     * @param string $key
     * @param string $type
     * @param mixed $config
     */
    public function __construct($key, $type, $config = []) {
        $this->key = $key;
        $this->type = $type;
        $this->config = $config;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }


    /**
     * Return credentials object for this instance
     *
     * @return AuthenticationCredentials
     */
    public function returnCredentials() {

        /**
         * @var ObjectBinder $objectBinder
         */
        $objectBinder = Container::instance()->get(ObjectBinder::class);

        try {
            $credentialsClass = Container::instance()->getInterfaceImplementationClass(AuthenticationCredentials::class, $this->getType());
            if ($this->getConfig()) {
                return $objectBinder->bindFromArray($this->getConfig(), $credentialsClass);
            } else {
                return Container::instance()->new($credentialsClass);
            }

        } catch (MissingInterfaceImplementationException $e) {

            throw new InvalidDatasourceAuthenticationCredentialsException(
                ["authenticationCredentials" => [
                    "type" => new FieldValidationError("type", "unknowntype", "Authentication credentials of type '$credentialsType' does not exist")
                ]
                ]);
        }

    }

}