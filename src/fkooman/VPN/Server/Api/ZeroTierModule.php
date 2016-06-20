<?php
/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\VPN\Server\Api;

use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Rest\ServiceModuleInterface;
use fkooman\Rest\Plugin\Authentication\Bearer\TokenInfo;
use fkooman\VPN\Server\ZeroTier\ZeroTier;
use fkooman\VPN\Server\ApiResponse;

class ZeroTierModule implements ServiceModuleInterface
{
    /** @var \fkooman\VPN\Server\ZeroTier\ZeroTier */
    private $zeroTier;

    public function __construct(ZeroTier $zeroTier)
    {
        $this->zeroTier = $zeroTier;
    }

    public function init(Service $service)
    {
        $service->get(
            '/zt/networks',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                return new ApiResponse(
                    'networks',
                    $this->zeroTier->getNetworks($tokenInfo->getUserId())
                );
            }
        );
        $service->post(
            '/zt/networks',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                // XXX obtain name from post params
                $networkName = 'zt_network_name';

                return new ApiResponse(
                    'ok',
                    $this->zeroTier->addNetwork(
                        $tokenInfo->getUserId(),
                        $networkName
                    )
                );
            }
        );
    }
}
