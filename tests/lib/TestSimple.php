<?php
/*
    Procedural wrapper for TestSimple.php
    See TestSimpleOO.php for documentation.
*/

require_once('TestSimpleOO.php');

global $__Test;
$__Test = new TestSimple();

// Expose public API for TestSimple methods as functions
function plan()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'plan'),$args); }
function ok()           { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'ok'),$args); }
function diag()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'diag'),$args); }
function web_output()   { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'web_output'),$args); }

?>
