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

/**
 * @package Kaltura
 * @subpackage Client
 */
class ServiceActionCall
{
    /**
     * Contruct new Kaltura service action call, if params array contain sub arrays (for objects), it will be flattened
     */
    public function __construct(
        public string $service,
        public string $action,
        public array $params = [],
        public array $files = []
    ) {
        $this->params = $this->parseParams($params);
    }

    /**
     * Parse params array and sub arrays (for objects)
     */
    public function parseParams(array $params): array
    {
        $newParams = [];
        foreach ($params as $key => $val) {
            $newParams[$key] = is_array($val)
                ? $this->parseParams($val)
                : $val;
        }

        return $newParams;
    }

    /**
     * Return the parameters for a multi request
     */
    public function getParamsForMultiRequest(int $multiRequestIndex): array
    {
        $multiRequestParams = [];
        $multiRequestParams[$multiRequestIndex]['service'] = $this->service;
        $multiRequestParams[$multiRequestIndex]['action'] = $this->action;
        foreach ($this->params as $key => $val) {
            $multiRequestParams[$multiRequestIndex][$key] = $val;
        }

        return $multiRequestParams;
    }

    /**
     * Return the parameters for a multi request
     */
    public function getFilesForMultiRequest(int $multiRequestIndex): array
    {
        $multiRequestParams = [];
        foreach ($this->files as $key => $val) {
            $multiRequestParams["$multiRequestIndex:$key"] = $val;
        }

        return $multiRequestParams;
    }
}
