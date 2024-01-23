<?php
// ===================================================================================================
//						   _  __	 _ _
//						  | |/ /__ _| | |_ _  _ _ _ __ _
//						  | ' </ _` | |  _| || | '_/ _` |
//						  |_|\_\__,_|_|\__|\_,_|_| \__,_|
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

use CURLFile;
use JsonException;
use Kaltura\Client\Type\RequestConfiguration;
use ReflectionClass;
use SimpleXMLElement;

/**
 * @package Kaltura
 * @subpackage Client
 */
class Base
{
    private const SUPPORTED_FORMATS = [
        self::KALTURA_SERVICE_FORMAT_JSON,
        self::KALTURA_SERVICE_FORMAT_XML,
    ];

    // KS V2 constants
    public const RANDOM_SIZE = 16;

    public const FIELD_EXPIRY = '_e';
    public const FIELD_TYPE = '_t';
    public const FIELD_USER = '_u';

    public const KALTURA_SERVICE_FORMAT_JSON = 1;
    public const KALTURA_SERVICE_FORMAT_XML = 2;
    public const KALTURA_SERVICE_FORMAT_PHP = 3;

    protected array $clientConfiguration = [];

    protected array $requestConfiguration = [];

    private bool $shouldLog = false;

    /**
     * @var array|null of classes
     */
    private ?array $multiRequestReturnType = null;

    /**
     * @var ServiceActionCall[]
     */
    private array $callsQueue = [];

    /**
     * @var string[] of response headers
     */
    private array $responseHeaders = [];

    /**
     * Kaltura client constructor
     */
    public function __construct(protected Configuration $config)
    {
        $logger = $this->config->getLogger();
        if ($logger) {
            $this->shouldLog = true;
        }
    }

    /* Store response headers into array */
    public function readHeader($ch, $string): int
    {
        $this->responseHeaders[] = $string;
        return strlen($string);
    }

    /* Retrieve response headers */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function getServeUrl(): ?string
    {
        if (count($this->callsQueue) !== 1) {
            return null;
        }

        $params = [];
        $this->log("service url: [" . $this->config->getServiceUrl() . "]");

        // append the basic params
        $this->addParam($params, "format", $this->config->getFormat());

        foreach ($this->clientConfiguration as $param => $value) {
            $this->addParam($params, $param, $value);
        }

        $call = $this->callsQueue[0];
        $this->callsQueue = [];

        $params = array_merge($params, $call->params);
        $signature = $this->signature($params);
        $this->addParam($params, "kalsig", $signature);

        $url = $this->config->getServiceUrl() . "/api_v3/service/{$call->service}/action/{$call->action}";
        $url .= '?' . http_build_query($params);
        $this->log("Returned url [$url]");
        return $url;
    }

    public function queueServiceActionCall($service, $action, $returnType, $params = [], $files = []): void
    {
        foreach ($this->requestConfiguration as $param => $value) {
            $this->addParam($params, $param, $value);
        }

        $call = new ServiceActionCall($service, $action, $params, $files);
        if (!is_null($this->multiRequestReturnType)) {
            $this->multiRequestReturnType[] = $returnType;
        }
        $this->callsQueue[] = $call;
    }

    protected function resetRequest(): void
    {
        $this->multiRequestReturnType = null;
        $this->callsQueue = [];
    }

