<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2015 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Mustache_Engine
{
    const VERSION        = '2.9.0';
    const SPEC_VERSION   = '1.1.2';
    const PRAGMA_FILTERS      = 'FILTERS';
    const PRAGMA_BLOCKS       = 'BLOCKS';
    const PRAGMA_ANCHORED_DOT = 'ANCHORED-DOT';
        private static $knownPragmas = array(
        self::PRAGMA_FILTERS      => true,
        self::PRAGMA_BLOCKS       => true,
        self::PRAGMA_ANCHORED_DOT => true,
    );
        private $templates = array();
        private $templateClassPrefix = '__Mustache_';
    private $cache;
    private $lambdaCache;
    private $cacheLambdaTemplates = false;
    private $loader;
    private $partialsLoader;
    private $helpers;
    private $escape;
    private $entityFlags = ENT_COMPAT;
    private $charset = 'UTF-8';
    private $logger;
    private $strictCallables = false;
    private $pragmas = array();
        private $tokenizer;
    private $parser;
    private $compiler;
    public function __construct(array $options = array())
    {
        if (isset($options['template_class_prefix'])) {
            $this->templateClassPrefix = $options['template_class_prefix'];
        }
        if (isset($options['cache'])) {
            $cache = $options['cache'];
            if (is_string($cache)) {
                $mode  = isset($options['cache_file_mode']) ? $options['cache_file_mode'] : null;
                $cache = new Mustache_Cache_FilesystemCache($cache, $mode);
            }
            $this->setCache($cache);
        }
        if (isset($options['cache_lambda_templates'])) {
            $this->cacheLambdaTemplates = (bool) $options['cache_lambda_templates'];
        }
        if (isset($options['loader'])) {
            $this->setLoader($options['loader']);
        }
        if (isset($options['partials_loader'])) {
            $this->setPartialsLoader($options['partials_loader']);
        }
        if (isset($options['partials'])) {
            $this->setPartials($options['partials']);
        }
        if (isset($options['helpers'])) {
            $this->setHelpers($options['helpers']);
        }
        if (isset($options['escape'])) {
            if (!is_callable($options['escape'])) {
                throw new Mustache_Exception_InvalidArgumentException('Mustache Constructor "escape" option must be callable');
            }
            $this->escape = $options['escape'];
        }
        if (isset($options['entity_flags'])) {
            $this->entityFlags = $options['entity_flags'];
        }
        if (isset($options['charset'])) {
            $this->charset = $options['charset'];
        }
        if (isset($options['logger'])) {
            $this->setLogger($options['logger']);
        }
        if (isset($options['strict_callables'])) {
            $this->strictCallables = $options['strict_callables'];
        }
        if (isset($options['pragmas'])) {
            foreach ($options['pragmas'] as $pragma) {
                if (!isset(self::$knownPragmas[$pragma])) {
                    throw new Mustache_Exception_InvalidArgumentException(sprintf('Unknown pragma: "%s".', $pragma));
                }
                $this->pragmas[$pragma] = true;
            }
        }
    }
    public function render($template, $context = array())
    {
        return $this->loadTemplate($template)->render($context);
    }
    public function getEscape()
    {
        return $this->escape;
    }
    public function getEntityFlags()
    {
        return $this->entityFlags;
    }
    public function getCharset()
    {
        return $this->charset;
    }
    public function getPragmas()
    {
        return array_keys($this->pragmas);
    }
    public function setLoader(Mustache_Loader $loader)
    {
        $this->loader = $loader;
    }
    public function getLoader()
    {
        if (!isset($this->loader)) {
            $this->loader = new Mustache_Loader_StringLoader();
        }
        return $this->loader;
    }
    public function setPartialsLoader(Mustache_Loader $partialsLoader)
    {
        $this->partialsLoader = $partialsLoader;
    }
    public function getPartialsLoader()
    {
        if (!isset($this->partialsLoader)) {
            $this->partialsLoader = new Mustache_Loader_ArrayLoader();
        }
        return $this->partialsLoader;
    }
    public function setPartials(array $partials = array())
    {
        if (!isset($this->partialsLoader)) {
            $this->partialsLoader = new Mustache_Loader_ArrayLoader();
        }
        if (!$this->partialsLoader instanceof Mustache_Loader_MutableLoader) {
            throw new Mustache_Exception_RuntimeException('Unable to set partials on an immutable Mustache Loader instance');
        }
        $this->partialsLoader->setTemplates($partials);
    }
    public function setHelpers($helpers)
    {
        if (!is_array($helpers) && !$helpers instanceof Traversable) {
            throw new Mustache_Exception_InvalidArgumentException('setHelpers expects an array of helpers');
        }
        $this->getHelpers()->clear();
        foreach ($helpers as $name => $helper) {
            $this->addHelper($name, $helper);
        }
    }
    public function getHelpers()
    {
        if (!isset($this->helpers)) {
            $this->helpers = new Mustache_HelperCollection();
        }
        return $this->helpers;
    }
    public function addHelper($name, $helper)
    {
        $this->getHelpers()->add($name, $helper);
    }
    public function getHelper($name)
    {
        return $this->getHelpers()->get($name);
    }
    public function hasHelper($name)
    {
        return $this->getHelpers()->has($name);
    }
    public function removeHelper($name)
    {
        $this->getHelpers()->remove($name);
    }
    public function setLogger($logger = null)
    {
        if ($logger !== null && !($logger instanceof Mustache_Logger || is_a($logger, 'Psr\\Log\\LoggerInterface'))) {
            throw new Mustache_Exception_InvalidArgumentException('Expected an instance of Mustache_Logger or Psr\\Log\\LoggerInterface.');
        }
        if ($this->getCache()->getLogger() === null) {
            $this->getCache()->setLogger($logger);
        }
        $this->logger = $logger;
    }
    public function getLogger()
    {
        return $this->logger;
    }
    public function setTokenizer(Mustache_Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }
    public function getTokenizer()
    {
        if (!isset($this->tokenizer)) {
            $this->tokenizer = new Mustache_Tokenizer();
        }
        return $this->tokenizer;
    }
    public function setParser(Mustache_Parser $parser)
    {
        $this->parser = $parser;
    }
    public function getParser()
    {
        if (!isset($this->parser)) {
            $this->parser = new Mustache_Parser();
        }
        return $this->parser;
    }
    public function setCompiler(Mustache_Compiler $compiler)
    {
        $this->compiler = $compiler;
    }
    public function getCompiler()
    {
        if (!isset($this->compiler)) {
            $this->compiler = new Mustache_Compiler();
        }
        return $this->compiler;
    }
    public function setCache(Mustache_Cache $cache)
    {
        if (isset($this->logger) && $cache->getLogger() === null) {
            $cache->setLogger($this->getLogger());
        }
        $this->cache = $cache;
    }
    public function getCache()
    {
        if (!isset($this->cache)) {
            $this->setCache(new Mustache_Cache_NoopCache());
        }
        return $this->cache;
    }
    protected function getLambdaCache()
    {
        if ($this->cacheLambdaTemplates) {
            return $this->getCache();
        }
        if (!isset($this->lambdaCache)) {
            $this->lambdaCache = new Mustache_Cache_NoopCache();
        }
        return $this->lambdaCache;
    }
    public function getTemplateClassName($source)
    {
        return $this->templateClassPrefix . md5(sprintf(
            'version:%s,escape:%s,entity_flags:%i,charset:%s,strict_callables:%s,pragmas:%s,source:%s',
            self::VERSION,
            isset($this->escape) ? 'custom' : 'default',
            $this->entityFlags,
            $this->charset,
            $this->strictCallables ? 'true' : 'false',
            implode(' ', $this->getPragmas()),
            $source
        ));
    }
    public function loadTemplate($name)
    {
        return $this->loadSource($this->getLoader()->load($name));
    }
    public function loadPartial($name)
    {
        try {
            if (isset($this->partialsLoader)) {
                $loader = $this->partialsLoader;
            } elseif (isset($this->loader) && !$this->loader instanceof Mustache_Loader_StringLoader) {
                $loader = $this->loader;
            } else {
                throw new Mustache_Exception_UnknownTemplateException($name);
            }
            return $this->loadSource($loader->load($name));
        } catch (Mustache_Exception_UnknownTemplateException $e) {
                        $this->log(
                Mustache_Logger::WARNING,
                'Partial not found: "{name}"',
                array('name' => $e->getTemplateName())
            );
        }
    }
    public function loadLambda($source, $delims = null)
    {
        if ($delims !== null) {
            $source = $delims . "\n" . $source;
        }
        return $this->loadSource($source, $this->getLambdaCache());
    }
    private function loadSource($source, Mustache_Cache $cache = null)
    {
        $className = $this->getTemplateClassName($source);
        if (!isset($this->templates[$className])) {
            if ($cache === null) {
                $cache = $this->getCache();
            }
            if (!class_exists($className, false)) {
                if (!$cache->load($className)) {
                    $compiled = $this->compile($source);
                    $cache->cache($className, $compiled);
                }
            }
            $this->log(
                Mustache_Logger::DEBUG,
                'Instantiating template: "{className}"',
                array('className' => $className)
            );
            $this->templates[$className] = new $className($this);
        }
        return $this->templates[$className];
    }
    private function tokenize($source)
    {
        return $this->getTokenizer()->scan($source);
    }
    private function parse($source)
    {
        $parser = $this->getParser();
        $parser->setPragmas($this->getPragmas());
        return $parser->parse($this->tokenize($source));
    }
    private function compile($source)
    {
        $tree = $this->parse($source);
        $name = $this->getTemplateClassName($source);
        $this->log(
            Mustache_Logger::INFO,
            'Compiling template to "{className}" class',
            array('className' => $name)
        );
        $compiler = $this->getCompiler();
        $compiler->setPragmas($this->getPragmas());
        return $compiler->compile($source, $tree, $name, isset($this->escape), $this->charset, $this->strictCallables, $this->entityFlags);
    }
    private function log($level, $message, array $context = array())
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
interface Mustache_Cache
{
    public function load($key);
    public function cache($key, $value);
}
abstract class Mustache_Cache_AbstractCache implements Mustache_Cache
{
    private $logger = null;
    public function getLogger()
    {
        return $this->logger;
    }
    public function setLogger($logger = null)
    {
        if ($logger !== null && !($logger instanceof Mustache_Logger || is_a($logger, 'Psr\\Log\\LoggerInterface'))) {
            throw new Mustache_Exception_InvalidArgumentException('Expected an instance of Mustache_Logger or Psr\\Log\\LoggerInterface.');
        }
        $this->logger = $logger;
    }
    protected function log($level, $message, array $context = array())
    {
        if (isset($this->logger)) {
            $this->logger->log($level, $message, $context);
        }
    }
}
class Mustache_Cache_FilesystemCache extends Mustache_Cache_AbstractCache
{
    private $baseDir;
    private $fileMode;
    public function __construct($baseDir, $fileMode = null)
    {
        $this->baseDir = $baseDir;
        $this->fileMode = $fileMode;
    }
    public function load($key)
    {
        $fileName = $this->getCacheFilename($key);
        if (!is_file($fileName)) {
            return false;
        }
        require_once $fileName;
        return true;
    }
    public function cache($key, $value)
    {
        $fileName = $this->getCacheFilename($key);
        $this->log(
            Mustache_Logger::DEBUG,
            'Writing to template cache: "{fileName}"',
            array('fileName' => $fileName)
        );
        $this->writeFile($fileName, $value);
        $this->load($key);
    }
    protected function getCacheFilename($name)
    {
        return sprintf('%s/%s.php', $this->baseDir, $name);
    }
    private function buildDirectoryForFilename($fileName)
    {
        $dirName = dirname($fileName);
        if (!is_dir($dirName)) {
            $this->log(
                Mustache_Logger::INFO,
                'Creating Mustache template cache directory: "{dirName}"',
                array('dirName' => $dirName)
            );
            @mkdir($dirName, 0777, true);
            if (!is_dir($dirName)) {
                throw new Mustache_Exception_RuntimeException(sprintf('Failed to create cache directory "%s".', $dirName));
            }
        }
        return $dirName;
    }
    private function writeFile($fileName, $value)
    {
        $dirName = $this->buildDirectoryForFilename($fileName);
        $this->log(
            Mustache_Logger::DEBUG,
            'Caching compiled template to "{fileName}"',
            array('fileName' => $fileName)
        );
        $tempFile = tempnam($dirName, basename($fileName));
        if (false !== @file_put_contents($tempFile, $value)) {
            if (@rename($tempFile, $fileName)) {
                $mode = isset($this->fileMode) ? $this->fileMode : (0666 & ~umask());
                @chmod($fileName, $mode);
                return;
            }
            $this->log(
                Mustache_Logger::ERROR,
                'Unable to rename Mustache temp cache file: "{tempName}" -> "{fileName}"',
                array('tempName' => $tempFile, 'fileName' => $fileName)
            );
        }
        throw new Mustache_Exception_RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
    }
}
class Mustache_Cache_NoopCache extends Mustache_Cache_AbstractCache
{
    public function load($key)
    {
        return false;
    }
    public function cache($key, $value)
    {
        $this->log(
            Mustache_Logger::WARNING,
            'Template cache disabled, evaluating "{className}" class at runtime',
            array('className' => $key)
        );
        eval('?>' . $value);
    }
}
class Mustache_Compiler
{
    private $pragmas;
    private $defaultPragmas = array();
    private $sections;
    private $blocks;
    private $source;
    private $indentNextLine;
    private $customEscape;
    private $entityFlags;
    private $charset;
    private $strictCallables;
    public function compile($source, array $tree, $name, $customEscape = false, $charset = 'UTF-8', $strictCallables = false, $entityFlags = ENT_COMPAT)
    {
        $this->pragmas         = $this->defaultPragmas;
        $this->sections        = array();
        $this->blocks          = array();
        $this->source          = $source;
        $this->indentNextLine  = true;
        $this->customEscape    = $customEscape;
        $this->entityFlags     = $entityFlags;
        $this->charset         = $charset;
        $this->strictCallables = $strictCallables;
        return $this->writeCode($tree, $name);
    }
    public function setPragmas(array $pragmas)
    {
        $this->pragmas = array();
        foreach ($pragmas as $pragma) {
            $this->pragmas[$pragma] = true;
        }
        $this->defaultPragmas = $this->pragmas;
    }
    private function walk(array $tree, $level = 0)
    {
        $code = '';
        $level++;
        foreach ($tree as $node) {
            switch ($node[Mustache_Tokenizer::TYPE]) {
                case Mustache_Tokenizer::T_PRAGMA:
                    $this->pragmas[$node[Mustache_Tokenizer::NAME]] = true;
                    break;
                case Mustache_Tokenizer::T_SECTION:
                    $code .= $this->section(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_INVERTED:
                    $code .= $this->invertedSection(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_PARTIAL:
                    $code .= $this->partial(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::INDENT]) ? $node[Mustache_Tokenizer::INDENT] : '',
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_PARENT:
                    $code .= $this->parent(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::INDENT]) ? $node[Mustache_Tokenizer::INDENT] : '',
                        $node[Mustache_Tokenizer::NODES],
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_BLOCK_ARG:
                    $code .= $this->blockArg(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_BLOCK_VAR:
                    $code .= $this->blockVar(
                        $node[Mustache_Tokenizer::NODES],
                        $node[Mustache_Tokenizer::NAME],
                        $node[Mustache_Tokenizer::INDEX],
                        $node[Mustache_Tokenizer::END],
                        $node[Mustache_Tokenizer::OTAG],
                        $node[Mustache_Tokenizer::CTAG],
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_COMMENT:
                    break;
                case Mustache_Tokenizer::T_ESCAPED:
                case Mustache_Tokenizer::T_UNESCAPED:
                case Mustache_Tokenizer::T_UNESCAPED_2:
                    $code .= $this->variable(
                        $node[Mustache_Tokenizer::NAME],
                        isset($node[Mustache_Tokenizer::FILTERS]) ? $node[Mustache_Tokenizer::FILTERS] : array(),
                        $node[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_ESCAPED,
                        $level
                    );
                    break;
                case Mustache_Tokenizer::T_TEXT:
                    $code .= $this->text($node[Mustache_Tokenizer::VALUE], $level);
                    break;
                default:
                    throw new Mustache_Exception_SyntaxException(sprintf('Unknown token type: %s', $node[Mustache_Tokenizer::TYPE]), $node);
            }
        }
        return $code;
    }
    const KLASS = '<?php
        class %s extends Mustache_Template
        {
            private $lambdaHelper;%s
            public function renderInternal(Mustache_Context $context, $indent = \'\')
            {
                $this->lambdaHelper = new Mustache_LambdaHelper($this->mustache, $context);
                $buffer = \'\';
                $newContext = array();
        %s
                return $buffer;
            }
        %s
        %s
        }';
    const KLASS_NO_LAMBDAS = '<?php
        class %s extends Mustache_Template
        {%s
            public function renderInternal(Mustache_Context $context, $indent = \'\')
            {
                $buffer = \'\';
                $newContext = array();
        %s
                return $buffer;
            }
        }';
    const STRICT_CALLABLE = 'protected $strictCallables = true;';
    private function writeCode($tree, $name)
    {
        $code     = $this->walk($tree);
        $sections = implode("\n", $this->sections);
        $blocks   = implode("\n", $this->blocks);
        $klass    = empty($this->sections) && empty($this->blocks) ? self::KLASS_NO_LAMBDAS : self::KLASS;
        $callable = $this->strictCallables ? $this->prepare(self::STRICT_CALLABLE) : '';
        return sprintf($this->prepare($klass, 0, false, true), $name, $callable, $code, $sections, $blocks);
    }
    const BLOCK_VAR = '
        $blockFunction = $context->findInBlock(%s);
        if (is_callable($blockFunction)) {
            $buffer .= call_user_func($blockFunction, $context);
        } else {%s
        }
    ';
    private function blockVar($nodes, $id, $start, $end, $otag, $ctag, $level)
    {
        $id = var_export($id, true);
        return sprintf($this->prepare(self::BLOCK_VAR, $level), $id, $this->walk($nodes, $level));
    }
    const BLOCK_ARG = '$newContext[%s] = array($this, \'block%s\');';
    private function blockArg($nodes, $id, $start, $end, $otag, $ctag, $level)
    {
        $key = $this->block($nodes);
        $keystr = var_export($key, true);
        $id = var_export($id, true);
        return sprintf($this->prepare(self::BLOCK_ARG, 1), $id, $key);
    }
    const BLOCK_FUNCTION = '
        public function block%s($context)
        {
            $indent = $buffer = \'\';%s
            return $buffer;
        }
    ';
    private function block($nodes)
    {
        $code = $this->walk($nodes, 0);
        $key = ucfirst(md5($code));
        if (!isset($this->blocks[$key])) {
            $this->blocks[$key] = sprintf($this->prepare(self::BLOCK_FUNCTION, 0), $key, $code);
        }
        return $key;
    }
    const SECTION_CALL = '
        // %s section
        $value = $context->%s(%s);%s
        $buffer .= $this->section%s($context, $indent, $value);
    ';
    const SECTION = '
        private function section%s(Mustache_Context $context, $indent, $value)
        {
            $buffer = \'\';
            if (%s) {
                $source = %s;
                $result = call_user_func($value, $source, $this->lambdaHelper);
                if (strpos($result, \'{{\') === false) {
                    $buffer .= $result;
                } else {
                    $buffer .= $this->mustache
                        ->loadLambda((string) $result%s)
                        ->renderInternal($context);
                }
            } elseif (!empty($value)) {
                $values = $this->isIterable($value) ? $value : array($value);
                foreach ($values as $value) {
                    $context->push($value);
                    %s
                    $context->pop();
                }
            }
            return $buffer;
        }
    ';
    private function section($nodes, $id, $filters, $start, $end, $otag, $ctag, $level, $arg = false)
    {
        $source   = var_export(substr($this->source, $start, $end - $start), true);
        $callable = $this->getCallable();
        if ($otag !== '{{' || $ctag !== '}}') {
            $delims = ', ' . var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
        } else {
            $delims = '';
        }
        $key = ucfirst(md5($delims . "\n" . $source));
        if (!isset($this->sections[$key])) {
            $this->sections[$key] = sprintf($this->prepare(self::SECTION), $key, $callable, $source, $delims, $this->walk($nodes, 2));
        }
        if ($arg === true) {
            return $key;
        } else {
            $method  = $this->getFindMethod($id);
            $id      = var_export($id, true);
            $filters = $this->getFilters($filters, $level);
            return sprintf($this->prepare(self::SECTION_CALL, $level), $id, $method, $id, $filters, $key);
        }
    }
    const INVERTED_SECTION = '
        // %s inverted section
        $value = $context->%s(%s);%s
        if (empty($value)) {
            %s
        }
    ';
    private function invertedSection($nodes, $id, $filters, $level)
    {
        $method  = $this->getFindMethod($id);
        $id      = var_export($id, true);
        $filters = $this->getFilters($filters, $level);
        return sprintf($this->prepare(self::INVERTED_SECTION, $level), $id, $method, $id, $filters, $this->walk($nodes, $level));
    }
    const PARTIAL_INDENT = ', $indent . %s';
    const PARTIAL = '
        if ($partial = $this->mustache->loadPartial(%s)) {
            $buffer .= $partial->renderInternal($context%s);
        }
    ';
    private function partial($id, $indent, $level)
    {
        if ($indent !== '') {
            $indentParam = sprintf(self::PARTIAL_INDENT, var_export($indent, true));
        } else {
            $indentParam = '';
        }
        return sprintf(
            $this->prepare(self::PARTIAL, $level),
            var_export($id, true),
            $indentParam
        );
    }
    const PARENT = '
        %s
        if ($parent = $this->mustache->LoadPartial(%s)) {
            $context->pushBlockContext($newContext);
            $buffer .= $parent->renderInternal($context, $indent);
            $context->popBlockContext();
        }
    ';
    private function parent($id, $indent, array $children, $level)
    {
        $realChildren = array_filter($children, array(__CLASS__, 'onlyBlockArgs'));
        return sprintf(
            $this->prepare(self::PARENT, $level),
            $this->walk($realChildren, $level),
            var_export($id, true),
            var_export($indent, true)
        );
    }
    private static function onlyBlockArgs(array $node)
    {
        return $node[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_BLOCK_ARG;
    }
    const VARIABLE = '
        $value = $this->resolveValue($context->%s(%s), $context, $indent);%s
        $buffer .= %s%s;
    ';
    private function variable($id, $filters, $escape, $level)
    {
        $method  = $this->getFindMethod($id);
        $id      = ($method !== 'last') ? var_export($id, true) : '';
        $filters = $this->getFilters($filters, $level);
        $value   = $escape ? $this->getEscape() : '$value';
        return sprintf($this->prepare(self::VARIABLE, $level), $method, $id, $filters, $this->flushIndent(), $value);
    }
    const FILTER = '
        $filter = $context->%s(%s);
        if (!(%s)) {
            throw new Mustache_Exception_UnknownFilterException(%s);
        }
        $value = call_user_func($filter, $value);%s
    ';
    private function getFilters(array $filters, $level)
    {
        if (empty($filters)) {
            return '';
        }
        $name     = array_shift($filters);
        $method   = $this->getFindMethod($name);
        $filter   = ($method !== 'last') ? var_export($name, true) : '';
        $callable = $this->getCallable('$filter');
        $msg      = var_export($name, true);
        return sprintf($this->prepare(self::FILTER, $level), $method, $filter, $callable, $msg, $this->getFilters($filters, $level));
    }
    const LINE = '$buffer .= "\n";';
    const TEXT = '$buffer .= %s%s;';
    private function text($text, $level)
    {
        $indentNextLine = (substr($text, -1) === "\n");
        $code = sprintf($this->prepare(self::TEXT, $level), $this->flushIndent(), var_export($text, true));
        $this->indentNextLine = $indentNextLine;
        return $code;
    }
    private function prepare($text, $bonus = 0, $prependNewline = true, $appendNewline = false)
    {
        $text = ($prependNewline ? "\n" : '') . trim($text);
        if ($prependNewline) {
            $bonus++;
        }
        if ($appendNewline) {
            $text .= "\n";
        }
        return preg_replace("/\n( {8})?/", "\n" . str_repeat(' ', $bonus * 4), $text);
    }
    const DEFAULT_ESCAPE = 'htmlspecialchars(%s, %s, %s)';
    const CUSTOM_ESCAPE  = 'call_user_func($this->mustache->getEscape(), %s)';
    private function getEscape($value = '$value')
    {
        if ($this->customEscape) {
            return sprintf(self::CUSTOM_ESCAPE, $value);
        }
        return sprintf(self::DEFAULT_ESCAPE, $value, var_export($this->entityFlags, true), var_export($this->charset, true));
    }
    private function getFindMethod($id)
    {
        if ($id === '.') {
            return 'last';
        }
        if (isset($this->pragmas[Mustache_Engine::PRAGMA_ANCHORED_DOT]) && $this->pragmas[Mustache_Engine::PRAGMA_ANCHORED_DOT]) {
            if (substr($id, 0, 1) === '.') {
                return 'findAnchoredDot';
            }
        }
        if (strpos($id, '.') === false) {
            return 'find';
        }
        return 'findDot';
    }
    const IS_CALLABLE        = '!is_string(%s) && is_callable(%s)';
    const STRICT_IS_CALLABLE = 'is_object(%s) && is_callable(%s)';
    private function getCallable($variable = '$value')
    {
        $tpl = $this->strictCallables ? self::STRICT_IS_CALLABLE : self::IS_CALLABLE;
        return sprintf($tpl, $variable, $variable);
    }
    const LINE_INDENT = '$indent . ';
    private function flushIndent()
    {
        if (!$this->indentNextLine) {
            return '';
        }
        $this->indentNextLine = false;
        return self::LINE_INDENT;
    }
}
class Mustache_Context
{
    private $stack      = array();
    private $blockStack = array();
    public function __construct($context = null)
    {
        if ($context !== null) {
            $this->stack = array($context);
        }
    }
    public function push($value)
    {
        array_push($this->stack, $value);
    }
    public function pushBlockContext($value)
    {
        array_push($this->blockStack, $value);
    }
    public function pop()
    {
        return array_pop($this->stack);
    }
    public function popBlockContext()
    {
        return array_pop($this->blockStack);
    }
    public function last()
    {
        return end($this->stack);
    }
    public function find($id)
    {
        return $this->findVariableInStack($id, $this->stack);
    }
    public function findDot($id)
    {
        $chunks = explode('.', $id);
        $first  = array_shift($chunks);
        $value  = $this->findVariableInStack($first, $this->stack);
        foreach ($chunks as $chunk) {
            if ($value === '') {
                return $value;
            }
            $value = $this->findVariableInStack($chunk, array($value));
        }
        return $value;
    }
    public function findAnchoredDot($id)
    {
        $chunks = explode('.', $id);
        $first  = array_shift($chunks);
        if ($first !== '') {
            throw new Mustache_Exception_InvalidArgumentException(sprintf('Unexpected id for findAnchoredDot: %s', $id));
        }
        $value  = $this->last();
        foreach ($chunks as $chunk) {
            if ($value === '') {
                return $value;
            }
            $value = $this->findVariableInStack($chunk, array($value));
        }
        return $value;
    }
    public function findInBlock($id)
    {
        foreach ($this->blockStack as $context) {
            if (array_key_exists($id, $context)) {
                return $context[$id];
            }
        }
        return '';
    }
    private function findVariableInStack($id, array $stack)
    {
        for ($i = count($stack) - 1; $i >= 0; $i--) {
            $frame = &$stack[$i];
            switch (gettype($frame)) {
                case 'object':
                    if (!($frame instanceof Closure)) {
                                                                        if (method_exists($frame, $id)) {
                            return $frame->$id();
                        }
                        if (isset($frame->$id)) {
                            return $frame->$id;
                        }
                        if ($frame instanceof ArrayAccess && isset($frame[$id])) {
                            return $frame[$id];
                        }
                    }
                    break;
                case 'array':
                    if (array_key_exists($id, $frame)) {
                        return $frame[$id];
                    }
                    break;
            }
        }
        return '';
    }
}
interface Mustache_Exception
{
    }
class Mustache_Exception_InvalidArgumentException extends InvalidArgumentException implements Mustache_Exception
{
    }
class Mustache_Exception_LogicException extends LogicException implements Mustache_Exception
{
    }
class Mustache_Exception_RuntimeException extends RuntimeException implements Mustache_Exception
{
    }
class Mustache_Exception_SyntaxException extends LogicException implements Mustache_Exception
{
    protected $token;
    public function __construct($msg, array $token)
    {
        $this->token = $token;
        parent::__construct($msg);
    }
    public function getToken()
    {
        return $this->token;
    }
}
class Mustache_Exception_UnknownFilterException extends UnexpectedValueException implements Mustache_Exception
{
    protected $filterName;
    public function __construct($filterName)
    {
        $this->filterName = $filterName;
        parent::__construct(sprintf('Unknown filter: %s', $filterName));
    }
    public function getFilterName()
    {
        return $this->filterName;
    }
}
class Mustache_Exception_UnknownHelperException extends InvalidArgumentException implements Mustache_Exception
{
    protected $helperName;
    public function __construct($helperName)
    {
        $this->helperName = $helperName;
        parent::__construct(sprintf('Unknown helper: %s', $helperName));
    }
    public function getHelperName()
    {
        return $this->helperName;
    }
}
class Mustache_Exception_UnknownTemplateException extends InvalidArgumentException implements Mustache_Exception
{
    protected $templateName;
    public function __construct($templateName)
    {
        $this->templateName = $templateName;
        parent::__construct(sprintf('Unknown template: %s', $templateName));
    }
    public function getTemplateName()
    {
        return $this->templateName;
    }
}
class Mustache_HelperCollection
{
    private $helpers = array();
    public function __construct($helpers = null)
    {
        if ($helpers === null) {
            return;
        }
        if (!is_array($helpers) && !$helpers instanceof Traversable) {
            throw new Mustache_Exception_InvalidArgumentException('HelperCollection constructor expects an array of helpers');
        }
        foreach ($helpers as $name => $helper) {
            $this->add($name, $helper);
        }
    }
    public function __set($name, $helper)
    {
        $this->add($name, $helper);
    }
    public function add($name, $helper)
    {
        $this->helpers[$name] = $helper;
    }
    public function __get($name)
    {
        return $this->get($name);
    }
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new Mustache_Exception_UnknownHelperException($name);
        }
        return $this->helpers[$name];
    }
    public function __isset($name)
    {
        return $this->has($name);
    }
    public function has($name)
    {
        return array_key_exists($name, $this->helpers);
    }
    public function __unset($name)
    {
        $this->remove($name);
    }
    public function remove($name)
    {
        if (!$this->has($name)) {
            throw new Mustache_Exception_UnknownHelperException($name);
        }
        unset($this->helpers[$name]);
    }
    public function clear()
    {
        $this->helpers = array();
    }
    public function isEmpty()
    {
        return empty($this->helpers);
    }
}
class Mustache_LambdaHelper
{
    private $mustache;
    private $context;
    public function __construct(Mustache_Engine $mustache, Mustache_Context $context)
    {
        $this->mustache = $mustache;
        $this->context  = $context;
    }
    public function render($string)
    {
        return $this->mustache
            ->loadLambda((string) $string)
            ->renderInternal($this->context);
    }
}
interface Mustache_Loader
{
    public function load($name);
}
class Mustache_Loader_ArrayLoader implements Mustache_Loader, Mustache_Loader_MutableLoader
{
    private $templates;
    public function __construct(array $templates = array())
    {
        $this->templates = $templates;
    }
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }
        return $this->templates[$name];
    }
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }
    public function setTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }
}
class Mustache_Loader_CascadingLoader implements Mustache_Loader
{
    private $loaders;
    public function __construct(array $loaders = array())
    {
        $this->loaders = array();
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }
    public function addLoader(Mustache_Loader $loader)
    {
        $this->loaders[] = $loader;
    }
    public function load($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (Mustache_Exception_UnknownTemplateException $e) {
                            }
        }
        throw new Mustache_Exception_UnknownTemplateException($name);
    }
}
class Mustache_Loader_FilesystemLoader implements Mustache_Loader
{
    private $baseDir;
    private $extension = '.mustache';
    private $templates = array();
    public function __construct($baseDir, array $options = array())
    {
        $this->baseDir = $baseDir;
        if (strpos($this->baseDir, '://') === false) {
            $this->baseDir = realpath($this->baseDir);
        }
        if (!is_dir($this->baseDir)) {
            throw new Mustache_Exception_RuntimeException(sprintf('FilesystemLoader baseDir must be a directory: %s', $baseDir));
        }
        if (array_key_exists('extension', $options)) {
            if (empty($options['extension'])) {
                $this->extension = '';
            } else {
                $this->extension = '.' . ltrim($options['extension'], '.');
            }
        }
    }
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->loadFile($name);
        }
        return $this->templates[$name];
    }
    protected function loadFile($name)
    {
        $fileName = $this->getFileName($name);
        if (!file_exists($fileName)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }
        return file_get_contents($fileName);
    }
    protected function getFileName($name)
    {
        $fileName = $this->baseDir . '/' . $name;
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }
        return $fileName;
    }
}
class Mustache_Loader_InlineLoader implements Mustache_Loader
{
    protected $fileName;
    protected $offset;
    protected $templates;
    public function __construct($fileName, $offset)
    {
        if (!is_file($fileName)) {
            throw new Mustache_Exception_InvalidArgumentException('InlineLoader expects a valid filename.');
        }
        if (!is_int($offset) || $offset < 0) {
            throw new Mustache_Exception_InvalidArgumentException('InlineLoader expects a valid file offset.');
        }
        $this->fileName = $fileName;
        $this->offset   = $offset;
    }
    public function load($name)
    {
        $this->loadTemplates();
        if (!array_key_exists($name, $this->templates)) {
            throw new Mustache_Exception_UnknownTemplateException($name);
        }
        return $this->templates[$name];
    }
    protected function loadTemplates()
    {
        if ($this->templates === null) {
            $this->templates = array();
            $data = file_get_contents($this->fileName, false, null, $this->offset);
            foreach (preg_split("/^@@(?= [\w\d\.]+$)/m", $data, -1) as $chunk) {
                if (trim($chunk)) {
                    list($name, $content)         = explode("\n", $chunk, 2);
                    $this->templates[trim($name)] = trim($content);
                }
            }
        }
    }
}
interface Mustache_Loader_MutableLoader
{
    public function setTemplates(array $templates);
    public function setTemplate($name, $template);
}
class Mustache_Loader_StringLoader implements Mustache_Loader
{
    public function load($name)
    {
        return $name;
    }
}
interface Mustache_Logger
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    public function emergency($message, array $context = array());
    public function alert($message, array $context = array());
    public function critical($message, array $context = array());
    public function error($message, array $context = array());
    public function warning($message, array $context = array());
    public function notice($message, array $context = array());
    public function info($message, array $context = array());
    public function debug($message, array $context = array());
    public function log($level, $message, array $context = array());
}
abstract class Mustache_Logger_AbstractLogger implements Mustache_Logger
{
    public function emergency($message, array $context = array())
    {
        $this->log(Mustache_Logger::EMERGENCY, $message, $context);
    }
    public function alert($message, array $context = array())
    {
        $this->log(Mustache_Logger::ALERT, $message, $context);
    }
    public function critical($message, array $context = array())
    {
        $this->log(Mustache_Logger::CRITICAL, $message, $context);
    }
    public function error($message, array $context = array())
    {
        $this->log(Mustache_Logger::ERROR, $message, $context);
    }
    public function warning($message, array $context = array())
    {
        $this->log(Mustache_Logger::WARNING, $message, $context);
    }
    public function notice($message, array $context = array())
    {
        $this->log(Mustache_Logger::NOTICE, $message, $context);
    }
    public function info($message, array $context = array())
    {
        $this->log(Mustache_Logger::INFO, $message, $context);
    }
    public function debug($message, array $context = array())
    {
        $this->log(Mustache_Logger::DEBUG, $message, $context);
    }
}
class Mustache_Logger_StreamLogger extends Mustache_Logger_AbstractLogger
{
    protected static $levels = array(
        self::DEBUG     => 100,
        self::INFO      => 200,
        self::NOTICE    => 250,
        self::WARNING   => 300,
        self::ERROR     => 400,
        self::CRITICAL  => 500,
        self::ALERT     => 550,
        self::EMERGENCY => 600,
    );
    protected $level;
    protected $stream = null;
    protected $url    = null;
    public function __construct($stream, $level = Mustache_Logger::ERROR)
    {
        $this->setLevel($level);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }
    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
    public function setLevel($level)
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new Mustache_Exception_InvalidArgumentException(sprintf('Unexpected logging level: %s', $level));
        }
        $this->level = $level;
    }
    public function getLevel()
    {
        return $this->level;
    }
    public function log($level, $message, array $context = array())
    {
        if (!array_key_exists($level, self::$levels)) {
            throw new Mustache_Exception_InvalidArgumentException(sprintf('Unexpected logging level: %s', $level));
        }
        if (self::$levels[$level] >= self::$levels[$this->level]) {
            $this->writeLog($level, $message, $context);
        }
    }
    protected function writeLog($level, $message, array $context = array())
    {
        if (!is_resource($this->stream)) {
            if (!isset($this->url)) {
                throw new Mustache_Exception_LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->stream = fopen($this->url, 'a');
            if (!is_resource($this->stream)) {
                                throw new Mustache_Exception_RuntimeException(sprintf('The stream or file "%s" could not be opened.', $this->url));
                            }
        }
        fwrite($this->stream, self::formatLine($level, $message, $context));
    }
    protected static function getLevelName($level)
    {
        return strtoupper($level);
    }
    protected static function formatLine($level, $message, array $context = array())
    {
        return sprintf(
            "%s: %s\n",
            self::getLevelName($level),
            self::interpolateMessage($message, $context)
        );
    }
    protected static function interpolateMessage($message, array $context = array())
    {
        if (strpos($message, '{') === false) {
            return $message;
        }
                $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }
                return strtr($message, $replace);
    }
}
class Mustache_Parser
{
    private $lineNum;
    private $lineTokens;
    private $pragmas;
    private $defaultPragmas = array();
    private $pragmaFilters;
    private $pragmaBlocks;
    public function parse(array $tokens = array())
    {
        $this->lineNum    = -1;
        $this->lineTokens = 0;
        $this->pragmas    = $this->defaultPragmas;
        $this->pragmaFilters = isset($this->pragmas[Mustache_Engine::PRAGMA_FILTERS]);
        $this->pragmaBlocks  = isset($this->pragmas[Mustache_Engine::PRAGMA_BLOCKS]);
        return $this->buildTree($tokens);
    }
    public function setPragmas(array $pragmas)
    {
        $this->pragmas = array();
        foreach ($pragmas as $pragma) {
            $this->enablePragma($pragma);
        }
        $this->defaultPragmas = $this->pragmas;
    }
    private function buildTree(array &$tokens, array $parent = null)
    {
        $nodes = array();
        while (!empty($tokens)) {
            $token = array_shift($tokens);
            if ($token[Mustache_Tokenizer::LINE] === $this->lineNum) {
                $this->lineTokens++;
            } else {
                $this->lineNum    = $token[Mustache_Tokenizer::LINE];
                $this->lineTokens = 0;
            }
            if ($this->pragmaFilters && isset($token[Mustache_Tokenizer::NAME])) {
                list($name, $filters) = $this->getNameAndFilters($token[Mustache_Tokenizer::NAME]);
                if (!empty($filters)) {
                    $token[Mustache_Tokenizer::NAME]    = $name;
                    $token[Mustache_Tokenizer::FILTERS] = $filters;
                }
            }
            switch ($token[Mustache_Tokenizer::TYPE]) {
                case Mustache_Tokenizer::T_DELIM_CHANGE:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $this->clearStandaloneLines($nodes, $tokens);
                    break;
                case Mustache_Tokenizer::T_SECTION:
                case Mustache_Tokenizer::T_INVERTED:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $this->buildTree($tokens, $token);
                    break;
                case Mustache_Tokenizer::T_END_SECTION:
                    if (!isset($parent)) {
                        $msg = sprintf(
                            'Unexpected closing tag: /%s on line %d',
                            $token[Mustache_Tokenizer::NAME],
                            $token[Mustache_Tokenizer::LINE]
                        );
                        throw new Mustache_Exception_SyntaxException($msg, $token);
                    }
                    if ($token[Mustache_Tokenizer::NAME] !== $parent[Mustache_Tokenizer::NAME]) {
                        $msg = sprintf(
                            'Nesting error: %s (on line %d) vs. %s (on line %d)',
                            $parent[Mustache_Tokenizer::NAME],
                            $parent[Mustache_Tokenizer::LINE],
                            $token[Mustache_Tokenizer::NAME],
                            $token[Mustache_Tokenizer::LINE]
                        );
                        throw new Mustache_Exception_SyntaxException($msg, $token);
                    }
                    $this->clearStandaloneLines($nodes, $tokens);
                    $parent[Mustache_Tokenizer::END]   = $token[Mustache_Tokenizer::INDEX];
                    $parent[Mustache_Tokenizer::NODES] = $nodes;
                    return $parent;
                case Mustache_Tokenizer::T_PARTIAL:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                                        if ($indent = $this->clearStandaloneLines($nodes, $tokens)) {
                        $token[Mustache_Tokenizer::INDENT] = $indent[Mustache_Tokenizer::VALUE];
                    }
                    $nodes[] = $token;
                    break;
                case Mustache_Tokenizer::T_PARENT:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $nodes[] = $this->buildTree($tokens, $token);
                    break;
                case Mustache_Tokenizer::T_BLOCK_VAR:
                    if ($this->pragmaBlocks) {
                                                if ($parent[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_PARENT) {
                            $token[Mustache_Tokenizer::TYPE] = Mustache_Tokenizer::T_BLOCK_ARG;
                        }
                        $this->clearStandaloneLines($nodes, $tokens);
                        $nodes[] = $this->buildTree($tokens, $token);
                    } else {
                                                $token[Mustache_Tokenizer::TYPE] = Mustache_Tokenizer::T_ESCAPED;
                                                $token[Mustache_Tokenizer::NAME] = '$' . $token[Mustache_Tokenizer::NAME];
                        $nodes[] = $token;
                    }
                    break;
                case Mustache_Tokenizer::T_PRAGMA:
                    $this->enablePragma($token[Mustache_Tokenizer::NAME]);
                case Mustache_Tokenizer::T_COMMENT:
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $token;
                    break;
                default:
                    $nodes[] = $token;
                    break;
            }
        }
        if (isset($parent)) {
            $msg = sprintf(
                'Missing closing tag: %s opened on line %d',
                $parent[Mustache_Tokenizer::NAME],
                $parent[Mustache_Tokenizer::LINE]
            );
            throw new Mustache_Exception_SyntaxException($msg, $parent);
        }
        return $nodes;
    }
    private function clearStandaloneLines(array &$nodes, array &$tokens)
    {
        if ($this->lineTokens > 1) {
                        return;
        }
        $prev = null;
        if ($this->lineTokens === 1) {
                                    if ($prev = end($nodes)) {
                if (!$this->tokenIsWhitespace($prev)) {
                    return;
                }
            }
        }
        if ($next = reset($tokens)) {
                        if ($next[Mustache_Tokenizer::LINE] !== $this->lineNum) {
                return;
            }
                        if (!$this->tokenIsWhitespace($next)) {
                return;
            }
            if (count($tokens) !== 1) {
                                                if (substr($next[Mustache_Tokenizer::VALUE], -1) !== "\n") {
                    return;
                }
            }
                        array_shift($tokens);
        }
        if ($prev) {
                        return array_pop($nodes);
        }
    }
    private function tokenIsWhitespace(array $token)
    {
        if ($token[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_TEXT) {
            return preg_match('/^\s*$/', $token[Mustache_Tokenizer::VALUE]);
        }
        return false;
    }
    private function checkIfTokenIsAllowedInParent($parent, array $token)
    {
        if ($parent[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_PARENT) {
            throw new Mustache_Exception_SyntaxException('Illegal content in < parent tag', $token);
        }
    }
    private function getNameAndFilters($name)
    {
        $filters = array_map('trim', explode('|', $name));
        $name    = array_shift($filters);
        return array($name, $filters);
    }
    private function enablePragma($name)
    {
        $this->pragmas[$name] = true;
        switch ($name) {
            case Mustache_Engine::PRAGMA_BLOCKS:
                $this->pragmaBlocks = true;
                break;
            case Mustache_Engine::PRAGMA_FILTERS:
                $this->pragmaFilters = true;
                break;
        }
    }
}
abstract class Mustache_Template
{
    protected $mustache;
    protected $strictCallables = false;
    public function __construct(Mustache_Engine $mustache)
    {
        $this->mustache = $mustache;
    }
    public function __invoke($context = array())
    {
        return $this->render($context);
    }
    public function render($context = array())
    {
        return $this->renderInternal(
            $this->prepareContextStack($context)
        );
    }
    abstract public function renderInternal(Mustache_Context $context, $indent = '');
    protected function isIterable($value)
    {
        switch (gettype($value)) {
            case 'object':
                return $value instanceof Traversable;
            case 'array':
                $i = 0;
                foreach ($value as $k => $v) {
                    if ($k !== $i++) {
                        return false;
                    }
                }
                return true;
            default:
                return false;
        }
    }
    protected function prepareContextStack($context = null)
    {
        $stack = new Mustache_Context();
        $helpers = $this->mustache->getHelpers();
        if (!$helpers->isEmpty()) {
            $stack->push($helpers);
        }
        if (!empty($context)) {
            $stack->push($context);
        }
        return $stack;
    }
    protected function resolveValue($value, Mustache_Context $context, $indent = '')
    {
        if (($this->strictCallables ? is_object($value) : !is_string($value)) && is_callable($value)) {
            return $this->mustache
                ->loadLambda((string) call_user_func($value))
                ->renderInternal($context, $indent);
        }
        return $value;
    }
}
class Mustache_Tokenizer
{
        const IN_TEXT     = 0;
    const IN_TAG_TYPE = 1;
    const IN_TAG      = 2;
        const T_SECTION      = '#';
    const T_INVERTED     = '^';
    const T_END_SECTION  = '/';
    const T_COMMENT      = '!';
    const T_PARTIAL      = '>';
    const T_PARENT       = '<';
    const T_DELIM_CHANGE = '=';
    const T_ESCAPED      = '_v';
    const T_UNESCAPED    = '{';
    const T_UNESCAPED_2  = '&';
    const T_TEXT         = '_t';
    const T_PRAGMA       = '%';
    const T_BLOCK_VAR    = '$';
    const T_BLOCK_ARG    = '$arg';
        private static $tagTypes = array(
        self::T_SECTION      => true,
        self::T_INVERTED     => true,
        self::T_END_SECTION  => true,
        self::T_COMMENT      => true,
        self::T_PARTIAL      => true,
        self::T_PARENT       => true,
        self::T_DELIM_CHANGE => true,
        self::T_ESCAPED      => true,
        self::T_UNESCAPED    => true,
        self::T_UNESCAPED_2  => true,
        self::T_PRAGMA       => true,
        self::T_BLOCK_VAR    => true,
    );
        const TYPE    = 'type';
    const NAME    = 'name';
    const OTAG    = 'otag';
    const CTAG    = 'ctag';
    const LINE    = 'line';
    const INDEX   = 'index';
    const END     = 'end';
    const INDENT  = 'indent';
    const NODES   = 'nodes';
    const VALUE   = 'value';
    const FILTERS = 'filters';
    private $state;
    private $tagType;
    private $buffer;
    private $tokens;
    private $seenTag;
    private $line;
    private $otag;
    private $ctag;
    private $otagLen;
    private $ctagLen;
    public function scan($text, $delimiters = null)
    {
                        $encoding = null;
        if (function_exists('mb_internal_encoding') && ini_get('mbstring.func_overload') & 2) {
            $encoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }
        $this->reset();
        if ($delimiters = trim($delimiters)) {
            $this->setDelimiters($delimiters);
        }
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            switch ($this->state) {
                case self::IN_TEXT:
                    if ($this->tagChange($this->otag, $this->otagLen, $text, $i)) {
                        $i--;
                        $this->flushBuffer();
                        $this->state = self::IN_TAG_TYPE;
                    } else {
                        $char = $text[$i];
                        $this->buffer .= $char;
                        if ($char === "\n") {
                            $this->flushBuffer();
                            $this->line++;
                        }
                    }
                    break;
                case self::IN_TAG_TYPE:
                    $i += $this->otagLen - 1;
                    $char = $text[$i + 1];
                    if (isset(self::$tagTypes[$char])) {
                        $tag = $char;
                        $this->tagType = $tag;
                    } else {
                        $tag = null;
                        $this->tagType = self::T_ESCAPED;
                    }
                    if ($this->tagType === self::T_DELIM_CHANGE) {
                        $i = $this->changeDelimiters($text, $i);
                        $this->state = self::IN_TEXT;
                    } elseif ($this->tagType === self::T_PRAGMA) {
                        $i = $this->addPragma($text, $i);
                        $this->state = self::IN_TEXT;
                    } else {
                        if ($tag !== null) {
                            $i++;
                        }
                        $this->state = self::IN_TAG;
                    }
                    $this->seenTag = $i;
                    break;
                default:
                    if ($this->tagChange($this->ctag, $this->ctagLen, $text, $i)) {
                        $token = array(
                            self::TYPE  => $this->tagType,
                            self::NAME  => trim($this->buffer),
                            self::OTAG  => $this->otag,
                            self::CTAG  => $this->ctag,
                            self::LINE  => $this->line,
                            self::INDEX => ($this->tagType === self::T_END_SECTION) ? $this->seenTag - $this->otagLen : $i + $this->ctagLen,
                        );
                        if ($this->tagType === self::T_UNESCAPED) {
                                                        if ($this->ctag === '}}') {
                                if (($i + 2 < $len) && $text[$i + 2] === '}') {
                                    $i++;
                                } else {
                                    $msg = sprintf(
                                        'Mismatched tag delimiters: %s on line %d',
                                        $token[self::NAME],
                                        $token[self::LINE]
                                    );
                                    throw new Mustache_Exception_SyntaxException($msg, $token);
                                }
                            } else {
                                $lastName = $token[self::NAME];
                                if (substr($lastName, -1) === '}') {
                                    $token[self::NAME] = trim(substr($lastName, 0, -1));
                                } else {
                                    $msg = sprintf(
                                        'Mismatched tag delimiters: %s on line %d',
                                        $token[self::NAME],
                                        $token[self::LINE]
                                    );
                                    throw new Mustache_Exception_SyntaxException($msg, $token);
                                }
                            }
                        }
                        $this->buffer = '';
                        $i += $this->ctagLen - 1;
                        $this->state = self::IN_TEXT;
                        $this->tokens[] = $token;
                    } else {
                        $this->buffer .= $text[$i];
                    }
                    break;
            }
        }
        $this->flushBuffer();
                if ($encoding) {
            mb_internal_encoding($encoding);
        }
        return $this->tokens;
    }
    private function reset()
    {
        $this->state   = self::IN_TEXT;
        $this->tagType = null;
        $this->buffer  = '';
        $this->tokens  = array();
        $this->seenTag = false;
        $this->line    = 0;
        $this->otag    = '{{';
        $this->ctag    = '}}';
        $this->otagLen = 2;
        $this->ctagLen = 2;
    }
    private function flushBuffer()
    {
        if (strlen($this->buffer) > 0) {
            $this->tokens[] = array(
                self::TYPE  => self::T_TEXT,
                self::LINE  => $this->line,
                self::VALUE => $this->buffer,
            );
            $this->buffer   = '';
        }
    }
    private function changeDelimiters($text, $index)
    {
        $startIndex = strpos($text, '=', $index) + 1;
        $close      = '=' . $this->ctag;
        $closeIndex = strpos($text, $close, $index);
        $this->setDelimiters(trim(substr($text, $startIndex, $closeIndex - $startIndex)));
        $this->tokens[] = array(
            self::TYPE => self::T_DELIM_CHANGE,
            self::LINE => $this->line,
        );
        return $closeIndex + strlen($close) - 1;
    }
    private function setDelimiters($delimiters)
    {
        list($otag, $ctag) = explode(' ', $delimiters);
        $this->otag = $otag;
        $this->ctag = $ctag;
        $this->otagLen = strlen($otag);
        $this->ctagLen = strlen($ctag);
    }
    private function addPragma($text, $index)
    {
        $end    = strpos($text, $this->ctag, $index);
        $pragma = trim(substr($text, $index + 2, $end - $index - 2));
                array_unshift($this->tokens, array(
            self::TYPE => self::T_PRAGMA,
            self::NAME => $pragma,
            self::LINE => 0,
        ));
        return $end + $this->ctagLen - 1;
    }
    private function tagChange($tag, $tagLen, $text, $index)
    {
        return substr($text, $index, $tagLen) === $tag;
    }
}
