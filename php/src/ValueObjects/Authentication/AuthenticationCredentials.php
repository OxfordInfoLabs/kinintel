<?php

namespace Kinintel\ValueObjects\Authentication;

/**
 * Authentication credentials interface
 *
 * Interface AuthenticationCredentials
 *
 * @implementation singlekey \Kinintel\ValueObjects\Authentication\Generic\SingleKeyAuthenticationCredentials
 * @implementation accesskeyandsecret \Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials
 * @implementation usernameandpassword \Kinintel\ValueObjects\Authentication\Generic\UsernameAndPasswordAuthenticationCredentials
 * @implementation http-basic \Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials
 * @implementation http-query \Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials
 * @implementation http-headers \Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials
 * @implementation http-substitution \Kinintel\ValueObjects\Authentication\WebService\SubstitutionParameterAuthenticationCredentials
 * @implementation ftp \Kinintel\ValueObjects\Authentication\FTP\FTPAuthenticationCredentials
 *
 * @implementation mysql \Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials
 * @implementation postgresql \Kinintel\ValueObjects\Authentication\SQLDatabase\PostgreSQLAuthenticationCredentials
 * @implementation sqlite \Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials
 *
 * @implementation google-cloud \Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials
 *
 */
interface AuthenticationCredentials {
}