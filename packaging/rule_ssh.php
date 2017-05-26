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
use \clearos\apps\groups\Group_Factory as Group_Factory;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\ssh_server\OpenSSH as OpenSSH;

clearos_load_library('firewall_dynamic/Firewall_Dynamic');
clearos_load_library('groups/Group_Factory');
clearos_load_library('network/Network_Utils');
clearos_load_library('ssh_server/OpenSSH');

// Exceptions
//-----------

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////////

//--------------------------------------------------------------------
// Command line options
//--------------------------------------------------------------------

$short_options  = '';

// Common
$short_options .= 'u:';   // Username
$short_options .= 's:';   // Source IP
$short_options .= 'h';   // Help

$helpopts  = '
  Common Options
  --------------
  -u: username
  -s: source IP
  -h: help
';

// Handle command line options
//----------------------------

$options = getopt($short_options);

$firewall_dynamic = new Firewall_Dynamic();

$username = isset($options['u']) ? $options['u'] : FALSE;
$source_ip = isset($options['s']) ? $options['s'] : FALSE;
$help = isset($options['h']) ? TRUE : FALSE;

if ($help) {
    echo "usage: " . $argv[0] . " [options]\n";
    echo $helpopts;
    exit(0);
}

if (!$username) {
    echo "Username required (-u <username>)\n";
    exit(1);
}

if (!$source_ip) {
    echo "Source IP (-s <ip address>)\n";
    exit(1);
}

$substitutions = array();

try {

    $ssh = new OpenSSH();
    $substitutions['s'] = $source_ip;
    $substitutions['dport'] = $ssh->get_port(); 
    date_default_timezone_set("UTC");
    $rule = $firewall_dynamic->get_rule('ssh');

    // Rule active?
    if (!$rule['enabled'])
        exit(0);

    // Check group membership
    // If not part of ssh group, don't do anything.
    if ($rule['group'] != -1) {
        $group = Group_Factory::create($rule['group']);
        $group_info = $group->get_info();

        if ($username != 'root' && !in_array($username, $group_info['core']['members']))
            exit(0);
    }

    if ($username == 'root' && !$rule['root'])
        exit(0);

    if (! Network_Utils::is_valid_ip($source_ip)) {
        echo lang('network_ip_invalid') . "\n";
        exit(1);
    }

    $datestop = date("Y-m-d\TG:i:s", strtotime("+" . $rule['window'] . " seconds"));
    $substitutions['datestop'] = $datestop;
    $firewall_dynamic->create_rule('ssh', $substitutions);
    exit(0);
} catch (Exception $e) {
    echo clearos_exception_message($e);
    exit(1);
}

// vim: syntax=php
