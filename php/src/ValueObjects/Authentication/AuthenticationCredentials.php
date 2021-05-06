<?php

namespace Kinintel\ValueObjects\Authentication;

/**
 * Authentication credentials interface
 *
 * Interface AuthenticationCredentials
 *
 * @implementation singlekey \Kinintel\ValueObjects\Authentication\Generic\SingleKeyAuthenticationCredentials
 * @implementation accesskeyandsecret \Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials
 * @implementation http-basic \Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials
 * @implementation http-query \Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials
 */
interface AuthenticationCredentials {
}