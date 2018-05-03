<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle;

use OAuth2Framework\ServerBundle\DependencyInjection\OAuth2FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OAuth2FrameworkServerBundle extends Bundle
{
    /**
     * @var Component\Component[]
     */
    private $components = [];

    /**
     * JoseFrameworkBundle constructor.
     */
    public function __construct()
    {
        foreach ($this->getComponents() as $component) {
            $this->components[$component->name()] = $component;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new OAuth2FrameworkExtension('oauth2_server', $this->components);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        foreach ($this->components as $component) {
            $component->build($container);
        }
    }

    /**
     * @return Component\Component[]
     */
    private function getComponents(): array
    {
        return [
            new Component\Core\TrustedIssuerSource(),
            new Component\Core\ClientSource(),
            new Component\Core\AccessTokenSource(),
            new Component\Core\UserAccountSource(),
            new Component\Core\ServicesSource(),
            new Component\Core\ResourceServerSource(),
            new Component\ClientRule\ClientRuleSource(),
            new Component\ClientAuthentication\ClientAuthenticationSource(),

            new Component\Scope\ScopeSource(),
            new Component\TokenType\TokenTypeSource(),
            new Component\Endpoint\EndpointSource(),
            new Component\Grant\GrantSource(),
            new Component\OpenIdConnect\OpenIdConnectSource(),

            /*
            new Component\HttpSource(),
            new Component\KeySet(),*/
        ];
    }
}
