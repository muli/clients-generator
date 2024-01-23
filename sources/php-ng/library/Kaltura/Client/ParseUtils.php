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

use SimpleXMLElement;
use stdClass;

/**
 * @package Kaltura
 * @subpackage Client
 */
class ParseUtils
{
    public static function unmarshalSimpleType(SimpleXMLElement $xml): string
    {
        return (string)$xml;
    }

    public static function unmarshalObject(SimpleXMLElement $xml, ?string $fallbackType = null)
    {
        $objectType = (string)$xml->objectType;
        $type = TypeMap::getZendType($objectType);
        if (!class_exists($type)) {
            $type = TypeMap::getZendType($fallbackType);
            if (!class_exists($type)) {
                throw new ClientException(
                    "Invalid object type class [$type] of Kaltura type [$objectType]",
                    ClientException::ERROR_INVALID_OBJECT_TYPE
                );
            }
        }

        return new $type($xml);
    }

    public static function unmarshalArray(SimpleXMLElement $xml, ?string $fallbackType = null): array
    {
        $xmls = $xml->children();
        $ret = [];

        foreach ($xmls as $childXml) {
            $ret[] = self::unmarshalObject($childXml, $fallbackType);
        }

        return $ret;
    }

    public static function unmarshalMap(SimpleXMLElement $xml, ?string $fallbackType = null): array
    {
        $xmls = $xml->children();
        $ret = [];

        foreach ($xmls as $childXml) {
            $ret[(string)$xml->itemKey] = self::unmarshalObject($childXml, $fallbackType);
        }

        return $ret;
    }

    /**
     * @throws ClientException
     */
    public static function jsObjectToClientObject(mixed $value, ?string $fallbackType = null, bool $isMultiRequest = false): mixed
    {
        if(is_array($value)) {
            foreach($value as &$item) {
                $item = self::jsObjectToClientObject($item);
            }
            unset($item);
        }

        if (is_object($value)) {
            if (isset($value->message, $value->code)) {
                if ($isMultiRequest) {
                    return new ApiException($value->message, $value->code, self::exceptionArgsArrayToObject($value->args));
                }

                throw new ApiException($value->message, $value->code, self::exceptionArgsArrayToObject($value->args));
            }

            if(!isset($value->objectType)) {
                if (isset($value->result)) {
                    $value = self::jsObjectToClientObject($value->result);
                } elseif (isset($value->error)) {
                    self::jsObjectToClientObject($value->error);
                } else {
                    throw new ClientException("Response format not supported - objectType is required for all objects", ClientException::ERROR_FORMAT_NOT_SUPPORTED);
                }
            }

            $objectType = $value->objectType;
            $zendClientClass = TypeMap::getZendType($objectType);
            if(!$zendClientClass && !is_null($fallbackType)) {
                $zendClientClass = TypeMap::getZendType($fallbackType);
            }

            $object = new $zendClientClass(null, $value);
            $attributes = get_object_vars($value);
            foreach ($attributes as $attribute => $attributeValue) {
                if ($attribute === 'objectType' || (!is_array($attributeValue) && !is_object($attributeValue))) {
                    continue;
                }

                if ($attribute === 'relatedObjects') {
                    $object->relatedObjects = [];
                    $objectVars = $attributeValue;
                    if (is_object($attributeValue)) {
                        $objectVars = get_object_vars($attributeValue);
                    }
                    foreach ($objectVars as $key => $relatedObject) {
                        $object->relatedObjects[$key] = self::jsObjectToClientObject($relatedObject);
                    }
                    continue;
                }

                $multiLingualAttribute = 'multiLingual_' . $attribute;
                if (isset($object->{$multiLingualAttribute})) {
                    continue;
                }
                $object->$attribute = self::jsObjectToClientObject($attributeValue);
            }

            $value = $object;
        }

        return $value;
    }

    private static function exceptionArgsArrayToObject(array|stdClass $args): array
    {
        $objectArgs = [];
        foreach($args as $argName => $argValue) {
            $argumentsObject = new stdClass();
            $argumentsObject->name = $argName;
            $argumentsObject->value = $argValue;
            $objectArgs[] = $argumentsObject;
        }

        return $objectArgs;
    }
}
