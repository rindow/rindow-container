<?php
namespace Rindow\Container;

interface InjectType
{
    const ARGUMENT_DEFAULT   = 'default';
    const ARGUMENT_VALUE     = 'value';
    const ARGUMENT_REFERENCE = 'ref';
    const ARGUMENT_REFERENCE_IN_CONFIG = 'ref@';
    const ARGUMENT_CONFIG    = 'config';

    const SCOPE_SINGLETON = 'singleton';
    const SCOPE_PROTOTYPE = 'prototype';

    // extend for web service container
    const SCOPE_REQUEST   = 'request'; // http request
    const SCOPE_SESSION   = 'session'; // php session
    const SCOPE_GLOBAL_SESSION = 'global_session'; // global portlet session
}