#!/usr/clearos/sandbox/usr/bin/php
<?php

/**
 * SSH Trigger for Firewall Dynamic.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage scripts
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\firewall_dynamic\Firewall_Dynamic as Firewall_Dynamic;
use \clearos\apps\ssh_server\OpenSSH as OpenSSH;

clearos_load_library('firewall_dynamic/Firewall_Dynamic');
clearos_load_library('ssh_server/OpenSSH');

// Exceptions
//-----------

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////////

//--------------------------------------------------------------------
// Command line options
//--------------------------------------------------------------------

$short_options  = '';

// Common
$short_options .= 's';   // Source IP
$short_options .= 'h';   // Help

$helpopts  = '
  Common Options
  --------------
  -s: source IP
  -h: help
';

// Handle command line options
//----------------------------

$options = getopt($short_options);

$firewall_dynamic = new Firewall_Dynamic();

$help = isset($options['h']) ? TRUE : FALSE;
$source_ip = isset($options['s']) ? $options['s'] : FALSE;

if ($help) {
    echo "usage: " . $argv[0] . " [options]\n";
    echo $helpopts;
    exit(0);
}

$substitutions = array();

try {
    $ssh = new OpenSSH();
    $substitutions['dport'] = $ssh->get_port(); 
    date_default_timezone_set("UTC");
    $datestop = date("Y-m-d\TG:i:s", strtotime("+" . $firewall_dynamic->get_window('ssh') . " minutes"));
    $substitutions['datestop'] = $datestop;
    $firewall_dynamic->create_rule('ssh', $substitutions);
    exit(0);
    echo "Complete.\n";
} catch (Exception $e) {
    echo clearos_exception_message($e);
    exit(1);
}

// vim: syntax=php
