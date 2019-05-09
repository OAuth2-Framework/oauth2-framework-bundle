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

namespace OAuth2Framework\ServerBundle\Routing;

use function Safe\sprintf;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    /**
     * @var RouteCollection
     */
    private $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * @param string   $name         The name of the route
     * @param string   $controllerId The controller service ID
     * @param string   $methodName   The controller method name
     * @param string   $path         The path pattern to match
     * @param array    $defaults     An array of default parameter values
     * @param array    $requirements An array of requirements for parameters (regexes)
     * @param array    $options      An array of options
     * @param string   $host         The host pattern to match
     * @param string[] $schemes      A required URI scheme or an array of restricted schemes
     * @param string[] $methods      A required HTTP method or an array of restricted methods
     * @param string   $condition    A condition that should evaluate to true for the route to match
     */
    public function addRoute($name, $controllerId, $methodName, $path, array $defaults = [], array $requirements = [], array $options = [], $host = '', $schemes = [], $methods = [], $condition = ''): void
    {
        $defaults['_controller'] = sprintf('%s:%s', $controllerId, $methodName);
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $this->routes->add(sprintf('oauth2_server_%s', $name), $route);
    }

    public function load($resource, $type = null): RouteCollection
    {
        return $this->routes;
    }

    public function supports($resource, $type = null): bool
    {
        return 'oauth2_server' === $type;
    }

    public function getResolver(): ?LoaderResolverInterface
    {
        return null;
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
    }
}
