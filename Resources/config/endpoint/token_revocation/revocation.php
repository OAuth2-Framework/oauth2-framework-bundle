<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\Middleware\HttpMethodMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_revocation_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_client_authentication'),
            ref('oauth2_server.client_authentication.middleware'),
            ref('token_revocation_method_handler'),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set('token_revocation_method_handler')
        ->class(HttpMethodMiddleware::class)
        ->call('add', ['POST', ref(TokenRevocationPostEndpoint::class)])
        ->call('add', ['GET', ref(TokenRevocationGetEndpoint::class)])
    ;

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenRevocationPostEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref(ResponseFactoryInterface::class),
        ])
    ;

    $container->set(TokenRevocationGetEndpoint::class)
        ->args([
            ref(TokenTypeHintManager::class),
            ref(ResponseFactoryInterface::class),
            '%oauth2_server.endpoint.token_revocation.allow_callback%',
        ])
    ;

    $container->set(AccessTokenRevocationTypeHint::class)
        ->args([
            ref(AccessTokenRepository::class),
        ])
    ;
};
