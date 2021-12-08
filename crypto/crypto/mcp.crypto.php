<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Crypto, (https://www.crypto.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\CP\Table;

/**
 * Crypto Module control panel
 */
class Crypto_mcp
{
  
    public $perpage = 50;

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct($switch = true)
    {
        $this->sidebar = ee('CP/Sidebar')->make();

        ee()->view->header = array(
            'toolbar_items' => array(
                'settings' => array(
                    'href' => ee('CP/URL')->make('addons/settings/crypto/settings'),
                    'title' => lang('settings')
                )
            )
        );
    }

    /**
     * Control Panel Index
     *
     * @access	public
     */
    public function index($message = '')
    {
        $table = ee('CP/Table');
        return true;
         
    }


    /*
================
     NOTES
================
*/
}

// EOF
