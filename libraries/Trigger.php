<?php

/**
 * Firewall Dynamic Trigger.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage libraries
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\firewall_dynamic;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('firewall_dynamic');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Folder');
clearos_load_library('base/Engine');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Trigger wrapper.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage libraries
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

class Trigger extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FOLDER_TRIGGERS = '/var/clearos/firewall_dynamic/triggers/';

    /**
     * Trigger constructor.
     *
     * @return void
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Execute trigger.
     *
     */

    public static function execute($name, $params)
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder(self::FOLDER_TRIGGERS . $name);
        if (!$folder->exists())
            return;
        $files = $folder->get_listing();
        foreach($files as $file) {
            try {
                $args = "";
                foreach ($params as $key => $value)
                    $args .= "-$key '$value' ";
                $shell = new Shell();
                $shell->execute($folder->get_folder_name() . "/" . $file, $args);
            } catch (Exception $e) {
                clearos_log(self::LOG_TAG, "Unable to add rule: " . clearos_exception_message($e) . " - " . $args);
            }
        }
    }
}
