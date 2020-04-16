<?php
namespace Rindow\Container;

use Rindow\Stdlib\FileUtil\Dir;
use ArrayObject;

class ModuleManager
{
    const DEFAULT_ANNOTATION_MANAGER = 'Rindow\\Annotation\\AnnotationManager';
    const DEFAULT_CONFIG_FACTORY_CLASS = 'Rindow\\Stdlib\\Cache\\ConfigCache\\ConfigCacheFactory';
    protected $config;
    protected $enableCache = true;
    protected $configCacheFactory;
    protected $mergedConfig;
    protected $cachedConfig;
    protected $versionCache;
    protected $modules;
    protected $serviceContainer;
    protected $annotationManager;
    protected $initialized = false;
    protected $aopManager;

    public function __construct($config=null,$environment=null)
    {
        $this->config = $config;
        if(!is_array($config) && $config!=null)
            throw new \Exception('Config mus be array:'.get_class($config));
    }

    public function setConfigCacheFactory($configCacheFactory)
    {
        $this->configCacheFactory = $configCacheFactory;
    }

    protected function getConfigCacheFactory()
    {
        if($this->configCacheFactory)
            return $this->configCacheFactory;
        $config = $this->config;
        $configCacheFactoryClass = null;
        if(isset($config['module_manager'])) {
            if(isset($config['module_manager']['enableCache'])&&
               !isset($config['cache']['enableCache'])) {
                $config['cache']['enableCache'] = $config['module_manager']['enableCache'];
            }
            if(isset($config['module_manager']['configCacheFactoryClass'])) {
                $configCacheFactoryClass = $config['module_manager']['configCacheFactoryClass'];
            }
        }
        $cacheConfig = null;
        if(isset($config['cache'])) {
            $cacheConfig = $config['cache'];
            if(isset($config['cache']['configCacheFactoryClass'])) {
                $configCacheFactoryClass = $config['cache']['configCacheFactoryClass'];
            }
        }
        if($configCacheFactoryClass==null) {
            $configCacheFactoryClass = self::DEFAULT_CONFIG_FACTORY_CLASS;
        }
        $this->configCacheFactory = new $configCacheFactoryClass($cacheConfig);
        return $this->configCacheFactory;
    }

    protected function getCachedConfig()
    {
        if($this->cachedConfig)
            return $this->cachedConfig;
        $this->cachedConfig = $this->getConfigCacheFactory()->create(__CLASS__.'/config');
        return $this->cachedConfig;
    }

    public function _mergeModuleNames($names)
    {
        if(isset($this->config['module_manager']['modules'])) {
            $config = $this->config['module_manager']['modules'];
        } else {
            $config = array();
        }
        $config = array_replace_recursive($config,$names);
        $this->config['module_manager']['modules'] = $config;
    }

    public function _getModules()
    {
        return $this->modules;
    }

    public function _getConfig()
    {
        return $this->config;
    }

    public function _loadModules()
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
        foreach ($filters as $filter => $switch) {
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

        $lastVersion = $this->getVersionCache()->get('version','FIRSTLOAD');
        $currentVersion = isset($this->config['module_manager']['version'])
            ? $this->config['module_manager']['version'] : 'NONE';
        if($lastVersion!='FIRSTLOAD' && $lastVersion !== $currentVersion) {
            $this->getCachedConfig()->clear();
        }

        $staticConfig = $this->getStaticConfig();

        $this->mergedConfig = array_replace_recursive(
            $this->getConfigClosure(),
            $staticConfig);
        if($lastVersion=='FIRSTLOAD' || $lastVersion !== $currentVersion) {
            try {
                $this->checkDependency($this->mergedConfig);
            } catch(\Exception $e) {
                $staticConfig['module_manager']['version'] = 'DependencyError';
                $this->getCachedConfig()->set('staticConfig',$staticConfig);
                throw $e;
            }
        }
        if($lastVersion!=$currentVersion)
            $this->getVersionCache()->set('version',$currentVersion);
        return $this->mergedConfig;
    }

    protected function getVersionCache()
    {
        if($this->versionCache==null) {
            $this->versionCache = $this->getConfigCacheFactory()->create(__CLASS__.'/lastVersion',$forceFileCache=true);
        }
        return $this->versionCache;
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

    protected function getStaticConfig()
    {
        $generator = function ($key,$args) {
            list($moduleManager) = $args;
            $imports = $moduleManager->_getImports();
            if(isset($imports['module_manager']['modules'])) {
                $names = $imports['module_manager']['modules'];
                if(!is_array($names))
                    throw new Exception\DomainException('Invalid module names in the imports.');
                $moduleManager->_mergeModuleNames($names);
            }

            $moduleManager->_loadModules();
            $modules = $moduleManager->_getModules();

            if($modules===null)
                throw new Exception\DomainException('Modules are not loaded.');
            $tmpConfig = array();
            foreach($modules as $module) {
                if(method_exists($module,'getConfig'))
                    $tmpConfig = array_replace_recursive($tmpConfig, $module->getConfig());
            }
            $tmpConfig = array_replace_recursive($tmpConfig,$moduleManager->_getConfig());
            $tmpConfig = array_replace_recursive($tmpConfig,$imports);
            $config = $moduleManager->applyFilters($tmpConfig);
            return $config;
        };
        $config = $this->getCachedConfig()->getEx('staticConfig',$generator,array($this));
        if(isset($config['module_manager']['modules'])) {
            $this->config['module_manager']['modules'] = $config['module_manager']['modules'];
        }
        if($this->modules===null) {
            $this->_loadModules();
        }
        return $config;
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
            $this->serviceContainer = new Container(
                $containerConfig,null,null,$this->getInstanceManager(),
                null,$this->getConfigCacheFactory());
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
            $this->annotationManager = new $annotationManagerClass($this->getConfigCacheFactory());
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
        $this->aopManager = new $className($serviceContainer,null,null,null,$this->getConfigCacheFactory());
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
        $serviceContainer->setInstance('config',$config);
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
        if($this->configCacheFactory) {
            $serviceContainer->setInstance('ConfigCacheFactory',$this->configCacheFactory);
            $serviceContainer->setInstance('SimpleCache',$this->configCacheFactory->getMemCache());
        }

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
