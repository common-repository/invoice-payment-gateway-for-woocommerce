<?php
namespace IPGW\Interfaces;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstraction that provides contract relating to activation.
 * Any model that needs some sort of activation must implement this interface.
 */
interface Activatable_Interface {

    /**
     * Contruct for activation.
     *
     */
    public function activate();

}