<?php
namespace Rindow\Container;

use Rindow\Stdlib\Cache\CacheFactory;
use Rindow\Stdlib\Cache\CacheHandlerTemplate;
use Rindow\Stdlib\FileUtil\Dir;
use ArrayObject;

class ModuleManager
{
    const DEFAULT_ANNOTATION_MANAGER = 'Rindow\\Annotation\\AnnotationManager';
    protected $config;
    protected $cacheHandler;
    protected $mergedConfig;
    protected $cachedConfig;
    protected $modules;
    protected $serviceContainer;
    protected $annotationManager;
    protected $initialized = false;
    protected $aopManager;

    public function __construct($config=null,$environment=null)
    {
        $this->config = $config;
        if(!is_array($config) && $config!=null)
            throw new \Exception('config is not array:'.get_class($config));
            
        if(isset($config['cache']))
            CacheFactory::setConfig($config['cache']);
        $this->setupCache($config);
    }

    protected function setupCache($config)
    {
        $this->cacheHandler = new CacheHandlerTemplate(__CLASS__);
        if(isset($config['module_manager']['disable_config_cache'])) {
            $this->cacheHandler->setEnableCache(false);
        }
        if(isset($config['module_manager']['config_cache_path'])) {
            $this->cacheHandler->setCachePath($config['module_manager']['config_cache_path']);
        }
        $this->cachedConfig = $this->cacheHandler->getCache('config');
    }

    public function _getModules()
    {
        return $this->modules;
    }

    public function _getConfig()
    {
        return $this->config;
    }

    protected function loadModules()
    {
        if($this->modules!==null)
            return;

        if(!isset($this->config['module_manager']['modules']))
            throw new Exception\DomainException('Modules are not defined in module manager configuration.');

        $moduleNames = $this->config['module_manager']['modules'];
        $this->modules = array();
        if(!is_array($moduleNames)) {
            throw new Exception\InvalidArgumentException(
                'Argument must be set array. type is invalid:'.
                (is_object($moduleNames) ? get_class($moduleNames) : gettype($moduleNames))
            );
        }
        foreach($moduleNames as $className => $switch) {
            if(!$switch)
                continue;
            if(!class_exists($className))
                throw new Exception\DomainException('A class is not found:'.$className);
            $this->modules[$className] = new $className();
        }
    }

    public function applyFilters($config)
    {
        if(!isset($config['module_manager']['filters']) ||
            !is_array($config['module_manager']['filters']))
            return $config;
        $filters = $config['module_manager']['filters'];
        foreach ($filters as $filter) {
            if(!is_callable($filter))
                throw new Exception\DomainException('a filter is not callable: '.$filter);
            $config = call_user_func($filter,$config);
        }
        return $config;
    }

    public function getConfig()
    {
        if($this->mergedConfig)
            return $this->mergedConfig;

        $this->loadModules();
        $firstLoad = false;
        $staticConfig = $this->getStaticConfig($firstLoad);
        $lastVersion = isset($staticConfig['module_manager']['version'])
            ? $staticConfig['module_manager']['version'] : null;
        $currentVersion = isset($this->config['module_manager']['version'])
            ? $this->config['module_manager']['version'] : null;
        if($lastVersion !== $currentVersion) {
            CacheFactory::clearCache();
            $this->setupCache($this->config);
            $staticConfig = $this->getStaticConfig($tmp);
        }
        $this->mergedConfig = array_replace_recursive(
            $this->getConfigClosure(),
            $staticConfig);
        if($firstLoad || $lastVersion !== $currentVersion) {
            try {
                $this->checkDependency($this->mergedConfig);
            } catch(\Exception $e) {
                $staticConfig['module_manager']['version'] = 'DependencyError';
                $this->cachedConfig->put('staticConfig',$staticConfig);
                throw $e;
            }
        }
        return $this->mergedConfig;
    }

    public function _getImports()
    {
        if(!isset($this->config['module_manager']['imports']))
            return array();
        $imports = $this->config['module_manager']['imports'];
        $config = array();
        foreach ($imports as $dir => $pattern) {
            foreach(Dir::factory()->glob($dir,$pattern) as $file) {
                $config = array_replace_recursive($config,require $file);
            }
        }
        return $config;
    }

    protected function getStaticConfig(&$firstLoad)
    {
        $moduleManager = $this;
        $firstLoad = false;
        $generator = function ($cache,$key,&$config) use ($moduleManager,&$firstLoad) {
            $modules = $moduleManager->_getModules();
            if($modules===null)
                throw new Exception\DomainException('Modules are not loaded.');
            $tmpConfig = array();
            foreach($modules as $module) {
                if(method_exists($module,'getConfig'))
                    $tmpConfig = array_replace_recursive($tmpConfig, $module->getConfig());
            }
            $tmpConfig = array_replace_recursive($tmpConfig,$moduleManager->_getConfig());
            $tmpConfig = array_replace_recursive($tmpConfig,$moduleManager->_getImports());
            $config = $moduleManager->applyFilters($tmpConfig);
            $firstLoad = true;
            return true;
        };
        return $this->cachedConfig->get('staticConfig',null,$generator);
    }

    protected function getConfigClosure()
    {
        $config = array();
        foreach($this->modules as $module) {
            if(method_exists($module,'getConfigClosure'))
                $config = array_replace_recursive($config, $module->getConfigClosure());
        }
        return $config;
    }

    protected function checkDependency($config)
    {
        foreach ($config['module_manager']['modules'] as $name => $flag) {
            if(!$flag)
                continue;
            if(method_exists($this->modules[$name], 'checkDependency'))
                $this->modules[$name]->checkDependency($config,$this);
        }
    }

