<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use OAuth2Framework\Component\MetadataEndpoint\Metadata;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use Symfony\Component\Routing\RouterInterface;

class MetadataBuilder
{
    private RouterInterface $router;

    private Metadata $metadata;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
        $this->metadata = new Metadata();
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function addRoute(string $name, string $routeName, array $routeParameters = []): void
    {
        $path = $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_URL);
        $this->metadata->set($name, $path);
    }

    public function setCodeChallengeMethodsSupported(PKCEMethodManager $PKCEMethodManager): void
    {
        $this->metadata->set('code_challenge_methods_supported', $PKCEMethodManager->names());
    }

    public function setClientAssertionJwt(ClientAssertionJwt $clientAssertionJwt): void
    {
        $this->metadata->set('token_endpoint_auth_signing_alg_values_supported', $clientAssertionJwt->getSupportedSignatureAlgorithms());
        $this->metadata->set('token_endpoint_auth_encryption_alg_values_supported', $clientAssertionJwt->getSupportedKeyEncryptionAlgorithms());
        $this->metadata->set('token_endpoint_auth_encryption_enc_values_supported', $clientAssertionJwt->getSupportedContentEncryptionAlgorithms());
    }

    public function setGrantTypeManager(GrantTypeManager $grantTypeManager): void
    {
        $this->metadata->set('grant_types_supported', $grantTypeManager->list());
    }

    public function setResponseTypeManager(ResponseTypeManager $responseTypeManager): void
    {
        $this->metadata->set('response_types_supported', $responseTypeManager->list());
    }

    public function setResponseModeManager(ResponseModeManager $responseModeManager): void
    {
        $this->metadata->set('response_modes_supported', $responseModeManager->list());
    }

    public function setTokenEndpointAuthMethodManager(AuthenticationMethodManager $tokenEndpointAuthMethodManager): void
    {
        $this->metadata->set('token_endpoint_auth_methods_supported', $tokenEndpointAuthMethodManager->list());
    }

    public function setScopeRepository(ScopeRepository $scopeRepository): void
    {
        $this->metadata->set('scopes_supported', $scopeRepository->all());
    }

    public function setUserinfo(UserInfo $userInfo): void
    {
        $this->metadata->set('subject_types_supported', $userInfo->isPairwiseSubjectIdentifierSupported() ? ['public', 'pairwise'] : ['public']);
    }

    public function setClaimsSupported(ClaimManager $claimManager): void
    {
        $this->metadata->set('claims_supported', $claimManager->list());
    }

    /**
     * @param null|mixed $value
     */
    public function addKeyValuePair(string $name, $value): void
    {
        $this->metadata->set($name, $value);
    }

    public function setAuthorizationRequestLoader(AuthorizationRequestLoader $authorizationRequestLoader): void
    {
        $requestObjectSupported = $authorizationRequestLoader->isRequestObjectSupportEnabled();
        $this->metadata->set('request_parameter_supported', $requestObjectSupported);
        if ($requestObjectSupported) {
            $this->metadata->set('request_uri_parameter_supported', $authorizationRequestLoader->isRequestObjectReferenceSupportEnabled());
            $this->metadata->set('require_request_uri_registration', $authorizationRequestLoader->isRequestUriRegistrationRequired());
            $this->metadata->set('request_object_signing_alg_values_supported', $authorizationRequestLoader->getSupportedSignatureAlgorithms());
            $this->metadata->set('request_object_encryption_alg_values_supported', $authorizationRequestLoader->getSupportedKeyEncryptionAlgorithms());
            $this->metadata->set('request_object_encryption_enc_values_supported', $authorizationRequestLoader->getSupportedContentEncryptionAlgorithms());
        }
    }
}
