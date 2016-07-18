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

        $addressBytes = [
            hexdec(bin2hex(random_bytes(1))),
            hexdec(bin2hex(random_bytes(1))),
        ];

        $ipLocalRoutes = sprintf('10.%s.%s.0/24', $addressBytes[0], $addressBytes[1]);
        $ipRangeStart = sprintf('10.%s.%s.0', $addressBytes[0], $addressBytes[1]);
        $ipRangeEnd = sprintf('10.%s.%s.127', $addressBytes[0], $addressBytes[1]);

        $body = json_encode(
            [
                'allowPassiveBridging' => false,
                'enableBroadcast' => true,
                'gateways' => [],
                'ipAssignmentPools' => [
                    [
                        'ipRangeEnd' => $ipRangeEnd,
                        'ipRangeStart' => $ipRangeStart,
                    ],
                ],
                'routes' => [
                    ['target' => $ipLocalRoutes, 'via' => null],
                ],
                'multicastLimit' => 32,
                'name' => sprintf('%s_%s', $userId, $networkName),
                'private' => true,
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
        );

        $response = $this->client->post(
            sprintf('%s/controller/network/%s', $this->controllerUrl, $networkId),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-ZT1-Auth' => $this->authToken,
                ],
                'body' => $body,
            ]
        )->json();

        // XXX see if all okay, then return true
        return $networkId;
    }

    public function getAllNetworks()
    {
        $networkIdentifiers = $this->client->get(
            sprintf('%s/controller/network', $this->controllerUrl),
            [
                'headers' => [
                    'X-ZT1-Auth' => $this->authToken,
                ],
            ]
        )->json();

        $responseData = [];
        // XXX this feels quite inefficient!
        foreach ($networkIdentifiers as $networkId) {
            $networkInfo = $this->client->get(
                sprintf('%s/controller/network/%s', $this->controllerUrl, $networkId),
                [
                    'headers' => [
                        'X-ZT1-Auth' => $this->authToken,
                    ],
                ]
            )->json();

            $memberList = [];
            $members = $this->getMembers($networkId);
            foreach ($members as $member) {
                $memberData = $this->getMember($networkId, $member);
                if (false === $memberData['authorized']) {
                    continue;
                }
                $memberList[] = $member;
            }

            list($userId, $networkName) = explode('_', $networkInfo['name'], 2);

            $responseData[] = [
                'members' => $memberList,
                'id' => $networkId,
                'name' => $networkName,
                'user_id' => $userId,
                'ipAssignmentPools' => $networkInfo['ipAssignmentPools'],
            ];
        }

        return $responseData;
    }

    public function getMembers($networkId)
    {
        return array_keys(
            $this->client->get(
                sprintf('%s/controller/network/%s/member', $this->controllerUrl, $networkId),
                [
                    'headers' => [
                        'X-ZT1-Auth' => $this->authToken,
                    ],
                ]
            )->json()
        );
    }

    public function getMember($networkId, $clientId)
    {
        return $this->client->get(
            sprintf('%s/controller/network/%s/member/%s', $this->controllerUrl, $networkId, $clientId),
            [
                'headers' => [
                    'X-ZT1-Auth' => $this->authToken,
                ],
            ]
        )->json();
    }

    /**
     * Get the ZeroTier networks for a particular user.
     */
    public function getNetworks($userId)
    {
        $networks = $this->getAllNetworks();

        $responseData = [];
        foreach ($networks as $network) {
            if ($network['user_id'] === $userId) {
                $responseData[] = $network;
            }
        }

        return $responseData;
    }

    /**
     * Add a client to a network.
     */
    public function addClient($networkId, $clientId)
    {
        $this->client->post(
            sprintf('%s/controller/network/%s/member/%s', $this->controllerUrl, $networkId, $clientId),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-ZT1-Auth' => $this->authToken,
                ],
                'body' => json_encode(
                    [
                        'authorized' => true,
                        'activeBridge' => false,    // XXX
                    ]
                ),
            ]
        )->json();

        // XXX see if all okay, then return true
        return true;
    }

    /**
     * Remove a client from a network.
     */
    public function removeClient($networkId, $clientId)
    {
        $this->client->delete(
            sprintf('%s/controller/network/%s/member/%s', $this->controllerUrl, $networkId, $clientId),
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-ZT1-Auth' => $this->authToken,
                ],
            ]
        )->json();

        // XXX see if all okay, then return true
        return true;
    }

    /**
     * Remove a network.
     */
    public function removeNetwork($networkId)
    {
        $this->client->delete(
            sprintf('%s/controller/network/%s', $this->controllerUrl, $networkId),
            [
                'headers' => [
                    'X-ZT1-Auth' => $this->authToken,
                ],
            ]
        )->json();

        // XXX see if all okay, then return true
        return true;
    }
}
