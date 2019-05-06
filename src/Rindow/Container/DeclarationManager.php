<?php
namespace Rindow\Container;

use Rindow\Stdlib\Cache\ConfigCache\ConfigCacheFactory;
use Rindow\Container\Exception;
use ArrayObject;

class DeclarationManager
{
    protected $runtimeComplie = true;
    protected $annotationManager;
    protected $configCacheFactory;
    protected $cachePath;
    //protected $enableCache = true;
    protected $declarationCache;

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
        //$this->configCacheFactory = new configCacheFactoryTemplate($cachePath.'DeclarationManager');
    }

    public function setEnableCache($enableCache=true)
    {
        $this->configCacheFactory->setEnableCache($enableCache);
    }

    public function setCachePath($cachePath)
    {
        $this->configCacheFactory->setCachePath($cachePath);
    }

    public function getDeclarationCache()
    {
        if($this->declarationCache==null) {
            $this->declarationCache = $this->configCacheFactory
                ->create($this->cachePath.'/DeclarationManager/declaration');
        }
        return $this->declarationCache;
    }

    public function setRuntimeComplie($complie = true)
    {
        $this->runtimeComplie = $complie;
    }

    public function getDeclaration($className)
    {
        $declarationCache = $this->getDeclarationCache();
        if($declarationCache->has($className))
            return $declarationCache->get($className);

        if(!$this->runtimeComplie)
            throw new Exception\DomainException($className.' does not defined',0);

        $declaration = $this->complieClassDelaration($className);

        $declarationCache->set($className,$declaration);
        return $declaration;
    }

    public function setDeclaration($className, ComponentDefinition $declaration)
    {
        $declarationCache = $this->getDeclarationCache();
        $declarationCache->set($className,$declaration);
    }

    public function complieClassDelaration($className)
    {
        return new ComponentDefinition($className,$this->annotationManager);
    }

    public function setAnnotationManager($annotationManager)
    {
        $this->annotationManager = $annotationManager;
    }
    
    public function setConfig($config)
    {
        if(array_key_exists('runtime_complie',$config)) {
            $this->setRuntimeComplie($config['runtime_complie']);
        }
        if(array_key_exists('cache_path',$config)) {
            $this->setCachePath($config['cache_path']);
        }
        if(array_key_exists('enable_cache',$config)) {
            $this->setEnableCache($config['enable_cache']);
        }
        if(isset($config['declarations'])) {
            $declarationCache = $this->getDeclarationCache();
            foreach($config['declarations'] as $className => $defConfig) {
                $declaration = $this->complieClassDelaration($defConfig);
                $declarationCache->set($declaration->getClassName(),$declaration);
            }
        }
    }
}