    /**
     * Call all API service that are in queue
     *
     * @throws ClientException
     */
    public function doQueue(): ?string
    {
        if (count($this->callsQueue) === 0) {
            $this->resetRequest();
            return null;
        }

        $startTime = microtime(true);

        $params = [];
        $files = [];
        $this->log("service url: [" . $this->config->getServiceUrl() . "]");

        // append the basic params
        $this->addParam($params, "format", $this->config->getFormat());
        $this->addParam($params, "ignoreNull", true);

        foreach ($this->clientConfiguration as $param => $value) {
            $this->addParam($params, $param, $value);
        }

        $url = $this->config->getServiceUrl() . "/api_v3/service";
        if ($this->multiRequestReturnType !== null) {
            $url .= "/multirequest";
            $i = 0;
            foreach ($this->callsQueue as $call) {
                $callParams = $call->getParamsForMultiRequest($i);
                $callFiles = $call->getFilesForMultiRequest($i);
                $params[] = $callParams;
                $files[] = $callFiles;
                $i++;
            }
            $params = array_merge([], ...$params);
            $files = array_merge([], ...$files);
        } else {
            $call = $this->callsQueue[0];
            $url .= "/{$call->service}/action/{$call->action}";
            $params = array_merge($params, $call->params);
            $files = $call->files;
        }

        // reset
        $this->callsQueue = [];

        $signature = $this->signature($params);
        $this->addParam($params, "kalsig", $signature);

        [$postResult, $errorCode, $error] = $this->doHttpRequest($url, $params, $files);

        if ($error || ($errorCode !== 200)) {
            $error .= ". RC : $errorCode";
            $this->resetRequest();
            throw $this->getClientException($error, ClientException::ERROR_GENERIC);
        }

        // print server debug info to log
        $serverName = null;
        $serverSession = null;
        foreach ($this->responseHeaders as $curHeader) {
            $splitHeader = explode(':', $curHeader, 2);
            if (strtolower($splitHeader[0]) === 'x-me') {
                $serverName = trim($splitHeader[1]);
            } elseif (strtolower($splitHeader[0]) === 'x-kaltura-session') {
                $serverSession = trim($splitHeader[1]);
            }
        }
        if ($serverName !== null || $serverSession !== null) {
            $this->log("server: [{$serverName}], session: [{$serverSession}]");
        }

        $this->log("result (serialized): " . $postResult);

        if (!in_array($this->config->getFormat(), self::SUPPORTED_FORMATS)) {
            $this->resetRequest();
            throw $this->getClientException(
                "unsupported format: $postResult",
                ClientException::ERROR_FORMAT_NOT_SUPPORTED
            );
        }

        $this->resetRequest();

        $endTime = microtime(true);

        $this->log("execution time for [" . $url . "]: [" . ($endTime - $startTime) . "]");

        return $postResult;
    }

    /**
     * Sorts array recursively
     */
    protected function ksortRecursive(array &$array, int $flags = SORT_REGULAR): void
    {
        ksort($array, $flags);
        foreach ($array as &$arr) {
            if (is_array($arr)) {
                $this->ksortRecursive($arr, $flags);
            }
        }
    }

    /**
     * Sign array of parameters
     */
    private function signature(array $params): string
    {
        $this->ksortRecursive($params);
        return md5($this->jsonEncode($params));
    }

    /**
     * Send http request by using curl (if available) or php stream_context
     *
     * @return array of result, error code and error
     * @throws ClientException
     */
    private function doHttpRequest(string $url, array $params = [], array $files = []): array
    {
        if (!function_exists('curl_init')) {
            throw $this->getClientException(
                "Curl extension must be enabled",
                ClientException::ERROR_CURL_MUST_BE_ENABLED
            );
        }

        return $this->doCurl($url, $params, $files);
    }

