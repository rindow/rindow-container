<?php
namespace Rindow\Container;

use Rindow\Stdlib\Cache\CacheHandlerTemplate;
use Rindow\Container\Exception;
use ArrayObject;

class DeclarationManager
{
    protected $runtimeComplie = true;
    protected $annotationManager;
    protected $cacheHandler;
    protected $cachePath;
    //protected $enableCache = true;
    protected $declarationCache;

    public function __construct($cachePath=null)
    {
        if($cachePath==null)
            $cachePath='';
        $this->cacheHandler = new CacheHandlerTemplate($cachePath.'DeclarationManager');
        if(empty($cachePath))
            $this->cacheHandler->setEnableCache(false);
    }

    public function setEnableCache($enableCache=true)
    {
        $this->cacheHandler->setEnableCache($enableCache);
    }

    public function setCachePath($cachePath)
    {
        $this->cacheHandler->setCachePath($cachePath);
    }

    public function getDeclarationCache()
    {
        return $this->cacheHandler->getCache('declaration');
    }

    public function setRuntimeComplie($complie = true)
    {
        $this->runtimeComplie = $complie;
    }

    public function getDeclaration($className)
    {
        $declarationCache = $this->getDeclarationCache();
        if(isset($declarationCache[$className]))
            return $declarationCache[$className];

        if(!$this->runtimeComplie)
            throw new Exception\DomainException($className.' does not defined',0);

        $declaration = $this->complieClassDelaration($className);

        $declarationCache[$className] = $declaration;
        return $declaration;
    }

    public function setDeclaration($className, ComponentDefinition $declaration)
    {
        $declarationCache = $this->getDeclarationCache();
        $declarationCache[$className] = $declaration;
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
                $declarationCache[$declaration->getClassName()] = $declaration;
            }
        }
    }
}
