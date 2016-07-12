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

use PDO;
use PDOException;

/**
 * Create a link between the user and their ZeroTier client identifiers.
 */
class ClientDb
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $prefix;

    public function __construct(PDO $db, $prefix = '')
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function register($userId, $clientId)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (
                    user_id,
                    client_id
                 ) 
                 VALUES(
                    :user_id, 
                    :client_id
                 )',
                $this->prefix.'zt_clients'
            )
        );

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    public function getUsers()
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT DISTINCT user_id
                 FROM %s',
                $this->prefix.'zt_clients'
            )
        );
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $userList = [];
        foreach ($result as $r) {
            $userList[] = $r['user_id'];
        }

        return array_values($userList);
    }

    public function get($userId)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT client_id 
                 FROM %s 
                 WHERE 
                    user_id = :user_id',
                $this->prefix.'zt_clients'
            )
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $clientList = [];
        foreach ($result as $r) {
            $clientList[] = $r['client_id'];
        }

        return array_values($clientList);
    }

    public function mapping($networkId, $groupId)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (
                    network_id,
                    group_id
                 ) 
                 VALUES(
                    :network_id, 
                    :group_id
                 )',
                $this->prefix.'zt_mapping'
            )
        );

        $stmt->bindValue(':network_id', $networkId, PDO::PARAM_STR);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    public function getMapping()
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT network_id, group_id 
                 FROM %s',
                $this->prefix.'zt_mapping'
            )
        );
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mapping = [];
        foreach ($result as $r) {
            $mapping[$r['network_id']] = $r['group_id'];
        }

        return $mapping;
    }

    public static function createTableQueries($prefix)
    {
        $query = [
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    user_id VARCHAR(255) NOT NULL,
                    client_id VARCHAR(255) NOT NULL,
                    UNIQUE(user_id, client_id)
                )',
                $prefix.'zt_clients'
            ),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (
                    network_id VARCHAR(255) NOT NULL,
                    group_id VARCHAR(255) NOT NULL,
                    UNIQUE(network_id, group_id)
                )',
                $prefix.'zt_mapping'
            ),
        ];

        return $query;
    }

    public function initDatabase()
    {
        $queries = self::createTableQueries($this->prefix);
        foreach ($queries as $q) {
            $this->db->query($q);
        }
    }
}
