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
use fkooman\VPN\Server\ZeroTier\ClientDb;
use fkooman\VPN\Server\ApiResponse;
use fkooman\VPN\Server\InputValidation;

class ZeroTierModule implements ServiceModuleInterface
{
    /** @var \fkooman\VPN\Server\ZeroTier\ZeroTier */
    private $zeroTier;

    /** @var \fkooman\VPN\Server\ZeroTier\ClientDb */
    private $clientDb;

    public function __construct(ZeroTier $zeroTier, ClientDb $clientDb)
    {
        $this->zeroTier = $zeroTier;
        $this->clientDb = $clientDb;
    }

    public function init(Service $service)
    {
        $service->get(
            '/zt/networks',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                $userId = $request->getUrl()->getQueryParameter('user_id');
                InputValidation::userId($userId);

                $networks = $this->zeroTier->getNetworks($userId);
                $mapping = $this->clientDb->getMapping();

                $networkList = [];
                foreach($networks as $network) {
                    if(array_key_exists($network['id'], $mapping)) {
                        $network['group_id'] = $mapping[$network['id']];
                    } else {
                        $network['group_id'] = null;
                    }
                    $networkList[] = $network;
                }
                    
                return new ApiResponse(
                    'networks',
                    $networkList
                );
            }
        );

        // get the networks for which this user is a guest
        $service->get(
            '/zt/networks/guest',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                $userId = $request->getUrl()->getQueryParameter('user_id');
                InputValidation::userId($userId);

                $networks = $this->zeroTier->getNetworks($userId);
                $mapping = $this->clientDb->getMapping();

                $networkList = [];
                foreach($networks as $network) {
                    if(array_key_exists($network['id'], $mapping)) {
                        $network['group_id'] = $mapping[$network['id']];
                    } else {
                        $network['group_id'] = null;
                    }
                    $networkList[] = $network;
                }
                    
                return new ApiResponse(
                    'networks',
                    $networkList
                );
            }
        );

        $service->delete(
            '/zt/networks/:networkId',
            function (Request $request, TokenInfo $tokenInfo, $networkId) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                InputValidation::networkId($networkId);

                return new ApiResponse(
                    'ok',
                    $this->zeroTier->removeNetwork(
                        $networkId
                    )
                );
            }
        );

        $service->post(
            '/zt/networks',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                $userId = $request->getPostParameter('user_id');
                InputValidation::userId($userId);
                $groupId = $request->getPostParameter('group_id');
                //InputValidation::groupId($groupId);
                $networkName = $request->getPostParameter('network_name');
                InputValidation::networkName($networkName);

                $networkId = $this->zeroTier->addNetwork(
                    $userId,
                    $networkName
                );

                $this->clientDb->mapping($networkId, $groupId);

                return new ApiResponse(
                    'network_id',
                    $networkId
                );
            }
        );

#        $service->post(
#            '/zt/networks/:networkId/member',
#            function (Request $request, TokenInfo $tokenInfo, $networkId) {
#                // XXX scope
#                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

#                InputValidation::networkId($networkId);
#                $clientId = $request->getPostParameter('client_id');
#                InputValidation::clientId($clientId);

#                return new ApiResponse(
#                    'ok',
#                    $this->zeroTier->addClient(
#                        $networkId,
#                        $clientId
#                    )
#                );
#            }
#        );

#        $service->delete(
#            '/zt/networks/:networkId/member/:clientId',
#            function (Request $request, TokenInfo $tokenInfo, $networkId, $clientId) {
#                // XXX scope
#                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

#                InputValidation::networkId($networkId);
#                InputValidation::clientId($clientId);

#                return new ApiResponse(
#                    'ok',
#                    $this->zeroTier->addClient(
#                        $networkId,
#                        $clientId
#                    )
#                );
#            }
#        );

        // register new client ID for user
        $service->post(
            '/zt/client',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                $userId = $request->getPostParameter('user_id');
                InputValidation::userId($userId);
                $clientId = $request->getPostParameter('client_id');
                InputValidation::clientId($clientId);

                return new ApiResponse(
                    'ok',
                    $this->clientDb->register(
                        $userId,
                        $clientId
                    )
                );
            }
        );

        // get client ID(s) for user
        $service->get(
            '/zt/client',
            function (Request $request, TokenInfo $tokenInfo) {
                // XXX scope
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                $userId = $request->getUrl()->getQueryParameter('user_id');
                InputValidation::userId($userId);

                return new ApiResponse(
                    'clients',
                    $this->clientDb->get(
                        $userId
                    )
                );
            }
        );
    }
}
