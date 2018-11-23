<?php
namespace Rindow\Container;

use ReflectionClass;
use Rindow\Annotation\NameSpaceExtractor;
use Rindow\Stdlib\FileUtil\Dir;
use Rindow\Stdlib\FileUtil\Exception\DomainException as FileUtilException;
use Rindow\Container\Exception;

class ComponentScanner
{
    protected $annotationManager;
    protected $completedListener = array();
    protected $collectListener = array();
    protected $onDemandCollectListener = array();

    public function setAnnotationManager($annotationManager)
    {
        $this->annotationManager = $annotationManager;
    }

    public function attachCollect($annotationName,$callback)
    {
        $this->collectListener[$annotationName][] = $callback;
    }

    public function attachCompleted($name,$callback)
    {
        $this->completedListener[$name][] = $callback;
    }

    public function scan(array $paths)
    {
        $dirUtil = new Dir();
        foreach ($paths as $path => $switch) {
            if($switch) {
                try {
                    $dirUtil->clawl($path,array($this,'scanFile'));
                } catch(FileUtilException $e) {
                    throw new Exception\DomainException($e->getMessage(),$e->getCode(),$e);
                }
            }
        }
        foreach ($this->completedListener as $name => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func($callback,$name);
            }
        }
    }

    public function scanFile($filename)
    {
        if(substr($filename,-4,4)!=='.php')
            return $this;
        require_once $filename;
        $parser = new NameSpaceExtractor($filename);
        $classes = $parser->getAllClass();
        if($classes==null)
            return $this;
        if($this->annotationManager==null)
            throw new Exception\DomainException('AnnotationReader is not specified.');
        foreach($classes as $class) {
            $this->scanClass($class);
        }
        return $this;
    }

    protected function scanClass($class)
    {
        $isComponent = false;
        $classRef = new ReflectionClass($class);
    
        $annos = $this->annotationManager->getClassAnnotations($classRef);
        foreach ($annos as $anno) {
            $annoName = get_class($anno);
            foreach ($this->collectListener as $name => $callbacks) {
                if($name == $annoName) {
                    foreach ($callbacks as $callback) {
                        call_user_func($callback,$name,$class,$anno,$classRef);
                    }
                }
            }
        }
    }
/*
    public function onCompile($classAnnotation,$class,$classRef)
    {
        $annoName = get_class($classAnnotation);
        foreach ($this->onDemandCollectListener as $name => $callbacks) {
            if($name == $annoName) {
                foreach ($callbacks as $callback) {
                    call_user_func($callback,$name,$class,$classAnnotation,$classRef);
                }
            }
        }
    }
*/
}