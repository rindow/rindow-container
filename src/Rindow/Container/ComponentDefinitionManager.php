<?php
namespace Rindow\Container;

use Rindow\Container\Annotation\Named;
use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Container\Exception;
use ArrayObject;

class ComponentDefinitionManager
{
    const NAMED_COMPONENT_ANNTATION = 'Rindow\\Container\\Annotation\\Named';
    //const COMPONENTS_ARE_LOADED = '__COMPONENTS_ARE_LOADED__';
    protected $annotationManager;
    protected $configCacheFactory;
    protected $cachePath;
    protected $componentCache;
    protected $scannedComponentCache;
    protected $aliases = array();
    protected $ignored;
    public $componentsInConfig;  // *** CAUTION *** Compatibility access level to PHP5.3
    protected $scannedComponentNames=array(); // For debug;
    protected $logger;
    protected $isDebug;

    public function __construct($cachePath=null,$configCacheFactory=null)
    {
        if($cachePath)
            $this->cachePath = $cachePath;
        else
            $this->cachePath = '';
        if($configCacheFactory)
            $this->configCacheFactory = $configCacheFactory;
        else
            $this->configCacheFactory = new ConfigCacheFactory(array('enableCache'=>false));
    }

    public function setAnnotationManager($annotationManager)
    {
        $this->annotationManager = $annotationManager;
    }

    public function getAnnotationManager()
    {
        return $this->annotationManager;
    }

