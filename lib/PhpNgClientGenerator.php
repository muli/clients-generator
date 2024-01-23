<?php

class PhpNgClientGenerator extends ClientGeneratorFromXml
{
    private array $cacheEnums = [];
    private array $cacheTypes = [];

    public function __construct($xmlPath, Zend_Config $config, $sourcePath = "php-ng")
    {
        parent::__construct($xmlPath, $sourcePath, $config);
    }

    private function cacheEnum(DOMElement $enumNode): void
    {
        $enumName = $enumNode->getAttribute('name');
        $enumCacheName = preg_replace('/^Kaltura(.+)$/', '$1', $enumName);

        $classInfo = new PhpZend2ClientGeneratorClassInfo();
        $classInfo->setClassName($enumCacheName);
        if ($enumNode->hasAttribute('plugin')) {
            $pluginName = ucfirst($enumNode->getAttribute('plugin'));
            $classInfo->setNamespace("Kaltura\\Client\\Plugin\\$pluginName\\Enum");
        } else {
            $classInfo->setNamespace("Kaltura\\Client\\Enum");
        }
        $this->cacheEnums[$enumName] = $classInfo;
    }

    private function cacheType(DOMElement $classNode): void
    {
        $className = $classNode->getAttribute('name');
        $classCacheName = preg_replace('/^Kaltura(.+)$/', '$1', $className);
        $classCacheName = $this->replaceReservedWords($classCacheName);

        $classInfo = new PhpZend2ClientGeneratorClassInfo();
        $classInfo->setClassName($classCacheName);
        if ($classNode->hasAttribute('plugin')) {
            $pluginName = ucfirst($classNode->getAttribute('plugin'));
            $classInfo->setNamespace("Kaltura\\Client\\Plugin\\$pluginName\\Type");
        } else {
            $classInfo->setNamespace("Kaltura\\Client\\Type");
        }
        $this->cacheTypes[$className] = $classInfo;
    }

    public function generate(): void
    {
        parent::generate();

        $xpath = new DOMXPath($this->_doc);

        $enumNodes = $xpath->query("/xml/enums/enum");
        foreach ($enumNodes as $enumNode) {
            $this->cacheEnum($enumNode);
        }

        $classNodes = $xpath->query("/xml/classes/class");
        foreach ($classNodes as $classNode) {
            $this->cacheType($classNode);
        }

        $this->startNewTextBlock();
        $this->appendLine('<?php');

        $this->appendLine('namespace Kaltura\Client;');
        $this->appendLine();

        if ($this->generateDocs) {
            $this->appendLine('/**');
            $this->appendLine(" * @package $this->package");
            $this->appendLine(" * @subpackage $this->subpackage");
            $this->appendLine(' */');
        }

        $this->appendLine('class TypeMap');
        $this->appendLine('{');

        $classNodes = $xpath->query("/xml/classes/class");
        $this->appendLine('    private static array $map = [');
        foreach ($classNodes as $classNode) {
            $kalturaType = $classNode->getAttribute('name');
            $zendType = $this->getTypeClassInfo($kalturaType);
            $this->appendLine("        '$kalturaType' => '{$zendType->getFullyQualifiedNameNoPrefixSlash()}',");
        }
        $this->appendLine('    ];');
        $this->appendLine();

        $this->appendLine('    public static function getZendType($kalturaType)');
        $this->appendLine('    {');
        $this->appendLine('        return self::$map[$kalturaType] ?? null;');
        $this->appendLine('    }');
        $this->appendLine('}');

        $this->addFile($this->getMapPath(), $this->getTextBlock());

        // enums
        $enumNodes = $xpath->query("/xml/enums/enum");
        foreach ($enumNodes as $enumNode) {
            $this->startNewTextBlock();
            $this->appendLine('<?php');
            $this->writeEnum($enumNode);
            $this->addFile($this->getEnumPath($enumNode), $this->getTextBlock());
        }

        // classes
        $classNodes = $xpath->query("/xml/classes/class");
        foreach ($classNodes as $classNode) {
            $this->writeClass($classNode);
        }

        // services
        $serviceNodes = $xpath->query("/xml/services/service");
        foreach ($serviceNodes as $serviceNode) {
            $this->startNewTextBlock();
            $this->appendLine('<?php');
            $this->writeService($serviceNode);
            $this->addFile($this->getServicePath($serviceNode), $this->getTextBlock());
        }

        $this->startNewTextBlock();
        $this->appendLine('<?php');

        $configurationNodes = $xpath->query("/xml/configurations/*");
        $this->writeMainClient($serviceNodes, $configurationNodes);

        $this->addFile($this->getMainPath(), $this->getTextBlock());


        // plugins
        $pluginNodes = $xpath->query("/xml/plugins/plugin");
        foreach ($pluginNodes as $pluginNode) {
            $this->writePlugin($pluginNode);
        }
    }

    protected function getEnumPath(DOMElement $enumNode): string
    {
        $enumName = $enumNode->getAttribute('name');
        $enumName = preg_replace('/^Kaltura(.+)$/', '$1', $enumName);

        if (!$enumNode->hasAttribute('plugin')) {
            return "library/Kaltura/Client/Enum/$enumName.php";
        }

        $pluginName = ucfirst($enumNode->getAttribute('plugin'));
        return "library/Kaltura/Client/Plugin/$pluginName/Enum/$enumName.php";
    }

