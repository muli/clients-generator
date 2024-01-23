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
 * Thrown for API server errors
 *
 * @package Kaltura
 * @subpackage Client
 */
class ApiException extends \RuntimeException
{
    /** @var ApiExceptionArg[] */
    private ?array $arguments = [];

    public function __construct(string $message, string $code, array $arguments = null)
    {
        $this->code = $code; // force property set here because php expect code to be integer, and it cannot pass in the constructor

        if ($arguments) {
            foreach ($arguments as $argument) {
                /* @var $argument ApiExceptionArg */
                $this->arguments[$argument->name] = $argument->value;
            }
        }

        parent::__construct($message);
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string|int $argument
     * @return string|null
     */
    public function getArgument(string|int $argument): ?string
    {
        return $this->arguments[$argument] ?? null;
    }
}
