<?php
namespace Rindow\Container;

use Rindow\Container\Annotation\Named;
use Rindow\Stdlib\Cache\CacheHandlerTemplate;
use Rindow\Container\Exception;
use ArrayObject;

class ComponentDefinitionManager
{
    const NAMED_COMPONENT_ANNTATION = 'Rindow\\Container\\Annotation\\Named';
    //const COMPONENTS_ARE_LOADED = '__COMPONENTS_ARE_LOADED__';
    protected $annotationManager;
    protected $cacheHandler;
    protected $aliases = array();
    protected $ignored;
    public $componentsInConfig;  // *** CAUTION *** Compatibility access level to PHP5.3

    public function __construct($cachePath=null)
    {
        if($cachePath==null)
            $cachePath='';
        $this->cacheHandler = new CacheHandlerTemplate($cachePath.'ComponentDefinitionManager');
        if(empty($cachePath))
            $this->cacheHandler->setEnableCache(false);
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
        $this->cacheHandler->setEnableCache($enableCache);
    }

    public function setCachePath($cachePath)
    {
        $this->cacheHandler->setCachePath($cachePath);
    }

    public function getComponentCache()
    {
        return $this->cacheHandler->getCache('component');
    }

    public function getScannedComponentCache()
    {
        return $this->cacheHandler->getCache('scannedComponent',$forceFileCache=true);
    }

    public function attachScanner(ComponentScanner $componentScanner)
    {
        if($this->hasScanned())
            return;
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
        return isset($cache[$name]);
    }

    public function getScannedComponent($name)
    {
        $cache = $this->getScannedComponentCache();
        return $cache->get($name,false);
    }

    public function addScannedComponent($name,$component)
    {
        $cache = $this->getScannedComponentCache();
        return $cache->put($name,$component);
    }

    public function getComponent($componentName,$force=false)
    {
        $components = $this->getComponentCache();
        $manager = $this;
        $component = $components->get(
            $componentName,
            false,
            function ($cache,$componentName,&$value) use ($force, $manager) {
                $scannedComponent = $manager->getScannedComponent($componentName);
                if($scannedComponent) {
                    if(is_string($scannedComponent))
                        $value = new ComponentDefinition($scannedComponent,$manager->getAnnotationManager());
                    else
                        $value = $scannedComponent;
                    return true;
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
                    $value = new ComponentDefinition($componentConfig);
                    return true;
                }
                if($force) {
                    $value = $manager->newComponent($componentName);
                    return true;
                }
                $value = false;
                return true;
            }
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
        if(isset($components[$componentName]))
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
        if(array_key_exists('cache_path',$config)) {
            $this->setCachePath($config['cache_path']);
        }
        if(array_key_exists('enable_cache',$config)) {
            $this->setEnableCache($config['enable_cache']);
        }
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
        $components[$name] = new ComponentDefinition($componentConfig);
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
        return isset($cache['__INITIALIZED__']);
    }

    public function completedScan()
    {
        $cache = $this->getScannedComponentCache();
        $cache['__INITIALIZED__'] = true;
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
            if(isset($cache[$className]))
                throw new Exception\DomainException('duplicate component name.: '.$classRef->getFilename().'('.$classRef->getStartLine().')');
            $cache[$name] = $className;
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
}
