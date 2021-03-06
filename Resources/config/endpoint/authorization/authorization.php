<?php

declare(strict_types=1);
use OAuth2Framework\Component\Core\Middleware\Pipe;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestEntryEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\ConsentPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\LoginPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\NonePrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\SelectAccountPrompt;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Middleware\AuthorizationExceptionMiddleware;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\DisplayParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\PromptParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\RedirectUriParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ResponseTypeParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\StateParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseModeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\RequestUriRule;
use OAuth2Framework\Component\AuthorizationEndpoint\Rule\ResponseTypesRule;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\Middleware;
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestHookCompilerPass;
use OAuth2Framework\ServerBundle\Service\AuthorizationRequestSessionStorage;
use OAuth2Framework\ServerBundle\Service\IgnoreAccountSelectionHandler;
use OAuth2Framework\ServerBundle\Service\RedirectAuthorizationRequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthorizationExceptionMiddleware::class)
        ->args([
            ref(ResponseTypeGuesser::class),
            ref(ResponseModeGuesser::class),
        ])
    ;

    // Controllers and pipes
    $container->set(AuthorizationRequestEntryEndpoint::class)
        ->args([
            ref(ParameterCheckerManager::class),
            ref(AuthorizationRequestLoader::class),
            ref(AuthorizationRequestStorage::class),
            ref(AuthorizationRequestHandler::class),
            ref(UserAccountDiscovery::class),
        ])
    ;

    $container->set('authorization_request_entry_endpoint_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_authorization_endpoint'),
            ref(AuthorizationExceptionMiddleware::class),
            ref(AuthorizationRequestEntryEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set(RedirectAuthorizationRequestHandler::class)
        ->args([
            ref(RouterInterface::class),
            ref(ResponseFactoryInterface::class),
        ])
    ;

    $container->set(AuthorizationEndpoint::class)
        ->args([
            ref(ResponseFactoryInterface::class),
            ref(TokenTypeGuesser::class),
            ref(ResponseTypeGuesser::class),
            ref(ResponseModeGuesser::class),
            ref(ConsentRepository::class)->nullOnInvalid(),
            ref(ExtensionManager::class),
            ref(AuthorizationRequestStorage::class),
            ref(LoginHandler::class),
            ref(ConsentHandler::class),
        ])
    ;

    $container->set('authorization_endpoint_pipe')
        ->class(Pipe::class)
        ->args([[
            ref('oauth2_server.message_middleware.for_authorization_endpoint'),
            ref(AuthorizationExceptionMiddleware::class),
            ref(AuthorizationEndpoint::class),
        ]])
        ->tag('controller.service_arguments')
    ;

    $container->set(UserAuthenticationCheckerManager::class);

    //Hooks
    $container->set(ConsentPrompt::class)
        ->args([
            ref(ConsentHandler::class),
        ])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, ['priority' => -200])
    ;

    $container->set(LoginPrompt::class)
        ->args([
            ref(UserAuthenticationCheckerManager::class),
            ref(LoginHandler::class),
        ])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, ['priority' => -100])
    ;

    $container->set(NonePrompt::class)
        ->args([
            ref(ConsentRepository::class)->nullOnInvalid(),
        ])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, ['priority' => 0])
    ;

    $container->set(SelectAccountPrompt::class)
        ->args([
            ref(SelectAccountHandler::class),
        ])
        ->tag(AuthorizationRequestHookCompilerPass::TAG_NAME, ['priority' => 0])
    ;

    $container->set(AuthorizationRequestSessionStorage::class)
        ->args([
            ref(SessionInterface::class),
        ])
    ;
    $container->set(IgnoreAccountSelectionHandler::class);

    //Authorization Request Loader
    $container->set(AuthorizationRequestLoader::class)
        ->args([
            ref(ClientRepository::class),
        ])
    ;

    // Consent Screen Extension
    $container->set(ExtensionManager::class);

    // Parameter Checker
    $container->set(ParameterCheckerManager::class);

    $container->set(RedirectUriParameterChecker::class)
        ->tag('oauth2_server_authorization_parameter_checker')
    ;
    $container->set(DisplayParameterChecker::class);
    $container->set(PromptParameterChecker::class);
    $container->set(StateParameterChecker::class)
        ->args([
            '%oauth2_server.endpoint.authorization.enforce_state%',
        ])
    ;

    // Rules
    $container->set(RequestUriRule::class);
    $container->set(ResponseTypesRule::class)
        ->args([
            ref(ResponseTypeManager::class),
        ])
    ;

    $container->set('oauth2_server.message_middleware.for_authorization_endpoint')
        ->class(OAuth2MessageMiddleware::class)
        ->args([
            ref('oauth2_server.message_factory_manager.for_authorization_endpoint'),
        ])
    ;

    $container->set('oauth2_server.message_factory_manager.for_authorization_endpoint')
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

    $container->set(ResponseModeGuesser::class)
        ->args([
            ref(ResponseModeManager::class),
            false,
        ])
    ;

    $container->set(ResponseTypeGuesser::class)
        ->args([
            ref(ResponseTypeManager::class),
        ])
    ;

    $container->set(ResponseTypeParameterChecker::class)
        ->args([
            ref(ResponseTypeManager::class),
        ])
    ;
};