    protected function replaceReservedWords($className): string
    {
        if ($className === 'String') {
            return 'Str';
        }

        return $className;
    }

    protected function getTypePath(DOMElement $classNode): string
    {
        $className = $classNode->getAttribute('name');
        $className = preg_replace('/^Kaltura(.+)$/', '$1', $className);
        $className = $this->replaceReservedWords($className);

        if (!$classNode->hasAttribute('plugin')) {
            return "library/Kaltura/Client/Type/$className.php";
        }

        $pluginName = ucfirst($classNode->getAttribute('plugin'));
        return "library/Kaltura/Client/Plugin/$pluginName/Type/$className.php";
    }

    protected function getServicePath($serviceNode): string
    {
        $serviceName = ucfirst($serviceNode->getAttribute('name'));

        if (!$serviceNode->hasAttribute('plugin')) {
            return "library/Kaltura/Client/Service/{$serviceName}Service.php";
        }

        $pluginName = ucfirst($serviceNode->getAttribute('plugin'));
        return "library/Kaltura/Client/Plugin/$pluginName/Service/{$serviceName}Service.php";
    }

    protected function getPluginPath($pluginName): string
    {
        $pluginName = ucfirst($pluginName);
        return "library/Kaltura/Client/Plugin/$pluginName/" . $this->getPluginClass($pluginName) . ".php";
    }

    protected function getMainPath(): string
    {
        return 'library/Kaltura/Client/Client.php';
    }

    protected function getMapPath(): string
    {
        return 'library/Kaltura/Client/TypeMap.php';
    }

    /**
     * @throws RuntimeException
     */
    protected function getEnumClassInfo($enumName): PhpZend2ClientGeneratorClassInfo
    {
        if (!isset($this->cacheEnums[$enumName])) {
            throw new RuntimeException("Enum info for $enumName not found");
        }

        return $this->cacheEnums[$enumName];
    }

    /**
     * @param string $className
     * @return PhpZend2ClientGeneratorClassInfo
     */
    protected function getTypeClassInfo(string $className): PhpZend2ClientGeneratorClassInfo
    {
        if ($className === 'KalturaObjectBase') {
            $classInfo = new PhpZend2ClientGeneratorClassInfo();
            $classInfo->setClassName($className);
            $classInfo->setNamespace("Kaltura\\Client\\Type");
            return $classInfo;
        }

        if (!isset($this->cacheTypes[$className])) {
            throw new RuntimeException("Class info for $className not found");
        }

        return $this->cacheTypes[$className];
    }

    protected function getServiceClass(DOMElement $serviceNode): string
    {
        $serviceName = ucfirst($serviceNode->getAttribute('name'));

        return "{$serviceName}Service";
    }

    protected function getPluginClass($pluginName): string
    {
        $pluginName = ucfirst($pluginName);
        return "{$pluginName}Plugin";
    }

    protected function formatMultiLineComment($description, $ident = 1): array|string
    {
        $tabs = str_repeat("\t", $ident);
        return str_replace("\n", "\n$tabs * ", $description); // to format multiline descriptions
    }

    public function writePlugin(DOMElement $pluginNode): void
    {
        $xpath = new DOMXPath($this->_doc);

        $pluginName = $pluginNode->getAttribute("name");
        $pluginClassName = $this->getPluginClass($pluginName);

        $this->startNewTextBlock();
        $this->appendLine('<?php');

        $this->appendLine('/**');
        $this->appendLine(' * @namespace');
        $this->appendLine(' */');
        $this->appendLine("namespace Kaltura\\Client\\Plugin\\" . ucfirst($pluginName) . ";");
        $this->appendLine();

        if ($this->generateDocs) {
            $this->appendLine('/**');
            $this->appendLine(" * @package $this->package");
            $this->appendLine(" * @subpackage $this->subpackage");
            $this->appendLine(' */');
        }

        $this->appendLine("class $pluginClassName extends \Kaltura\Client\Plugin");
        $this->appendLine('{');

        $serviceNodes = $xpath->query("/xml/services/service[@plugin = '$pluginName']");
        foreach ($serviceNodes as $serviceNode) {
            $serviceName = $serviceNode->getAttribute("name");
            $serviceClass = $this->getServiceClass($serviceNode);
            $serviceRelativeClassName = "Service\\$serviceClass";
            $this->appendLine('    /**');
            $this->appendLine("     * @var $serviceRelativeClassName");
            $this->appendLine('     */');
            $this->appendLine("    protected ?$serviceRelativeClassName \$$serviceName = null;");
            $this->appendLine();
        }

        $this->appendLine();
        $this->appendLine('    /**');
        $this->appendLine("     * @return $pluginClassName");
        $this->appendLine('     */');
        $this->appendLine('    public static function get(\Kaltura\Client\Client $client)');
        $this->appendLine('    {');
        $this->appendLine("        return new $pluginClassName(\$client);");
        $this->appendLine('    }');
        $this->appendLine();
        $this->appendLine('    /**');
        $this->appendLine('     * @return array<\Kaltura\Client\ServiceBase>');
        $this->appendLine('     */');
        $this->appendLine('    public function getServices(): array');
        $this->appendLine('    {');
        $this->appendLine('        return [');
        foreach ($serviceNodes as $serviceNode) {
            $serviceName = $serviceNode->getAttribute("name");
            $serviceClass = $this->getServiceClass($serviceNode);
            $this->appendLine("            '$serviceName' => \$this->get$serviceClass(),");
        }
        $this->appendLine('        ];');
        $this->appendLine('    }');
        $this->appendLine();
        $this->appendLine('    /**');
        $this->appendLine('     * @return string');
        $this->appendLine('     */');
        $this->appendLine('    public function getName(): string');
        $this->appendLine('    {');
        $this->appendLine("        return '$pluginName';");
        $this->appendLine('    }');

        foreach ($serviceNodes as $serviceNode) {
            $serviceName = $serviceNode->getAttribute("name");
            $serviceClass = $this->getServiceClass($serviceNode);
            $serviceRelativeClassName = "Service\\$serviceClass";

            $this->appendLine("    /**");
            $this->appendLine(
                "     * @return \\Kaltura\\Client\\Plugin\\" . ucfirst($pluginName) . "\\$serviceRelativeClassName"
            );
            $this->appendLine("     */");
            $this->appendLine("    public function get$serviceClass()");
            $this->appendLine("    {");
            $this->appendLine("        if (\$this->$serviceName === null) {");
            $this->appendLine("            \$this->$serviceName = new $serviceRelativeClassName(\$this->_client);");
            $this->appendLine("        }");
            $this->appendLine("        return \$this->$serviceName;");
            $this->appendLine("    }");
        }
        $this->appendLine('}');
        $this->appendLine();

        $this->addFile($this->getPluginPath($pluginName), $this->getTextBlock());
    }