    protected function getInstanceManager()
    {
        return null;
    }

    //public function setServiceContainer($serviceContainer)
    //{
    //    $this->serviceContainer = $serviceContainer;
    //}

    protected function getServiceContainer()
    {
        if($this->serviceContainer)
            return $this->serviceContainer;

        $config = $this->getConfig();
        if(isset($config['container']))
            $containerConfig = $config['container'];
        else
            $containerConfig = null;
        if(isset($config['module_manager']['container'])) {
            $containerClass = $config['module_manager']['container'];
            if(!class_exists($containerClass))
                throw new Exception\DomainException('Container class not found:'.$containerClass);
            $this->serviceContainer = new $containerClass($containerConfig);
        } else {
            $this->serviceContainer = new Container($containerConfig,null,null,$this->getInstanceManager(),__CLASS__);
            $this->serviceContainer->setAnnotationManager($this->getAnnotationManager($config));
        }
        return $this->serviceContainer;
    }

    protected function getAnnotationManager($config)
    {
        if($this->annotationManager)
            return $this->annotationManager;
        if(isset($config['module_manager']['annotation_manager'])) {
            $annotationManagerClass = $config['module_manager']['annotation_manager'];
            if($annotationManagerClass===true)
                $annotationManagerClass = self::DEFAULT_ANNOTATION_MANAGER;
        } else {
            $annotationManagerClass = false;
        }
        if(!$annotationManagerClass)
            return null;
        $instanceManager = $this->getInstanceManager();
        if($instanceManager==null) {
            if(!class_exists($annotationManagerClass))
                throw new Exception\DomainException('annotationManager class not found:'.$annotationManagerClass);
            $this->annotationManager = new $annotationManagerClass();
        } else {
            $this->annotationManager = $instanceManager->get($annotationManagerClass);
        }
        if(isset($config['annotation']['aliases'])) {
            $this->annotationManager->setAliases($config['annotation']['aliases']);
        }
        return $this->annotationManager;
    }

    protected function initAopManager($serviceContainer)
    {
        if($this->aopManager)
            return $this->aopManager;
        $config = $this->getConfig();
        if(!isset($config['module_manager']['aop_manager']))
            return null;
        $className = $config['module_manager']['aop_manager'];
        if(!class_exists($className))
            throw new Exception\DomainException('Aop Manager not found:'.$className);
        if(isset($config['aop']))
            $config = $config['aop'];
        else
            $config = array();
        $this->aopManager = new $className($serviceContainer);
        $this->aopManager->setConfig($config);
        $serviceContainer->setProxyManager($this->aopManager);
        return $this->aopManager;
    }

    public function init()
    {
        if($this->initialized)
            return $this;

        $config = $this->getConfig();

        $serviceContainer = $this->getServiceContainer();
        $aopManager = $this->initAopManager($serviceContainer);
        $annotationManager = $this->getAnnotationManager($config);
        $serviceContainer->scanComponents();

        $serviceContainer->setInstance(get_class($this),$this);
        $componentManager = $serviceContainer->getComponentManager();
        $componentManager->addAlias('ModuleManager',get_class($this));
        $serviceContainer->setInstance(get_class($serviceContainer),$serviceContainer);
        $componentManager->addAlias('ServiceLocator',get_class($serviceContainer));
        if($annotationManager) {
            $serviceContainer->setInstance(get_class($annotationManager),$annotationManager);
            $componentManager->addAlias('AnnotationReader',get_class($annotationManager));
        }
        if($aopManager) {
            $serviceContainer->setInstance(get_class($aopManager),$aopManager);
            $componentManager->addAlias('AopManager',get_class($aopManager));
        }

        $serviceContainer->setInstance('config',$config);

        foreach($this->modules as $module) {
            if(method_exists($module,'init'))
                $module->init($this);
        }
        $this->initialized = true;
        if($aopManager) {
            $aopManager->dumpDebug();
        }
        return $this;
    }

    public function getServiceLocator()
    {
        $this->init();
        return $this->getServiceContainer();
    }

    public function run($moduleName=null)
    {
        $this->init();
        $config = $this->getConfig();
        if($moduleName==null && isset($config['module_manager']['autorun']))
            $moduleName = $config['module_manager']['autorun'];
        if(!isset($this->modules[$moduleName]))
            throw new Exception\DomainException('The Module is not defined:'.$moduleName);

        $instance = $this->modules[$moduleName];
        $method = 'invoke';
        $class = 'self';

        if(isset($config['module_manager']['invokables'][$moduleName])) {
            $config = $config['module_manager']['invokables'][$moduleName];
            if(is_string($config)) {
                $class = $config;
            } else if(is_array($config)) {
                if(isset($config['class']))
                    $class = $config['class'];
                if(isset($config['method']))
                    $method = $config['method'];
            } else {
                throw new Exception\DomainException('A invokable configuration must be string or array.:'.$moduleName);
            }
        }

        if($class != 'self') {
            $instance = $this->serviceContainer->get($class);
        }

        if(!method_exists($instance,$method))
            throw new Exception\DomainException('The Module do not have invokable method for invokables configuration:'.$moduleName.'::'.$method);

        if(is_array($config) && isset($config['config_injector'])) {
            $module_config = $this->serviceContainer->get('config');
            $config_injector = $config['config_injector'];
            $instance->$config_injector($module_config);
        }
        
        return $instance->$method($this);
    }
}
