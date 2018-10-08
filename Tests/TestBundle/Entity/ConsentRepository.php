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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository as ConsentRepositoryInterface;

class ConsentRepository implements ConsentRepositoryInterface
{
    public function hasConsentBeenGiven(AuthorizationRequest $authorizationRequest): bool
    {
        return true;
    }
}