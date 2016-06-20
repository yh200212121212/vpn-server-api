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

namespace fkooman\VPN\Server\ZeroTier;

use GuzzleHttp\Client;

class ZeroTier
{
    /** @var string */
    private $controllerUrl;

    /** @var string */
    private $controllerId;

    /** @var string */
    private $authToken;

    /** @var GuzzleHttp\Client */
    private $client;

    public function __construct($controllerUrl, $controllerId, $authToken, Client $client = null)
    {
        $this->controllerUrl = $controllerUrl;
        $this->controllerId = $controllerId;
        $this->authToken = $authToken;

        if (is_null($client)) {
            $client = new Client();
        }
        $this->client = $client;
    }

    /**
     * Add a ZeroTier network to the controller for a particular user.
     */
    public function addNetwork($userId, $networkName)
    {
        // generate a new network ID
        $networkId = sprintf('%s%s', $this->controllerId, bin2hex(random_bytes(3)));

        $response = $this->client->post(
            sprintf('%s/controller/network/%s', $this->controllerUrl, $networkId),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-ZT1-Auth' => $this->authToken,
                ],
                'body' => json_encode(
                    [
                        'allowPassiveBridging' => false,
                        'enableBroadcast' => true,
                        'gateways' => [],
                        'ipAssignmentPools' => [
                            'ipRangeEnd' => '10.66.255.254',    // XXX
                            'ipRangeStart' => '10.66.0.1',      // XXX
                        ],
                        'ipLocalRoutes' => [
                            '10.66.0.0/16',                     // XXX
                        ],
                        'multicastLimit' => 32,
                        'name' => $networkName,
                        'private' => false,                     // XXX
                        'relays' => [],
                        'rules' => [
                            [
                                'action' => 'accept',
                                'etherType' => 2048,
                                'ruleNo' => 10,
                            ],
                            [
                                'action' => 'accept',
                                'etherType' => 2054,
                                'ruleNo' => 20,
                            ],
                            [
                                'action' => 'accept',
                                'etherType' => 34525,
                                'ruleNo' => 30,
                            ],
                        ],
                        'v4AssignMode' => 'zt',
                        'v6AssignMode' => 'rfc4193',
                    ]
                ),
            ]
        )->json();

        return $response;
    }

    /**
     * Get the ZeroTier networks for a particular user.
     */
    public function getNetworks($userId)
    {
        $response = $this->client->get(
            sprintf('%s/controller/network', $this->controllerUrl),
            [
                'headers' => [
                    'X-ZT1-Auth' => $this->authToken,
                ],
            ]
        );

        return $response->json();
    }

    /**
     * Add a client to a network.
     */
    public function addClient($userId, $networkId, $clientId)
    {
    }

    /**
     * Remove a client from a network.
     */
    public function removeClient($userId, $networkId, $clientId)
    {
    }

    /**
     * Remove a network.
     */
    public function removeNetwork($userId, $networkId)
    {
    }
}