    public function writeEnum(DOMElement $enumNode): void
    {
        $type = $enumNode->getAttribute('name');
        if (!$this->shouldIncludeType($type)) {
            return;
        }

        $enumClassInfo = $this->getEnumClassInfo($type);

        $this->appendLine("namespace {$enumClassInfo->getNamespace()};");
        $this->appendLine();

        if ($this->generateDocs) {
            $this->appendLine('/**');
            $this->appendLine(" * @package $this->package");
            $this->appendLine(" * @subpackage $this->subpackage");
            $this->appendLine(' */');
        }

        $this->appendLine("class {$enumClassInfo->getClassName()} extends \Kaltura\Client\EnumBase");
        $this->appendLine("{");
        foreach ($enumNode->childNodes as $constNode) {
            if ($constNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $propertyName = $constNode->getAttribute("name");
            $propertyValue = $constNode->getAttribute("value");
            if ($enumNode->getAttribute("enumType") === "string") {
                $this->appendLine("    public const $propertyName = \"$propertyValue\";");
            } else {
                $this->appendLine("    public const $propertyName = $propertyValue;");
            }
        }

        $this->appendLine("}");
        $this->appendLine();
    }

    public function writeClass(DOMElement $classNode): void
    {
        $kalturaType = $classNode->getAttribute('name');
        if (!$this->shouldIncludeType($kalturaType)) {
            return;
        }

        $this->startNewTextBlock();
        $this->appendLine('<?php');

        $description = $classNode->getAttribute("description");
        $type = $this->getTypeClassInfo($kalturaType);

        $abstract = '';
        if ($classNode->hasAttribute("abstract")) {
            $abstract = 'abstract ';
        }

        $this->appendLine("namespace {$type->getNamespace()};");

        $this->maybeGeneratePackageDocs($description);

        // class definition
        $baseClass = '\Kaltura\Client\ObjectBase';
        if ($classNode->hasAttribute('base')) {
            $baseClassInfo = $this->getTypeClassInfo($classNode->getAttribute('base'));
            $baseClass = $baseClassInfo->getFullyQualifiedName();
        }

        $this->appendLine($abstract . "class {$type->getClassName()} extends $baseClass");
        $this->appendLine("{");
        $this->appendLine("    public function getKalturaObjectType(): string");
        $this->appendLine("    {");
        $this->appendLine("        return '$kalturaType';");
        $this->appendLine("    }");

        if (!count($classNode->childNodes)) {
            // close class
            $this->appendLine("}");
            $this->addFile($this->getTypePath($classNode), $this->getTextBlock());

            return;
        }

        $this->appendLine('    public function __construct(\SimpleXMLElement $xml = null, $jsonObject = null)');
        $this->appendLine('    {');
        $this->appendLine('        parent::__construct($xml, $jsonObject);');
        $this->appendLine('        ');
        $this->appendLine('        if ($xml === null && $jsonObject === null) {');
        $this->appendLine('            return;');
        $this->appendLine('        }');
        $this->appendLine();
        $this->appendLine('        if ($xml !== null) {');
        $this->appendLine('            $this->buildFromXml($xml);');
        $this->appendLine('            return;');
        $this->appendLine('        }');
        $this->appendLine();
        $this->appendLine('        if ($jsonObject !== null) {');
        $this->appendLine('            $this->buildFromJson($jsonObject);');
        $this->appendLine('        }');
        $this->appendLine('    }');
        $this->appendLine();

        $this->writeXmlConstructor($kalturaType, $classNode->childNodes);
        $this->writeJsonConstructor($kalturaType, $classNode->childNodes);

        // class properties
        foreach ($classNode->childNodes as $propertyNode) {
            if ($propertyNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $propName = $propertyNode->getAttribute("name");
            $isReadyOnly = (int)$propertyNode->getAttribute("readOnly") === 1;
            $isInsertOnly = (int)$propertyNode->getAttribute("insertOnly") === 1;
            $isEnum = $propertyNode->hasAttribute("enumType");
            $propType = $isEnum
                ? $propertyNode->getAttribute("enumType")
                : $propertyNode->getAttribute("type");

            $propType = $this->getPHPType($propType);
            $description = $propertyNode->getAttribute("description");

            $this->appendLine("    /**");
            $this->appendLine("     * " . $this->formatMultiLineComment($description));

            $classProperty = '';
            if ($propType === "array") {
                $classProperty = '?array';
                $propClassInfo = $this->getTypeClassInfo($propertyNode->getAttribute("arrayType"));
                $this->appendLine("     * @var {$propClassInfo->getClassName()}[]");
            } elseif ($propType === "map") {
                $classProperty = '?array';
                $propClassInfo = $this->getTypeClassInfo($propertyNode->getAttribute("arrayType"));
                $this->appendLine("     * @var array<string, {$propClassInfo->getClassName()}>");
            } elseif ($this->isSimpleType($propType)) {
                $classProperty = "?{$this->getPHPType($propType)}";
                $this->appendLine("     * @var $propType|null");
            } elseif ($isEnum) {
                $propClassInfo = $this->getEnumClassInfo($propType);
                $this->appendLine("     * @var {$propClassInfo->getFullyQualifiedName()}|null");
            } else {
                $propClassInfo = $this->getTypeClassInfo($propType);
                $this->appendLine("     * @var {$propClassInfo->getFullyQualifiedName()}");
            }

            if ($isReadyOnly) {
                $this->appendLine("     * @readonly");
            }

            if ($isInsertOnly) {
                $this->appendLine("     * @insertonly");
            }

            $this->appendLine("     */");

            $propertyLine = "public $classProperty $$propName = null";

            $this->appendLine("    $propertyLine;");
            $this->appendLine();
        }

        // close class
        $this->appendLine("}");
        $this->addFile($this->getTypePath($classNode), $this->getTextBlock());
    }

    public function writeService(DOMElement $serviceNode): void
    {
        $serviceId = $serviceNode->getAttribute("id");
        if (!$this->shouldIncludeService($serviceId)) {
            return;
        }

        $plugin = null;
        if ($serviceNode->hasAttribute('plugin')) {
            $plugin = $serviceNode->getAttribute('plugin');
        }

        $description = $serviceNode->getAttribute("description");

        $serviceClassName = $this->getServiceClass($serviceNode);
        $this->appendLine();

        if ($plugin) {
            $this->appendLine("namespace Kaltura\\Client\\Plugin\\" . ucfirst($plugin) . "\\Service;");
        } else {
            $this->appendLine('namespace Kaltura\Client\Service;');
        }

        $this->maybeGeneratePackageDocs($description);

        $this->appendLine("class $serviceClassName extends \Kaltura\Client\ServiceBase");
        $this->appendLine("{");
        $this->appendLine("    public function __construct(\\Kaltura\\Client\\Client \$client = null)");
        $this->appendLine("    {");
        $this->appendLine("        parent::__construct(\$client);");
        $this->appendLine("    }");

        $actionNodes = $serviceNode->childNodes;
        foreach ($actionNodes as $actionNode) {
            if ($actionNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $this->writeAction($serviceId, $actionNode);
        }
        $this->appendLine("}");
    }

    public function writeAction($serviceId, DOMElement $actionNode): void
    {
        $action = $actionNode->getAttribute("name");
        if (!$this->shouldIncludeAction($serviceId, $action)) {
            return;
        }

        $resultNode = $actionNode->getElementsByTagName("result")->item(0);
        $resultType = $resultNode?->getAttribute("type");
        $arrayObjectType = ($resultType === 'array') ? $resultNode?->getAttribute("arrayType") : null;
        $description = $actionNode->getAttribute("description");

        $enableInMultiRequest = true;
        if ($actionNode->hasAttribute("enableInMultiRequest")) {
            $enableInMultiRequest = (int)$actionNode->getAttribute("enableInMultiRequest");
        }

        // method signature
        $methodName = $action;
        if (in_array($action, ["list", "clone", "goto"])) {
            // reserved PHP keywords
            $methodName = "{$methodName}Action";
        }

        $signature = "public function $methodName(";

        $paramNodes = $actionNode->getElementsByTagName("param");
        $signature .= $this->getSignature($paramNodes);

        $this->appendLine();
        $this->appendLine("    /**");
        if ($description) {
            $this->appendLine("     * " . $this->formatMultiLineComment($description));
        }
        $this->appendLine("     * ");
        $multiRequestResultType = '';
        if ($enableInMultiRequest) {
            $multiRequestResultType = '|\Kaltura\Client\MultiRequestSubResult';
        }
        if ($resultType && $resultType !== 'null') {
            if ($resultType === 'file') {
                $this->appendLine("     * @return string$multiRequestResultType");
            }
            elseif ($resultType === 'array' || $this->isSimpleType($resultType)) {
                $this->appendLine("     * @return $resultType$multiRequestResultType");
            } else {
                $resultTypeClassInfo = $this->getTypeClassInfo($resultType);
                $this->appendLine("     * @return {$resultTypeClassInfo->getFullyQualifiedName()}$multiRequestResultType");
            }
        }
        $this->appendLine("     */");
        $this->appendLine("    $signature");
        $this->appendLine("    {");

        if (!$enableInMultiRequest) {
            $this->appendLine("        if (\$this->client->isMultiRequest()) {");
            $this->appendLine("            throw \$this->client->getClientException(\"Action is not supported as part of multi-request.\", ClientException::ERROR_ACTION_IN_MULTIREQUEST);");
            $this->appendLine("        }");
            $this->appendLine();
        }

        $this->appendLine("        \$kparams = [];");
        $haveFiles = false;
        foreach ($paramNodes as $paramNode) {
            $paramType = $paramNode->getAttribute("type");
            $paramName = $paramNode->getAttribute("name");
            $isEnum = $paramNode->hasAttribute("enumType");
            $isOptional = $paramNode->getAttribute("optional");

            if ($haveFiles === false && $paramType === "file") {
                $haveFiles = true;
                $this->appendLine("        \$kfiles = [];");
            }

            if ($this->isSimpleType($paramType)) {
                $this->appendLine("        \$this->client->addParam(\$kparams, \"$paramName\", \$$paramName);");
                continue;
            }

            if ($isEnum) {
                $this->appendLine("        \$this->client->addParam(\$kparams, \"$paramName\", \$$paramName);");
            } elseif ($paramType === "file") {
                $this->appendLine("        \$this->client->addParam(\$kfiles, \"$paramName\", \$$paramName);");
            } elseif ($paramType === "array" || $paramType === "map") {
                $extraTab = "";
                if ($isOptional) {
                    $this->appendLine("        if (\$$paramName !== null) {");
                    $extraTab = "    ";
                }
                $this->appendLine("$extraTab        foreach (\$$paramName as \$index => \$obj) {");
                $this->appendLine("$extraTab            \$this->client->addParam(\$kparams, \"$paramName:\$index\", \$obj->toParams());");
                $this->appendLine("$extraTab        }");
                if ($isOptional) {
                    $this->appendLine("        }");
                }
            } else {
                $extraTab = "";
                if ($isOptional) {
                    $this->appendLine("        if (\$$paramName !== null) {");
                    $extraTab = "    ";
                }
                $this->appendLine("$extraTab        \$this->client->addParam(\$kparams, \"$paramName\", \$$paramName" . "->toParams());");
                if ($isOptional) {
                    $this->appendLine("        }");
                }
            }
        }

        if ($resultType === 'file') {
            $this->appendLine("        \$this->client->queueServiceActionCall('" . strtolower($serviceId) . "', '$action', null, \$kparams);");
            $this->appendLine();
            $this->appendLine('        return $this->client->getServeUrl();');
            $this->appendLine('    }');
            return;
        }

        $fallbackClass = 'null';
        if ($resultType === 'array') {
            $fallbackClass = "\"$arrayObjectType\"";
        } elseif ($resultType && !$this->isSimpleType($resultType)) {
            $fallbackClass = "\"$resultType\"";
        }

        $queueServiceActionCall = "        \$this->client->queueServiceActionCall(\"" . strtolower($serviceId) . "\", \"$action\", $fallbackClass, \$kparams";
        if ($haveFiles) {
            $queueServiceActionCall .= ", \$kfiles";
        }
        $this->appendLine("$queueServiceActionCall);");

        if ($enableInMultiRequest) {
            $this->appendLine("        if (\$this->client->isMultiRequest()) {");
            $this->appendLine("            return \$this->client->getMultiRequestResult();");
            $this->appendLine("        }");
        }

        $this->appendLine("        \$rawResult = \$this->client->doQueue();");
        $this->appendLine("        if (\$this->client->getConfig()->getFormat() === \\Kaltura\\Client\\Base::KALTURA_SERVICE_FORMAT_JSON) {");
        $this->appendLine("            return \\Kaltura\\Client\\ParseUtils::jsObjectToClientObject(json_decode(\$rawResult));");
        $this->appendLine("        }");
        $this->appendLine("        \$resultXmlObject = new \\SimpleXMLElement(\$rawResult);");
        $this->appendLine("        \$this->client->checkIfError(\$resultXmlObject->result);");
        switch ($resultType) {
            case 'int':
            case 'bigint':
                $this->appendLine(
                    "        return (int)\\Kaltura\\Client\\ParseUtils::unmarshalSimpleType(\$resultXmlObject->result);"
                );
                $this->appendLine("    }");
                return;
            case 'bool':
                $this->appendLine(
                    "        return (bool)\\Kaltura\\Client\\ParseUtils::unmarshalSimpleType(\$resultXmlObject->result);"
                );
                $this->appendLine("    }");
                return;
            case 'string':
                $this->appendLine(
                    "        return (string)\\Kaltura\\Client\\ParseUtils::unmarshalSimpleType(\$resultXmlObject->result);"
                );
                $this->appendLine("    }");
                return;
            case 'array':
                $this->appendLine(
                    "        \$resultObject = \\Kaltura\\Client\\ParseUtils::unmarshalArray(\$resultXmlObject->result, \"$arrayObjectType\");"
                );
                $this->appendLine("        \$this->client->validateObjectType(\$resultObject, \"$resultType\");");
                break;
            default:
                if ($resultType) {
                    $resultTypeClassInfo = $this->getTypeClassInfo($resultType);
                    $resultObjectTypeEscaped = str_replace(
                        "\\",
                        "\\\\",
                        $resultTypeClassInfo->getFullyQualifiedName()
                    );
                    $this->appendLine(
                        "        \$resultObject = \\Kaltura\\Client\\ParseUtils::unmarshalObject(\$resultXmlObject->result, \"$resultType\");"
                    );
                    $this->appendLine(
                        "        \$this->client->validateObjectType(\$resultObject, {$resultTypeClassInfo->getFullyQualifiedName()}::class);"
                    );
                }
        }

        if ($resultType && $resultType !== 'null') {
            $this->appendLine();
            $this->appendLine("        return \$resultObject;");
        }
        $this->appendLine("    }");
    }

    public function getSignature($paramNodes): string
    {
        $signature = "";
        foreach ($paramNodes as $paramNode) {
            $paramName = $paramNode->getAttribute("name");
            $paramType = $paramNode->getAttribute("type");
            $defaultValue = $paramNode->getAttribute("default");

            if ($paramType === "file") {
                $signature .= "$" . $paramName;
            }
            elseif ($this->isSimpleType($paramType)) {
                $signature .= "{$this->getPHPType($paramType)} \$$paramName";
            } elseif ($paramType === "array" || $paramType === "map") {
                $signature .= "array \$$paramName";
            } else {
                $typeClass = $this->getTypeClassInfo($paramType);
                $signature .= $typeClass->getFullyQualifiedName() . " $" . $paramName;
            }


            if ($paramNode->getAttribute("optional")) {
                if ($this->isSimpleType($paramType)) {
                    if ($defaultValue === "false") {
                        $signature .= " = false";
                    } elseif ($defaultValue === "true") {
                        $signature .= " = true";
                    } elseif ($defaultValue === "null") {
                        $signature .= " = null";
                    } elseif ($paramType === "string") {
                        $signature .= " = \"$defaultValue\"";
                    } elseif ($paramType === "int" || $paramType === "bigint" || $paramType === "float") {
                        if ($defaultValue === "") {
                            $signature .= " = \"\"";
                        } // hack for partner.getUsage
                        else {
                            $signature .= " = $defaultValue";
                        }
                    }
                } else {
                    $signature .= " = null";
                }
            }

            $signature .= ", ";
        }
        if ($this->endsWith($signature, ", ")) {
            $signature = substr($signature, 0, -2);
        }
        $signature .= ")";

        return $signature;
    }

    public function writeMainClient(DOMNodeList $serviceNodes, DOMNodeList $configurationNodes): void
    {
        $apiVersion = $this->_doc->documentElement->getAttribute('apiVersion');
        $date = date('y-m-d');

        $this->appendLine('/**');
        $this->appendLine(' * @namespace');
        $this->appendLine(' */');
        $this->appendLine('namespace Kaltura\Client;');
        $this->appendLine();

        foreach ($serviceNodes as $serviceNode) {
            if (!$this->shouldIncludeService($serviceNode->getAttribute("id"))) {
                continue;
            }

            if ($serviceNode->hasAttribute("plugin")) {
                continue;
            }

            $serviceName = ucfirst($serviceNode->getAttribute("name"));
            $this->appendLine("use Kaltura\\Client\\Service\\{$serviceName}Service;");
        }
        $this->appendLine();

        if ($this->generateDocs) {
            $this->appendLine('/**');
            $this->appendLine(" * @package $this->package");
            $this->appendLine(" * @subpackage $this->subpackage");
            $this->appendLine(' */');
        }

        $this->appendLine("class Client extends Base");
        $this->appendLine("{");

        foreach ($serviceNodes as $serviceNode) {
            if (!$this->shouldIncludeService($serviceNode->getAttribute("id"))) {
                continue;
            }

            if ($serviceNode->hasAttribute("plugin")) {
                continue;
            }

            $serviceName = $serviceNode->getAttribute("name");
            $serviceClassName = ucfirst($serviceName) . "Service";
            $this->appendLine(<<<CODE
    /**
     * @var ?$serviceClassName $$serviceName
     */
    protected ?$serviceClassName $$serviceName = null;

CODE
);
        }

        $this->appendLine("    /**");
        $this->appendLine("     * Kaltura client constructor");
        $this->appendLine("     *");
        $this->appendLine("     * @param \\Kaltura\\Client\\Configuration \$config");
        $this->appendLine("     */");
        $this->appendLine("    public function __construct(Configuration \$config)");
        $this->appendLine("    {");
        $this->appendLine("        parent::__construct(\$config);");
        $this->appendLine("        ");
        $this->appendLine("        \$this->setClientTag('php8:$date');");
        $this->appendLine("        \$this->setApiVersion('$apiVersion');");
        $this->appendLine("    }");
        $this->appendLine("    ");

        foreach ($serviceNodes as $serviceNode) {
            if (!$this->shouldIncludeService($serviceNode->getAttribute("id"))) {
                continue;
            }

            if ($serviceNode->hasAttribute("plugin")) {
                continue;
            }

            $serviceName = $serviceNode->getAttribute("name");
            $serviceClassName = ucfirst($serviceName) . "Service";
            $serviceGetter = 'get' . ucfirst($serviceName) . 'Service()';

            $this->appendLine(<<<CODE
    /**
     * @return $serviceClassName
     */
    public function $serviceGetter: $serviceClassName
    {
        if (\$this->$serviceName === null) {
            \$this->$serviceName = new $serviceClassName(\$this);
        }

        return \$this->$serviceName;
    }

CODE
);
        }

        $volatileProperties = [];
        foreach ($configurationNodes as $configurationNode) {
            /* @var $configurationNode DOMElement */
            $configurationName = $configurationNode->nodeName;
            $attributeName = lcfirst($configurationName) . "Configuration";
            $volatileProperties[$attributeName] = [];

            foreach ($configurationNode->childNodes as $configurationPropertyNode) {
                /* @var $configurationPropertyNode DOMElement */

                if ($configurationPropertyNode->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $configurationProperty = $configurationPropertyNode->localName;

                if ($configurationPropertyNode->hasAttribute('volatile') && $configurationPropertyNode->getAttribute(
                        'volatile'
                    )) {
                    $volatileProperties[$attributeName][] = $configurationProperty;
                }

                $type = $configurationPropertyNode->getAttribute('type');
                $type = $this->isSimpleType($type)
                    ? $this->getPHPType($type)
                    : $this->getTypeClassInfo($type)->getFullyQualifiedName();

                $description = null;
                if ($configurationPropertyNode->hasAttribute('description')) {
                    $description = $configurationPropertyNode->getAttribute('description');
                }

                $this->writeConfigurationProperty(
                    $configurationName,
                    $configurationProperty,
                    $configurationProperty,
                    $type,
                    $description
                );

                if ($configurationPropertyNode->hasAttribute('alias')) {
                    $this->writeConfigurationProperty(
                        $configurationName,
                        $configurationPropertyNode->getAttribute('alias'),
                        $configurationProperty,
                        $type,
                        $description
                    );
                }
            }
        }

        $this->appendLine("    /**");
        $this->appendLine("     * Clear all volatile configuration parameters");
        $this->appendLine("     */");
        $this->appendLine("    protected function resetRequest(): void");
        $this->appendLine("    {");
        $this->appendLine("        parent::resetRequest();");
        foreach ($volatileProperties as $attributeName => $properties) {
            foreach ($properties as $propertyName) {
                $this->appendLine("        unset(\$this->{$attributeName}['$propertyName']);");
            }
        }
        $this->appendLine("    }");

        $this->appendLine("}");
    }

    protected function writeConfigurationProperty(string $configurationName, string $name, string $paramName, string $type, ?string $description): void
    {
        $methodsName = ucfirst($name);

        $this->appendLine("    /**");
        if ($description) {
            $this->appendLine("     * $description");
            $this->appendLine("     * ");
        }
        $this->appendLine("     * @param $type \$$name");
        $this->appendLine("     */");
        $this->appendLine("    public function set$methodsName($type \$$name): void");
        $this->appendLine("    {");
        $this->appendLine("        \$this->{$configurationName}Configuration['$paramName'] = \$$name;");
        $this->appendLine("    }");
        $this->appendLine("    ");


        $this->appendLine("    /**");
        if ($description) {
            $this->appendLine("     * $description");
            $this->appendLine("     * ");
        }
        $this->appendLine("     * @return ?$type");
        $this->appendLine("     */");
        $this->appendLine("    public function get$methodsName(): ?$type");
        $this->appendLine("    {");
        $this->appendLine("        return \$this->{$configurationName}Configuration['$paramName'] ?? null;");
        $this->appendLine("    }");
        $this->appendLine("    ");
    }

    protected function addFile($fileName, $fileContents, $addLicense = true): void
    {
        $patterns = [
            '/@package\s+.+/',
            '/@subpackage\s+.+/',
        ];
        $replacements = [
            '@package ' . $this->package,
            '@subpackage ' . $this->subpackage,
        ];
        $fileContents = preg_replace($patterns, $replacements, $fileContents);
        parent::addFile($fileName, $fileContents, $addLicense);
    }

    public function getPHPType(string $propType): string
    {
        return match ($propType) {
            "bigint" => "int",
            default => $propType,
        };
    }

    /**
     * @param string $description
     * @return void
     */
    public function maybeGeneratePackageDocs(string $description): void
    {
        $this->appendLine();

        if ($this->generateDocs) {
            $this->appendLine('/**');
            if ($description) {
                $this->appendLine(" * " . $this->formatMultiLineComment($description, 0));
            }
            $this->appendLine(" * @package $this->package");
            $this->appendLine(" * @subpackage $this->subpackage");
            $this->appendLine(' */');
        }
    }

    private function writeXmlConstructor(string $kalturaType, DOMNodeList $childNodes): void
    {
        $this->appendLine('    private function buildFromXml(\SimpleXMLElement $xml): void');
        $this->appendLine('    {');

        foreach ($childNodes as $propertyNode) {
            if ($propertyNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $propName = $propertyNode->getAttribute("name");
            $propType = $propertyNode->getAttribute("type");
            $isMultiLingual = $propertyNode->getAttribute("multiLingual") === '1';

            switch ($propType) {
                case "file" :
                    KalturaLog::info("File attribute [$propName] are not supported for class [$kalturaType]");
                    return;

                case "int":
                case "string":
                    if ($isMultiLingual) {
                        $this->appendLine("        if (count(\$xml->$propName)) {");
                        $this->appendLine("            if (isset(\$xml->{$propName}->item) && count(\$xml->{$propName}->item)) {");
                        $this->appendLine("                \$this->multiLingual_$propName = \Kaltura\Client\ParseUtils::unmarshalArray(\$xml->$propName, '');");
                        $this->appendLine("            } else {");
                        $this->appendLine("                \$this->$propName = ($propType)\$xml->$propName;");
                        $this->appendLine("            }");
                        $this->appendLine("        }");
                        break;
                    }

                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = ($propType)\$xml->$propName;");
                    $this->appendLine("        }");
                    break;
                case "float":
                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = ($propType)\$xml->$propName;");
                    $this->appendLine("        }");
                    break;

                case "bigint":
                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = (string)\$xml->$propName;");
                    $this->appendLine("        }");
                    break;

                case "bool":
                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = !empty(\$xml->$propName) && \$xml->$propName != 'false';");
                    $this->appendLine("        }");
                    break;

                case "array":
                    $arrayType = $propertyNode->getAttribute("arrayType");
                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = [];");
                    $this->appendLine("            if (!empty(\$xml->$propName)) {");
                    $this->appendLine("                \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalArray(\$xml->$propName, \"$arrayType\");");
                    $this->appendLine("            }");
                    $this->appendLine("        }");
                    break;

                case "map":
                    $arrayType = $propertyNode->getAttribute("arrayType");
                    $this->appendLine("        if (count(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = [];");
                    $this->appendLine("            if (!empty(\$xml->$propName)) {");
                    $this->appendLine("                \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalMap(\$xml->$propName, \"$arrayType\");");
                    $this->appendLine("            }");
                    $this->appendLine("        }");
                    break;

                default: // sub object
                    $this->appendLine("        if (count(\$xml->$propName) && !empty(\$xml->$propName)) {");
                    $this->appendLine("            \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalObject(\$xml->$propName, \"$propType\");");
                    $this->appendLine("        }");
                    break;
            }
        }

        $this->appendLine('    }');
    }

    private function writeJsonConstructor(string $kalturaType, DOMNodeList $childNodes): void
    {
        $this->appendLine('    private function buildFromJson($jsonObject): void');
        $this->appendLine('    {');

        foreach ($childNodes as $propertyNode) {
            if ($propertyNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $propName = $propertyNode->getAttribute("name");
            $propType = $propertyNode->getAttribute("type");
            $isMultiLingual = $propertyNode->getAttribute("multiLingual") === '1';

            switch ($propType) {
                case "file" :
                    KalturaLog::info("File attribute [$propName] are not supported for class [$kalturaType]");
                    return;

                case "string":
                    if ($isMultiLingual) {
                        $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                        $this->appendLine("            if (is_array(\$jsonObject->{$propName})) {");
                        $this->appendLine("                \$this->multiLingual_{$propName} = \Kaltura\Client\ParseUtils::jsObjectToClientObject(\$jsonObject->$propName, '');");
                        $this->appendLine("            } else {");
                        $this->appendLine("                \$this->$propName = ($propType)\$jsonObject->$propName;");
                        $this->appendLine("            }");
                        $this->appendLine("        }");
                        break;
                    }

                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = ($propType)\$jsonObject->$propName;");
                    $this->appendLine("        }");
                    break;
                case "int":
                case "float":
                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = ($propType)\$jsonObject->$propName;");
                    $this->appendLine("        }");
                    break;

                case "bigint":
                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = (string)\$jsonObject->$propName;");
                    $this->appendLine("        }");
                    break;

                case "bool":
                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = \$jsonObject->$propName;");
                    $this->appendLine("        }");
                    break;

                case "array":
                    $arrayType = $propertyNode->getAttribute("arrayType");
                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = [];");
                    $this->appendLine("            if (!empty(\$jsonObject->$propName)) {");
                    $this->appendLine("                \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalArray(\$jsonObject->$propName, \"$arrayType\");");
                    $this->appendLine("            }");
                    $this->appendLine("        }");
                    break;

                case "map":
                    $arrayType = $propertyNode->getAttribute("arrayType");
                    $this->appendLine("        if (isset(\$jsonObject->{$propName})) {");
                    $this->appendLine("            \$this->$propName = [];");
                    $this->appendLine("            if (!empty(\$jsonObject->$propName)) {");
                    $this->appendLine("                \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalMap(\$jsonObject->$propName, \"$arrayType\");");
                    $this->appendLine("            }");
                    $this->appendLine("        }");
                    break;

                default: // sub object
                    $this->appendLine("        if (!empty(\$jsonObject->$propName)) {");
                    $this->appendLine("            \$this->$propName = \Kaltura\Client\ParseUtils::unmarshalObject(\$jsonObject->$propName, \"$propType\");");
                    $this->appendLine("        }");
                    break;
            }
        }

        $this->appendLine('    }');
    }
}