    /**
     * Curl HTTP POST Request
     *
     * @return array of result, error code and error
     */
    private function doCurl(string $url, array $params = [], array $files = []): array
    {
        $this->responseHeaders = [];
        $requestHeaders = $this->config->getRequestHeaders();

        $params = $this->jsonEncode($params);
        $this->log("curl: $url");
        $this->log("post: $params");
        if ($this->config->getFormat() === self::KALTURA_SERVICE_FORMAT_JSON) {
            $requestHeaders[] = 'Accept: application/json';
        } elseif ($this->config->getFormat() === self::KALTURA_SERVICE_FORMAT_XML) {
            $requestHeaders[] = 'Accept: application/xml';
        }

        $cookies = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (count($files) > 0) {
            $params = ['json' => $params];
            foreach ($files as $key => $file) {
                $params[$key] = new CURLFile($file);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            $requestHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->config->getUserAgent());
        if (count($files) > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->getCurlTimeout());
        }

        if ($this->config->getStartZendDebuggerSession() === true) {
            $zendDebuggerParams = $this->getZendDebuggerParams($url);
            $cookies = array_merge($cookies, $zendDebuggerParams);
        }

        if (count($cookies) > 0) {
            $cookiesStr = http_build_query($cookies, null, '; ');
            curl_setopt($ch, CURLOPT_COOKIE, $cookiesStr);
        }

        if ($this->config->getProxyHost()) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
            curl_setopt($ch, CURLOPT_PROXY, $this->config->getProxyHost());
            if ($this->config->getProxyPort()) {
                curl_setopt($ch, CURLOPT_PROXYPORT, $this->config->getProxyPort());
            }
            if ($this->config->getProxyUser()) {
                curl_setopt(
                    $ch,
                    CURLOPT_PROXYUSERPWD,
                    $this->config->getProxyUser() . ':' . $this->config->getProxyPassword()
                );
            }
            if ($this->config->getProxyType() === 'SOCKS5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
        }

        // Set SSL verification
        if (!$this->config->getVerifySSL()) {
            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        // Set custom headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);

        // Save response headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'readHeader']);

        $result = curl_exec($ch);
        $curlErrorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        return [$result, $curlErrorCode, $curlError];
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;

        $logger = $this->config->getLogger();
        if ($logger instanceof ILogger) {
            $this->shouldLog = true;
        }
    }

    public function setClientConfiguration(Configuration $configuration): void
    {
        $params = get_class_vars('\Kaltura\Client\Type\ClientConfiguration');
        foreach ($params as $param) {
            if (is_null($configuration->$param)) {
                if (isset($this->clientConfiguration[$param])) {
                    unset($this->clientConfiguration[$param]);
                }
            } else {
                $this->clientConfiguration[$param] = $configuration->$param;
            }
        }
    }

    public function setRequestConfiguration(RequestConfiguration $configuration): void
    {
        $params = get_class_vars('\Kaltura\Client\Type\RequestConfiguration');
        $params = array_keys($params);
        foreach ($params as $param) {
            if (is_null($configuration->$param)) {
                if (isset($this->requestConfiguration[$param])) {
                    unset($this->requestConfiguration[$param]);
                }
            } else {
                $this->requestConfiguration[$param] = $configuration->$param;
            }
        }
    }

    /**
     * Add parameter to array of parameters that is passed by reference
     */
    public function addParam(
        array &$params,
        string $paramName,
        bool|string|array|null|NullValue|ObjectBase $paramValue
    ): void {
        if ($paramValue === null) {
            return;
        }

        if ($paramValue instanceof NullValue) {
            $params[$paramName . '__null'] = '';
            return;
        }

        if ($paramValue instanceof ObjectBase) {
            $params[$paramName] = [
                'objectType' => $paramValue->getKalturaObjectType()
            ];

            foreach ($paramValue as $prop => $val) {
                $this->addParam($params[$paramName], $prop, $val);
            }

            return;
        }

        if (is_bool($paramValue)) {
            $params[$paramName] = $paramValue;
            return;
        }

        if (!is_array($paramValue)) {
            $params[$paramName] = (string)$paramValue;
            return;
        }

        $params[$paramName] = [];
        if ($paramValue) {
            foreach ($paramValue as $subParamName => $subParamValue) {
                $this->addParam($params[$paramName], $subParamName, $subParamValue);
            }
        } else {
            $params[$paramName]['-'] = '';
        }
    }

    public function jsObjectToClientObject(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->jsObjectToClientObject($item);
            }

            unset($item);
        }

        if (is_object($value)) {
            if (isset($value->message, $value->code)) {
                if ($this->isMultiRequest()) {
                    if (isset($value->args)) {
                        $value->args = (array)$value->args;
                    }
                    return (array)$value;
                }
                throw $this->getAPIException($value->message, $value->code, $value->args);
            }

            if (!isset($value->objectType)) {
                throw $this->getClientException(
                    "Response format not supported - objectType is required for all objects",
                    ClientException::ERROR_FORMAT_NOT_SUPPORTED
                );
            }

            $objectType = $value->objectType;
            $object = new $objectType();
            $attributes = get_object_vars($value);
            foreach ($attributes as $attribute => $attributeValue) {
                if ($attribute === 'objectType') {
                    continue;
                }

                $object->$attribute = $this->jsObjectToClientObject($attributeValue);
            }

            $value = $object;
        }

