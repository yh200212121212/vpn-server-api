<?php
/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
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

namespace fkooman\VPN\Server;

use fkooman\Http\Exception\BadRequestException;

class InputValidation
{
    const COMMON_NAME_PATTERN = '/^[a-zA-Z0-9-_.@]+$/';
    const USER_ID_PATTERN = '/^[a-zA-Z0-9-_.@]+$/';
    const DATE_PATTERN = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
    const OTP_PATTERN = '/^[0-9]{6}$/';

    public static function commonName($commonName)
    {
        if (0 === preg_match(self::COMMON_NAME_PATTERN, $commonName)) {
            throw new BadRequestException('invalid value for "common_name"');
        }
        if ('..' === $commonName) {
            throw new BadRequestException('"common_name" cannot be ".."');
        }
    }

    public static function userId($userId)
    {
        if (0 === preg_match(self::USER_ID_PATTERN, $userId)) {
            throw new BadRequestException('invalid value for "user_id"');
        }
        if ('..' === $userId) {
            throw new BadRequestException('"user_id" cannot be ".."');
        }
    }

    public static function date($date)
    {
        if (0 === preg_match(self::DATE_PATTERN, $date)) {
            throw new BadRequestException('invalid value for "date"');
        }
    }

    public static function disable($disable)
    {
        if (!is_bool($disable)) {
            throw new BadRequestException('"disable" must be boolean');
        }
    }

    public static function otp($otpValue)
    {
        if (0 === preg_match(self::OTP_PATTERN, $otpValue)) {
            throw new BadRequestException('invalid value for "otp"');
        }
    }
}
