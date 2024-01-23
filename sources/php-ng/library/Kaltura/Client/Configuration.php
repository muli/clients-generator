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
class Configuration
{
    /**
     * @var string
     */
    private string $serviceUrl = "https://www.kaltura.com/";

    /**
     * @var int
     */
    private int $format = Base::KALTURA_SERVICE_FORMAT_XML;

    /**
     * @var int
     */
    private int $curlTimeout = 120;

    /**
     * @var string
     */
    private string $userAgent = '';

    /**
     * @var bool
     */
    private bool $startZendDebuggerSession = false;

    /**
     * @var string|null
     */
    private ?string $proxyHost = null;

    /**
     * @var int|null
     */
    private ?int $proxyPort = null;

    /**
     * @var string
     */
    private string $proxyType = 'HTTP';

    /**
     * @var string|null
     */
    private ?string $proxyUser = null;

    /**
     * @var string
     */
    private string $proxyPassword = '';

    /**
     * @var bool
     */
    private bool $verifySSL = true;

    /**
     * @var array
     */
    private array $requestHeaders = [];

    /**
     * @var ILogger|null
     */
    private ILogger|null $logger = null;

    /**
     * @return string the service URL
     */
    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }

    /**
     * @return int the format
     */
    public function getFormat(): int
    {
        return $this->format;
    }

    /**
     * @return int the curlTimeout
     */
    public function getCurlTimeout(): int
    {
        return $this->curlTimeout;
    }

    /**
     * @return string the user agent
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return bool $startZendDebuggerSession
     */
    public function getStartZendDebuggerSession(): bool
    {
        return $this->startZendDebuggerSession;
    }

    /**
     * @return string|null proxy host
     */
    public function getProxyHost(): ?string
    {
        return $this->proxyHost;
    }

    /**
     * @return int|null $proxyPort
     */
    public function getProxyPort(): ?int
    {
        return $this->proxyPort;
    }

    /**
     * @return string $proxyType
     */
    public function getProxyType(): string
    {
        return $this->proxyType;
    }

    /**
     * @return string|null $proxyUser
     */
    public function getProxyUser(): ?string
    {
        return $this->proxyUser;
    }

    /**
     * @return string $proxyPassword
     */
    public function getProxyPassword(): string
    {
        return $this->proxyPassword;
    }

    /**
     * @return bool $verifySSL
     */
    public function getVerifySSL(): bool
    {
        return $this->verifySSL;
    }

    /**
     * @return array $requestHeaders
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    public function setServiceUrl(string $serviceUrl): void
    {
        $this->serviceUrl = $serviceUrl;
    }

    public function setFormat(int $format): void
    {
        $this->format = $format;
    }

    public function setCurlTimeout(int $curlTimeout): void
    {
        $this->curlTimeout = $curlTimeout;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setStartZendDebuggerSession(bool $startZendDebuggerSession): void
    {
        $this->startZendDebuggerSession = $startZendDebuggerSession;
    }

    public function setProxyHost(?string $proxyHost): void
    {
        $this->proxyHost = $proxyHost;
    }

    public function setProxyPort(?int $proxyPort): void
    {
        $this->proxyPort = $proxyPort;
    }

    public function setProxyType(string $proxyType): void
    {
        $this->proxyType = $proxyType;
    }

    public function setProxyUser(?string $proxyUser): void
    {
        $this->proxyUser = $proxyUser;
    }

    public function setProxyPassword(string $proxyPassword): void
    {
        $this->proxyPassword = $proxyPassword;
    }

    public function setVerifySSL(bool $verifySSL): void
    {
        $this->verifySSL = $verifySSL;
    }

    public function setRequestHeaders(array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * Set logger to get kaltura client debug logs
     */
    public function setLogger(ILogger $log): void
    {
        $this->logger = $log;
    }

    /**
     * Gets the logger (Internal client use)
     */
    public function getLogger(): ?ILogger
    {
        return $this->logger;
    }
}