        return $value;
    }

    /**
     * Encodes objects
     * @param mixed $value
     * @return string
     */
    public function jsonEncode(array $value): string
    {
        try {
            return json_encode($this->unsetNull($value), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new \RuntimeException();
        }
    }

    protected function unsetNull(array|object|string $object): object|array|string
    {
        if (is_string($object)) {
            return $object;
        }

        if (!is_array($object) && !is_object($object)) {
            return $object;
        }

        if ($object instanceof MultiRequestSubResult) {
            return (string)$object;
        }

        $array = (array)$object;
        foreach ($array as $key => $value) {
            if (is_null($value)) {
                unset($array[$key]);
                continue;
            }

            $array[$key] = $this->unsetNull($value);
        }

        if ($object instanceof ObjectBase) {
            $array['objectType'] = $object->getKalturaObjectType();
        }

        return $array;
    }

    /**
     * Validate that the passed object type is of the expected type
     */
    public function validateObjectType(array|object|null $resultObject, string $objectType): void
    {
        $knownNativeTypes = ["boolean", "integer", "double", "string"];
        if (is_null($resultObject) ||
            (in_array(gettype($resultObject), $knownNativeTypes) &&
                in_array($objectType, $knownNativeTypes))) {
            return;// we do not check native simple types
        }

        if (is_object($resultObject)) {
            if (!($resultObject instanceof $objectType)) {
                throw $this->getClientException(
                    "Invalid object type - not instance of $objectType",
                    ClientException::ERROR_INVALID_OBJECT_TYPE
                );
            }
        } elseif (class_exists($objectType) && is_subclass_of($objectType, 'EnumBase')) {
            $enum = new ReflectionClass($objectType);
            $values = array_map('strval', $enum->getConstants());
            if (!in_array($resultObject, $values, true)) {
                throw $this->getClientException(
                    "Invalid enum value",
                    ClientException::ERROR_INVALID_ENUM_VALUE
                );
            }
        } elseif (gettype($resultObject) !== $objectType) {
            throw $this->getClientException(
                "Invalid object type",
                ClientException::ERROR_INVALID_OBJECT_TYPE
            );
        }
    }

    public function startMultiRequest(): void
    {
        $this->multiRequestReturnType = [];
    }

    public function doMultiRequest(): ?array
    {
        if (count($this->callsQueue) === 0) {
            $this->resetRequest();
            return null;
        }

        if ($this->config->getFormat() === self::KALTURA_SERVICE_FORMAT_XML) {
            $xmlData = $this->doQueue();
            if (is_null($xmlData)) {
                return null;
            }

            $xml = new SimpleXMLElement($xmlData);
            $items = $xml->result->children();
            $ret = [];
            $i = 0;
            foreach ($items as $item) {
                $error = $this->checkIfError($item, false);
                $fallbackType = $this->multiRequestReturnType[$i] ?? null;
                if ($error) {
                    $ret[] = $error;
                } elseif ($item->objectType) {
                    $ret[] = ParseUtils::unmarshalObject($item, $fallbackType);
                } elseif ($item->item) {
                    $ret[] = ParseUtils::unmarshalArray($item, $fallbackType);
                } else {
                    $ret[] = ParseUtils::unmarshalSimpleType($item);
                }
                $i++;
            }
        } elseif ($this->config->getFormat() === self::KALTURA_SERVICE_FORMAT_JSON) {
            $postResult = $this->doQueue();
            try {
                $result = json_decode($postResult, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw $this->getClientException("failed to unserialize server result\n$postResult", ClientException::ERROR_UNSERIALIZE_FAILED);
            }

            if ($result === null && strtolower($postResult) !== 'null') {
                $this->resetRequest();
            }

            $ret = ParseUtils::jsObjectToClientObject($result, null, true);
        } else {
            $this->resetRequest();
            throw $this->getClientException("unsupported format: ".$this->config->getFormat(), ClientException::ERROR_FORMAT_NOT_SUPPORTED);
        }

        $this->resetRequest();
        return $ret;
    }

    public function isMultiRequest(): bool
    {
        return $this->multiRequestReturnType !== null;
    }

    public function getMultiRequestQueueSize(): int
    {
        return count($this->callsQueue);
    }

    public function getMultiRequestResult(): MultiRequestSubResult
    {
        return new MultiRequestSubResult($this->getMultiRequestQueueSize() . ':result');
    }

    protected function log(string $msg): void
    {
        if ($this->shouldLog && $this->config->getLogger()) {
            $this->config->getLogger()->log($msg);
        }
    }

    /**
     * Return a list of parameters used to start a new debug session on the destination server api
     * @link http://kb.zend.com/index.php?View=entry&EntryID=434
     */
    protected function getZendDebuggerParams(string $url): array
    {
        $params = [];
        $passThruParams = [
            'debug_host',
            'debug_fastfile',
            'debug_port',
            'start_debug',
            'send_debug_header',
            'send_sess_end',
            'debug_jit',
            'debug_stop',
            'use_remote'
        ];

        foreach ($passThruParams as $param) {
            if (isset($_COOKIE[$param])) {
                $params[$param] = $_COOKIE[$param];
            }
        }

        $params['original_url'] = $url;
        $params['debug_session_id'] = microtime(true); // to create a new debug session

        return $params;
    }

    public function generateSession(
        $adminSecretForSigning,
        $userId,
        $type,
        $partnerId,
        $expiry = 86400,
        $privileges = ''
    ): string {
        $rand = random_int(0, 32000);
        $expiry = time() + $expiry;
        $fields = [
            $partnerId,
            $partnerId,
            $expiry,
            $type,
            $rand,
            $userId,
            $privileges
        ];
        $info = implode(";", $fields);

        $signature = $this->hash($adminSecretForSigning, $info);
        $strToHash = $signature . "|" . $info;

        return base64_encode($strToHash);
    }

    public static function generateSessionV2(string $adminSecretForSigning, string $userId, int $type, int $partnerId, int $expiry, string $privileges): string
    {
        // build fields array
        $fields = [];
        foreach (explode(',', $privileges) as $privilege) {
            $privilege = trim($privilege);
            if (!$privilege) {
                continue;
            }
            if ($privilege === '*') {
                $privilege = 'all:*';
            }
            $splitPrivilege = explode(':', $privilege, 2);
            if (count($splitPrivilege) > 1) {
                $fields[$splitPrivilege[0]] = $splitPrivilege[1];
            } else {
                $fields[$splitPrivilege[0]] = '';
            }
        }
        $fields[self::FIELD_EXPIRY] = time() + $expiry;
        $fields[self::FIELD_TYPE] = $type;
        $fields[self::FIELD_USER] = $userId;

        // build fields string
        $fieldsStr = http_build_query($fields, '', '&');
        $rand = '';
        for ($i = 0; $i < self::RANDOM_SIZE; $i++) {
            $rand .= chr(random_int(0, 0xff));
        }
        $fieldsStr = $rand . $fieldsStr;
        $fieldsStr = sha1($fieldsStr, true) . $fieldsStr;

        // encrypt and encode
        $encryptedFields = self::aesEncrypt($adminSecretForSigning, $fieldsStr);
        $decodedKs = "v2|{$partnerId}|" . $encryptedFields;
        return str_replace(['+', '/'], ['-', '_'], base64_encode($decodedKs));
    }

    /** @noinspection EncryptionInitializationVectorRandomnessInspection */
    protected static function aesEncrypt(string $key, string $message): string
    {
        // no need for an IV since we add a random string to the message anyway
        // Pad with null byte to be compatible with mcrypt PKCS#5 padding
        $iv = str_repeat("\0", 16);
        $key = substr(sha1($key, true), 0, 16);

        $blockSize = 16;
        if (strlen($message) % $blockSize) {
            $padLength = $blockSize - strlen($message) % $blockSize;
            $message .= str_repeat("\0", $padLength);
        }

        return openssl_encrypt(
            $message,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
    }

    private function hash(string $salt, string $str): string
    {
        return sha1($salt . $str);
    }

    /**
     * @return NullValue
     */
    public static function getKalturaNullValue(): NullValue
    {
        return NullValue::getInstance();
    }

    public function __get(string $prop)
    {
        $getter = 'get' . ucfirst($prop) . 'Service';
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return null;
    }

    public function __set(string $name, mixed $value) {}

    public function __isset(string $name) {}

    public function getClientException(string $error, string $errorCode): ClientException
    {
        return new ClientException($error, $errorCode);
    }

    public function getAPIException(string $message, string $code, array $arguments): ApiException
    {
        return new ApiException($message, $code, $arguments);
    }

    public function checkIfError(SimpleXMLElement $xml, bool $throwException = true): ?ApiException
    {
        if (($xml->error) && (count($xml->children()) === 1)) {
            $code = (string)($xml->error->code);
            $message = (string)($xml->error->message);
            $arguments = $xml->error->args ? ParseUtils::unmarshalArray(
                $xml->error->args,
                'KalturaApiExceptionArg'
            ) : [];
            if ($throwException) {
                throw $this->getAPIException($message, $code, $arguments);
            }

            return $this->getAPIException($message, $code, $arguments);
        }

        return null;
    }
}