    public function setEnableCache($enableCache=true)
    {
        $this->configCacheFactory->setEnableCache($enableCache);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setDebug($debug)
    {
        $this->isDebug = $debug;
    }

    //public function setCachePath($cachePath)
    //{
    //    $this->configCacheFactory->setCachePath($cachePath);
    //}

    protected function debug($message, array $context = array())
    {
        if($this->logger&&$this->isDebug) {
            $this->logger->debug($message,$context);
        }
    }

    public function getComponentCache()
    {
        if($this->componentCache==null) {
            $this->componentCache = $this->configCacheFactory
                ->create($this->cachePath.'/ComponentDefinitionManager/component');
        }
        return $this->componentCache;
    }

    public function getScannedComponentCache()
    {
        if($this->scannedComponentCache==null) {
            $this->scannedComponentCache = $this->configCacheFactory
                ->create($this->cachePath.'/ComponentDefinitionManager/scannedComponent',
                    $forceFileCache=true);
        }
        return $this->scannedComponentCache;
    }

    public function attachScanner(ComponentScanner $componentScanner)
    {
        $this->debug('ComopentManager: attach scanner');
        if($this->hasScanned()) {
            $this->debug('ComopentManager: has scanned. It do not attach.');
            return;
        }
        $componentScanner->attachCollect(
            self::NAMED_COMPONENT_ANNTATION,
            array($this,'collectNamedComponent'));
        $componentScanner->attachCompleted(
            self::NAMED_COMPONENT_ANNTATION,
            array($this,'completedScan'));
    }

    public function hasScannedComponent($name)
    {
        $cache = $this->getScannedComponentCache();
        return $cache->has($name);
    }

    public function getScannedComponent($name)
    {
        $cache = $this->getScannedComponentCache();
        return $cache->get($name,false);
    }

    public function addScannedComponent($name,$component)
    {
        $cache = $this->getScannedComponentCache();
        return $cache->set($name,$component);
    }

    public function getComponent($componentName,$force=false)
    {
        $components = $this->getComponentCache();
        $manager = $this;
        $component = $components->getEx(
            $componentName,
            function ($index,$args) {
                list($componentName, $force, $manager) = $args;
                $scannedComponent = $manager->getScannedComponent($componentName);
                if($scannedComponent) {
                    if(is_string($scannedComponent))
                        return new ComponentDefinition($scannedComponent,$manager->getAnnotationManager());
                    else
                        return $scannedComponent;
                }
                if(isset($manager->componentsInConfig[$componentName])) {
                    // When a component in the ComponentCache is expired, it regenarate.
                    $componentConfig = $manager->componentsInConfig[$componentName];
                    $componentConfig['name'] = $componentName;
                    $currentConfig = $componentConfig;
                    $currentName = $componentName;
                    while(isset($currentConfig['parent'])) {
                        $parentName = $currentConfig['parent'];
                        if(!isset($manager->componentsInConfig[$parentName]))
                            throw new Exception\DomainException('No parent component definition found: '.$parentName.' in '.$currentName);
                        $currentConfig = $manager->componentsInConfig[$parentName];
                        $currentName = $parentName;
                        $componentConfig = array_replace_recursive($currentConfig, $componentConfig);
                    }
                    unset($componentConfig['parent']);
                    if(!isset($componentConfig['class']) && !isset($componentConfig['factory']))
                        $componentConfig['class'] = $componentName;
                    return new ComponentDefinition($componentConfig);
                }
                if($force) {
                    return $manager->newComponent($componentName);
                }
                return false;
            },
            array($componentName,$force,$manager)
        );
        return $component;
    }
    public function newComponent($config)
    {
        if(is_string($config))
            $config = array('name'=>$config,'class'=>$config);
        return new ComponentDefinition($config);
    }

    public function hasComponent($componentName)
    {
        $components = $this->getComponentCache();
        if($components->has($componentName))
            return true;
        // When a component in the ComponentCache is expired, it regenarate a component later.
        if(isset($this->componentsInConfig[$componentName]))
            return true;
        if($this->getScannedComponent($componentName))
            return true;
        return false;
    }

    public function setConfig($config)
    {
        //if(array_key_exists('cache_path',$config)) {
        //    $this->setCachePath($config['cache_path']);
        //}
        //if(array_key_exists('enable_cache',$config)) {
        //    $this->setEnableCache($config['enable_cache']);
        //}
        if(isset($config['aliases'])) {
            if(!is_array($config['aliases']))
                throw new Exception\DomainException('aliases field must be array.');
            $this->aliases = $config['aliases'];
        }

        if(isset($config['components'])) {
            if(!is_array($config['components']))
                throw new Exception\DomainException('components field must be array.');
            $this->componentsInConfig = $config['components'];
        }
        if(isset($config['ignored']))
            $this->ignored = $config['ignored'];
    }

    public function addComponent($name,array $componentConfig)
    {
        $components = $this->getComponentCache();
        $componentConfig['name'] = $name;
        if(!isset($componentConfig['class']) && !isset($componentConfig['factory']))
            $componentConfig['class'] = $name;
        $components->set($name,new ComponentDefinition($componentConfig));
    }

    public function resolveAlias($alias)
    {
        $recursionTracking = array();
        while(array_key_exists($alias, $this->aliases)) {
            if(!array_key_exists($alias, $recursionTracking)) {
                $recursionTracking[$alias] = true;
            } else {
                throw new Exception\DomainException('a recursion alias is detected in "'.$alias.'"');
            }
            $alias = $this->aliases[$alias];
        }
        return $alias;
    }

    public function addAlias($alias, $componentName)
    {
        if(isset($this->aliases[$alias]))
            throw new Exception\DomainException('Already defined the alias:'.$alias);
        $this->aliases[$alias] = $componentName;
    }

    public function hasScanned()
    {
        $cache = $this->getScannedComponentCache();
        return $cache->has('__INITIALIZED__');
    }

    public function completedScan()
    {
        $cache = $this->getScannedComponentCache();
        $cache->set('__INITIALIZED__','Initialized');
    }

    public function collectNamedComponent($annoName,$className,$anno,$classRef)
    {
        if($anno instanceof Named) {
            $cache = $this->getScannedComponentCache();
            if($anno->value==null) {
                $name = $className;
            } else {
                $name = $anno->value;
            }
            if($cache->has($className))
                throw new Exception\DomainException('duplicate component name.: '.$classRef->getFilename().'('.$classRef->getStartLine().')');
            $cache->set($name,$className);
            $this->scannedComponentNames[$name] = true;
            return true;
        }
        return false;
    }

    public function isIgnored($componentName)
    {
        return isset($this->ignored[$componentName]);
    }

    public function keysInConfig()
    {
        if($this->componentsInConfig==null)
            return array();
        return array_keys($this->componentsInConfig);
    }

    public function getScannedComponentNams()
    {
        return array_keys($this->scannedComponentNames);
    }
}
