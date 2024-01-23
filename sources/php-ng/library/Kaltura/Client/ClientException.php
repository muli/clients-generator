<?php
// ===================================================================================================
//                           _  __     _ _
//                          | |/ /__ _| | |_ _  _ _ _ __ _
//                          | ' </ _` | |  _| || | '_/ _` |
//                          |_|\_\__,_|_|\__|\_,_|_| \__,_|
//
// This file is part of the Kaltura Collaborative Media Suite which allows users
// to do with audio, video, and animation what Wiki platfroms allow them to do with
// text.
//
// Copyright (C) 2006-2023  Kaltura Inc.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// @ignore
// ===================================================================================================

/**
 * @namespace
 */

namespace Kaltura\Client;

use Exception;

/**
 * Thrown when for client library errors
 *
 * @package Kaltura
 * @subpackage Client
 */
class ClientException extends Exception
{
    public const ERROR_GENERIC = -1;
    public const ERROR_UNSERIALIZE_FAILED = -2;
    public const ERROR_FORMAT_NOT_SUPPORTED = -3;
    public const ERROR_UPLOAD_NOT_SUPPORTED = -4;
    public const ERROR_CONNECTION_FAILED = -5;
    public const ERROR_READ_FAILED = -6;
    public const ERROR_INVALID_PARTNER_ID = -7;
    public const ERROR_INVALID_OBJECT_TYPE = -8;
    public const ERROR_INVALID_OBJECT_FIELD = -9;
    public const ERROR_DOWNLOAD_NOT_SUPPORTED = -10;
    public const ERROR_DOWNLOAD_IN_MULTIREQUEST = -11;
    public const ERROR_ACTION_IN_MULTIREQUEST = -12;
    public const ERROR_INVALID_ENUM_VALUE = -13;
    public const ERROR_CURL_MUST_BE_ENABLED = -20;
}
