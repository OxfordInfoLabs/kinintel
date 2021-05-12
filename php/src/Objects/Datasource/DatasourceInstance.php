<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\DependencyInjection\MissingInterfaceImplementationException;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceTypeException;
use Kinintel\Services\Authentication\AuthenticationCredentialsService;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

/**
 * Data source instance - can be stored in database table
 *
 * @table ki_datasource_instance
 * @generate
 */
class DatasourceInstance extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $key;

    /**
     * Descriptive title for this data source instance
     *
     * @var string
     * @required
     */
    private $title;

    /**
     * Type for this data source - can either be a mapping implementation key
     * or a fully qualified class path
     *
     * @var string
     */
    private $type;

    /**
     * Config for the data source - should match the required format for
     * the configuration for the data source type.
     *
     * @var mixed
     * @json
     */
    private $config;


    /**
     * Key to credentials instance if referencing a shared credentials object
     *
     * @var string
     */
    private $credentialsKey;


    /**
     * The type of credentials being referenced if not referencing by key.  Can be an implementation key or a
     * direct path to a fully qualified class.
     *
     * @var string
     */
    private $credentialsType;


    /**
     * Inline credentials config if not referencing instance by key.  Should be valid
     * config for the supplied type.
     *
     * @var mixed
     * @json
     */
    private $credentialsConfig;

    /**
     * DatasourceInstance constructor.
     *
     * @param string $key
     * @param string $title
     * @param string $type
     * @param mixed $config
     * @param string $credentialsKey
     * @param string $credentialsType
     * @param mixed $credentialsConfig
     */
    public function __construct($key, $title, $type, $config = [], $credentialsKey = null, $credentialsType = null, $credentialsConfig = []) {
        $this->key = $key;
        $this->title = $title;
        $this->type = $type;
        $this->config = $config;
        $this->credentialsKey = $credentialsKey;
        $this->credentialsType = $credentialsType;
        $this->credentialsConfig = $credentialsConfig;
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
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
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
     * @return string
     */
    public function getCredentialsKey() {
        return $this->credentialsKey;
    }

    /**
     * @param string $credentialsKey
     */
    public function setCredentialsKey($credentialsKey) {
        $this->credentialsKey = $credentialsKey;
    }

    /**
     * @return string
     */
    public function getCredentialsType() {
        return $this->credentialsType;
    }

    /**
     * @param string $credentialsType
     */
    public function setCredentialsType($credentialsType) {
        $this->credentialsType = $credentialsType;
    }

    /**
     * @return mixed
     */
    public function getCredentialsConfig() {
        return $this->credentialsConfig;
    }

    /**
     * @param mixed $credentialsConfig
     */
    public function setCredentialsConfig($credentialsConfig) {
        $this->credentialsConfig = $credentialsConfig;
    }


    /**
     * Return a fully configured data source or throw appropriate validation exceptions
     *
     * @return BaseDatasource
     * @throws ValidationException
     */
    public function returnDataSource() {

        /**
         * @var ObjectBinder $objectBinder
         */
        $objectBinder = Container::instance()->get(ObjectBinder::class);

        $credentials = null;
        $credentialsType = $this->getCredentialsType();
        $credentialsConfig = $this->getCredentialsConfig();

        if ($this->getCredentialsKey()) {
            /**
             * @var AuthenticationCredentialsService $credentialsService
             */
            $credentialsService = Container::instance()->get(AuthenticationCredentialsService::class);
            $credentialsInstance = $credentialsService->getCredentialsInstanceByKey($this->getCredentialsKey());
            $credentialsType = $credentialsInstance->getType();
            $credentialsConfig = $credentialsInstance->getConfig();

        }

        // If credentials type, create and populate the appropriate object
        if ($credentialsType) {
            try {
                $credentialsClass = Container::instance()->getInterfaceImplementationClass(AuthenticationCredentials::class, $credentialsType);
                if ($credentialsConfig) {
                    $credentials = $objectBinder->bindFromArray($credentialsConfig, $credentialsClass);
                } else {
                    $credentials = Container::instance()->new($credentialsClass);
                }

            } catch (MissingInterfaceImplementationException $e) {
                throw new InvalidDatasourceAuthenticationCredentialsException(
                    ["authenticationCredentials" => [
                        "type" => new FieldValidationError("type", "unknowntype", "Authentication credentials of type '$credentialsType' does not exist")
                    ]
                    ]);
            }
        }

        // Attempt to grab a data source using the supplied type
        try {
            $dataSourceClass = Container::instance()->getInterfaceImplementationClass(Datasource::class, $this->type);

            /**
             * @var BaseDatasource $dataSource
             */
            $dataSource = Container::instance()->new($dataSourceClass);

            $config = null;
            if ($dataSource->getConfigClass()) {
                $config = $objectBinder->bindFromArray($this->config ?? [], $dataSource->getConfigClass());
                $dataSource->setConfig($config);
            }
            if ($credentials) {
                $dataSource->setAuthenticationCredentials($credentials);
            }


            return $dataSource;

        } catch (MissingInterfaceImplementationException $e) {
            throw new InvalidDatasourceTypeException($this->type);
        }


    }

}