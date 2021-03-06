<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientConfigurationEndpoint\ClientConfigurationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Message;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\ServerBundle\Controller\ClientConfigurationMiddleware;
use OAuth2Framework\ServerBundle\Rule\ClientConfigurationRouteRule;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
    ;

    $container->set('client_configuration_endpoint_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_client_configuration'),
            ref('oauth2_server.client_configuration.middleware'),
            ref(ClientConfigurationEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set('oauth2_server.client_configuration.bearer_token')
        ->class(BearerToken::class)
        ->args([
            '%oauth2_server.endpoint.client_configuration.realm%',
            true,  // Authorization Header
            false, // Request Body
            false, // Query String
        ])
    ;

    $container->set(ClientConfigurationEndpoint::class)
        ->args([
            ref(ClientRepository::class),
            ref('oauth2_server.client_configuration.bearer_token'),
            ref(ResponseFactoryInterface::class), //TODO: change the way the response factory is managed
            ref(RuleManager::class),
        ])
    ;

    $container->set('oauth2_server.client_configuration.middleware')
        ->class(ClientConfigurationMiddleware::class)
        ->args([
            ref(ClientRepository::class),
        ])
    ;

    $container->set(ClientConfigurationRouteRule::class)
        ->autoconfigure()
        ->args([
            ref('router'),
        ])
    ;

    $container->set('oauth2_server.message_middleware.for_client_configuration')
        ->class(OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_client_configuration'),
        ])
    ;
    $container->set('oauth2_server.message_factory_manager.for_client_configuration')
        ->class(OAuth2MessageFactoryManager::class)
        ->args([
            ref(ResponseFactoryInterface::class),
        ])
        ->call('addFactory', [ref('oauth2_server.message_factory.303')])
        ->call('addFactory', [ref('oauth2_server.message_factory.400')])
        ->call('addFactory', [ref('oauth2_server.message_factory.403')])
        ->call('addFactory', [ref('oauth2_server.message_factory.405')])
        ->call('addFactory', [ref('oauth2_server.message_factory.501')])
    ;
};
