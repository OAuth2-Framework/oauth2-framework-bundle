<?php

declare(strict_types=1);
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\Middleware\AccessTokenMiddleware;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\Rule\UserinfoEndpointAlgorithmsRule;
use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(UserInfoEndpoint::class)
        ->args([
            ref(IdTokenBuilderFactory::class),
            ref(ClientRepository::class),
            ref(UserAccountRepository::class),
            ref('httplug.message_factory'),
        ])
    ;

    $container->set('oauth2_server.userinfo_security.bearer_token_type')
        ->class(BearerToken::class)
        ->args([
            'Realm', //FIXME
            true,
            true,
            false,
        ])
    ;

    $container->set('oauth2_server.userinfo_security.token_type_manager')
        ->class(TokenTypeManager::class)
        ->call('add', [
            ref('oauth2_server.userinfo_security.bearer_token_type'),
        ])
    ;

    $container->set('userinfo_security_middleware')
        ->class(AccessTokenMiddleware::class)
        ->args([
            ref('oauth2_server.userinfo_security.token_type_manager'),
            ref(AccessTokenRepository::class),
        ])
    ;

    $container->set('oauth2_server_userinfo_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('userinfo_security_middleware'),
            ref(UserInfoEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set(UserinfoEndpointAlgorithmsRule::class)
        ->args([
            ref('jose.jws_builder.oauth2_server.openid_connect.id_token_from_userinfo')->nullOnInvalid(),
            ref('jose.jwe_builder.oauth2_server.openid_connect.id_token_from_userinfo')->nullOnInvalid(),
        ])
    ;
};
