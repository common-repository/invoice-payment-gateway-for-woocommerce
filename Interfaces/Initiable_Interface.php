<?php
namespace IPGW\Interfaces;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstraction that provides contract relating to initialization.
 * Any model that needs some sort of initialization must implement this interface.
 */
interface Initiable_Interface {

    /**
     * Contruct for initialization.
     *
     */
    public function initialize();

}