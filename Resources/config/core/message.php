<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\Message\Factory\AccessDeniedResponseFactory;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\Message\Factory\AuthenticateResponseForTokenFactory;
use OAuth2Framework\Component\Core\Message\Factory\AuthenticateResponseForClientFactory;
use OAuth2Framework\Component\Core\Message\Factory\BadRequestResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\MethodNotAllowedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\NotImplementedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\RedirectResponseFactory;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set('oauth2_server.message_middleware.for_client_authentication')
        ->class(OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_client_authentication'),
        ])
    ;
    $container->set('oauth2_server.message_factory_manager.for_client_authentication')
        ->class(OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
    ;

    $container->set('oauth2_server.message_middleware.for_token_authentication')
        ->class(OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_token_authentication'),
        ])
    ;
    $container->set('oauth2_server.message_factory_manager.for_token_authentication')
        ->class(OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
    ;

    //Factories
    $container->set('oauth2_server.message_factory.403')
        ->class(AccessDeniedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.401_for_token')
        ->args([
            ref(TokenTypeManager::class),
        ])
        ->class(AuthenticateResponseForTokenFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
    ;

    $container->set('oauth2_server.message_factory.401_for_client')
        ->args([
            ref(AuthenticationMethodManager::class),
        ])
        ->class(AuthenticateResponseForClientFactory::class)
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.400')
        ->class(BadRequestResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.405')
        ->class(MethodNotAllowedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.501')
        ->class(NotImplementedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.303')
        ->class(RedirectResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;
};
