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

use ArrayAccess;
use Stringable;

/**
 * @package Kaltura
 * @subpackage Client
 */
class MultiRequestSubResult implements ArrayAccess, Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return '{' . $this->value . '}';
    }

    public function __get($name)
    {
        return new MultiRequestSubResult($this->value . ':' . $name);
    }

    public function __set(string $name, mixed $value)
    {}

    public function __isset(string $name)
    {}

    public function offsetExists($offset): bool
    {
        return true;
    }

    public function offsetGet($offset): MultiRequestSubResult
    {
        return new MultiRequestSubResult($this->value . ':' . $offset);
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }
}
