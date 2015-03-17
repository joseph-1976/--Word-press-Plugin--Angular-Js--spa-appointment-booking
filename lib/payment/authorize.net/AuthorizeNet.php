<?php

class AuthorizeNet {

    public function autoload( $className ) {
        static $classMap;

        if ( !isset( $classMap ) ) {
            $classMap = require __DIR__ . DIRECTORY_SEPARATOR . 'classmap.php';
        }

        if ( isset( $classMap[$className] ) ) {
            include $classMap[$className];
        }
    }

    public function __construct() {
        spl_autoload_register( array($this, 'autoload') );
    }
}