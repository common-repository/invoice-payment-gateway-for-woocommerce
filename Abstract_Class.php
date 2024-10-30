<?php
namespace IPGW;

use IPGW\Interfaces\Model_Interface;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstract class that the main plugin class needs to extend.
 */
abstract class Abstract_Class {

    /**
     * Property that houses an array of all the "regular models" of the plugin.
     *
     */
    protected $__all_models = array();

    /**
     * Property that houses an array of all "public regular models" of the plugin.
     * Public models can be accessed and utilized by external entities via the main plugin class.
     *
     */
    public $models = array();

    /**
     * Add a "regular model" to the main plugin class "all models" array.
     *
     */
    public function add_to_all_plugin_models( Model_Interface $model ) {
        $class_name = get_class( $model );
        if ( !array_key_exists( $class_name , $this->__all_models ) )
            $this->__all_models[ $class_name ] = $model;
        
    }

    /**
     * Add a "regular model" to the main plugin class "public models" array.
     *
     */
    public function add_to_public_models( Model_Interface $model ) {
        
        $class_name = get_class( $model );
        if ( !array_key_exists( $class_name , $this->models ) )
            $this->models[ $class_name ] = $model;
        
    }

}
