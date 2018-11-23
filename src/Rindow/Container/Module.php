<?php
namespace Rindow\Container;

class Module
{
    public function getConfig()
    {
        return array(
            'annotation' => array(
                'aliases' => array(
                    'Interop\\Lenient\\Container\\Annotation\\Inject'=>
                        'Rindow\\Container\\Annotation\\Inject',
                    'Interop\\Lenient\\Container\\Annotation\\Lazy'=>
                        'Rindow\\Container\\Annotation\\Lazy',
                    'Interop\\Lenient\\Container\\Annotation\\Named'=>
                        'Rindow\\Container\\Annotation\\Named',
                    'Interop\\Lenient\\Container\\Annotation\\NamedConfig'=>
                        'Rindow\\Container\\Annotation\\NamedConfig',
                    'Interop\\Lenient\\Container\\Annotation\\PostConstruct'=>
                        'Rindow\\Container\\Annotation\\PostConstruct',
                    'Interop\\Lenient\\Container\\Annotation\\Proxy'=>
                        'Rindow\\Container\\Annotation\\Proxy',
                    'Interop\\Lenient\\Container\\Annotation\\Scope'=>
                        'Rindow\\Container\\Annotation\\Scope',
                ),
            ),
        );
    }
}
