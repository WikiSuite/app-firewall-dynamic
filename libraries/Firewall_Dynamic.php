<?php

/**
 * Firewall Dynamic for Webconfig.
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

use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\groups\Group_Factory as Group_Factory;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\openvpn\OpenVPN as OpenVPN;

clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Engine');
clearos_load_library('groups/Group_Factory');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall Dynamic for Webconfig.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage libraries
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

class Firewall_Dynamic extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const LOG_TAG = 'firewall-dynamic';
    const FOLDER_RULES = '/var/clearos/firewall_dynamic/rules/';
    const FOLDER_TRIGGERS = '/var/clearos/firewall_dynamic/triggers/';
    const FILE_CONFIG = '/etc/clearos/firewall.d/10-firewall-dynamic';
    const DEFAULT_WINDOW = 30; // Default Window of 30 minutes until timeout
    const CMD_IPTABLES = '/sbin/iptables';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $configuration = array();
    protected $ipv4_index = -1;
    protected $ipv6_index = -1;
    protected $is_loaded = FALSE;
    protected $commands = array();

    /**
     * Firewall_Dynamic constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Get rule.
     *
     * @param String rule
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function get_rule($rule)
    {
        $rules = $this->get_rules();
        return $rules[$rule];
    }

    /**
     * Get rules.
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function get_rules()
    {
        clearos_profile(__METHOD__, __LINE__);
        $rules = array();
        $folder = new Folder(self::FOLDER_RULES);
        $files = $folder->get_listing();
        foreach($files as $file) {
            $file = new File(self::FOLDER_RULES . $file);
                
            $xml_source = $file->get_contents();

            $xml = simplexml_load_string($xml_source);
            if ($xml === FALSE)
                throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);

            $name = (String)$xml->attributes()->name;
            $basename = (String)$xml->attributes()->basename;
            if (!empty($basename))
                clearos_load_language($basename);
            $rules[$name] = array(
                'name' => empty($basename) ? (String)$xml->name : lang((String)$xml->name),
                'description' => empty($basename) ? (String)$xml->description : lang((String)$xml->description),
                'trigger' => empty($basename) ? (String)$xml->trigger : lang((String)$xml->trigger),
                'enabled' => (int)$xml->enabled,
                'window' => (int)$xml->window,
            );
            if (isset($xml->root))
                $rules[$name]['root'] = (int)$xml->root;
            if (isset($xml->group))
                $rules[$name]['group'] = (String)$xml->group;
        }
        return $rules;
    }

    /**
     * Set rule window.
     *
     * @param String  rule
     * @param int     window
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_window($rule, $window)
    {
        Validation_Exception::is_valid($this->validate_window($window));

        $file = new File(self::FOLDER_RULES . $rule . '.xml');
        if (!$file->exists())
            throw new Engine_Exception(lang('firewall_dynamic_rule_not_found'), CLEAROS_ERROR);
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);
        if ($xml === FALSE)
            throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);
        $xml->window = $window;
        $xml->asXML(self::FOLDER_RULES . $rule . '.xml');
    }

    /**
     * Set root - whether rule should apply to root account.
     *
     * @param String  rule
     * @param Boolean enabled
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_root($rule, $enabled)
    {
        if ($enabled === 'on' || $enabled == 1 || $enabled == TRUE)
            $enabled = 1;
        else
            $enabled = 0;

        $file = new File(self::FOLDER_RULES . $rule . '.xml');
        if (!$file->exists())
            throw new Engine_Exception(lang('firewall_dynamic_rule_not_found'), CLEAROS_ERROR);
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);
        if ($xml === FALSE)
            throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);
        $xml->root = $enabled;
        $xml->asXML(self::FOLDER_RULES . $rule . '.xml');
    }

    /**
     * Set rule group.
     *
     * @param String  rule
     * @param String  group
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_group($rule, $group)
    {
        Validation_Exception::is_valid($this->validate_group($group));

        $file = new File(self::FOLDER_RULES . $rule . '.xml');
        if (!$file->exists())
            throw new Engine_Exception(lang('firewall_dynamic_rule_not_found'), CLEAROS_ERROR);
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);
        if ($xml === FALSE)
            throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);
        $xml->group = $group;
        $xml->asXML(self::FOLDER_RULES . $rule . '.xml');
    }

    /**
     * Set rule state.
     *
     * @param boolean state
     * @param String  rule
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_rule_state($state, $rule)
    {
        $file = new File(self::FOLDER_RULES . $rule . '.xml');
        if (!$file->exists())
            throw new Engine_Exception(lang('firewall_dynamic_rule_not_found'), CLEAROS_ERROR);
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);

        if ($xml === FALSE)
            throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);

        $basename = (String)$xml->attributes()->basename;
        if ($rule == 'openvpn' && !clearos_load_library('openvpn/OpenVPN'))
            throw new Engine_Exception(lang('firewall_dynamic_app_not_installed'), CLEAROS_ERROR);
        $xml->enabled = $state;
        $xml->asXML(self::FOLDER_RULES . $rule . '.xml');
    }

    /**
     * Create firewall (iptables) rule structure.
     *
     * @param String rule
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function create_rule($rule, $substitutions = [])
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FOLDER_RULES . $rule . '.xml');
        if (!$file->exists())
            throw new Engine_Exception(lang('firewall_dynamic_rule_not_found'), CLEAROS_ERROR);
            
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);
        if ($xml === FALSE)
            throw new Engine_Exception(lang('firewall_dynamic_invalid_rule'), CLEAROS_ERROR);

        foreach ($xml->table as $table) {
            foreach ($table->chain as $chain) {
                foreach ($chain->rule as $rule) {
                    $args = "-t " . $table->attributes()->name . " ";
                    if ($xml->position == 'INSERT')
                        $args .= "-I " . $chain->attributes()->name . " ";
                    else
                        $args .= "-A " . $chain->attributes()->name . " ";
                    foreach ($rule->conditions->match as $match) {
                        if ($match->attributes()->explicit == null) {
                            foreach($match->children() as $key => $value) {
                                $key = (String)$key;
                                $value = (String)$value;
                                if (empty($value) && !array_key_exists($key, $substitutions))
                                    continue;
                                $args .= "-$key " . (array_key_exists($key, $substitutions) ? $substitutions[$key] : $value) . " ";
                            }
                            continue;
                            
                        }
                        $params = "";
                        foreach($match->children() as $key => $value) {
                            $key = (String)$key;
                            $value = (String)$value;
                            if (empty($value) && !array_key_exists($key, $substitutions))
                                continue;
                            $params .= "--$key " . (array_key_exists($key, $substitutions) ? $substitutions[$key] : $value) . " ";
                        }
                        if (!empty($params))
                            $args .= "-m " . (String)$match->attributes()->explicit . " " . $params;
                    }

                    if ($rule->jump != null)
                        $args .= "-j " . (String)$rule->jump;
                    try {
                        $shell = new Shell();
                        $shell->execute(self::CMD_IPTABLES, " -w " . $args, TRUE);
                    } catch (Exception $e) {
                        clearos_log(self::LOG_TAG, "Unable to add rule: " . clearos_exception_message($e) . " - " . $args);
                        return;
                    }

                    $this->_add_rule($xml->version, self::CMD_IPTABLES . " -w " .  $args);
                }
            }
        }
    }

    /**
     * Get window options.
     *
     * @return array of times
     * @throws Engine_Exception
     */

    public function get_window_options()
    {
        clearos_profile(__METHOD__, __LINE__);
        $options = array(
            60 => "1 " . strtolower(lang('base_minute')),
            180 => "3 " . strtolower(lang('base_minutes')),
            300 => "5 " . strtolower(lang('base_minutes')),
            600 => "10 " . strtolower(lang('base_minutes')),
            1800 => "30 " . strtolower(lang('base_minutes')),
            3600 => "1 " . strtolower(lang('base_hour')),
            7200 => "2 " . strtolower(lang('base_hours')),
            14400 => "4 " . strtolower(lang('base_hours')),
            28800 => "8 " . strtolower(lang('base_hours')),
        );
        return $options;
    }

    /**
     * Purge firewall rules.
     *
     * @throws Engine_Exception
     */

    public function purge_rules()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        date_default_timezone_set("UTC");
        $now = date("Y-m-d\TG:i:s");
        foreach ($this->configuration as $index => $line) {
            if (key($line) != 'ipv4' && key($line) != 'ipv6')
                continue;
            if (preg_match("/.*datestop (\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d).*/", current($line), $match)) {
                if (strtotime($now) > strtotime($match[1]))
                    unset($this->configuration[$index]);
            }
        }
        $this->_save_configuration();
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Load configuration file
     *
     * @return void;
     */

    function _load_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->configuration = array();

        try {
            $file = new File(self::FILE_CONFIG);
            $lines = $file->get_contents_as_array();
            $type = 'unknown';
            $index = 0;
            foreach ($lines as $line) {
                if (preg_match("/.*FW_PROTO.*ipv4.*/", $line)) {
                    $this->ipv4_index = $index;
                    $this->configuration[] = array('bash' => $line);
                    $type = 'ipv4';
                    $index++;
                    continue;
                } else if (preg_match("/.*FW_PROTO.*ipv6.*/", $line)) {
                    $this->ipv6_index = $index;
                    $this->configuration[] = array('bash' => $line);
                    $index++;
                    $type = 'ipv6';
                    continue;
                } else if (preg_match("/^fi$/", $line)) {
                    $this->configuration[] = array('bash' => $line);
                    $type = 'unknown';
                    $index++;
                    continue;
                }
                $this->configuration[] = array($type => $line);
                $index++;
            }
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Add new rule
     *
     * @param String  $type        ipv4 or ipv6
     * @param String  $entry       line entry
     *
     * @return void
     * @throws Engine_Exception
     */

    function _add_rule($type, $entry)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_configuration();

        array_splice(
            $this->configuration,
            (1 + ($type == 'ipv4' ? $this->ipv4_index : $this->ipv6_index)),
            0,
            $entry
        );

        $this->_save_configuration();
    }

    /**
     * Save configuration file
     *
     * @return void;
     */

    function _save_configuration()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Delete any old temp file lying around
        //--------------------------------------

        $file = new File(self::FILE_CONFIG);

        if ($file->exists())
            $file->delete();

        // Create temp file
        //-----------------

        $file->create('root', 'root', '0755');

        // Write out the file
        //-------------------

        $contents = array();
        foreach ($this->configuration as $line) {
            if (is_array($line)) {
                if (key($line) == 'bash' || key($line) == 'unknown')
                    $contents[] = current($line);
                else
                    $contents[] = "\t" . trim(current($line));
            } else {
                $contents[] = "\t" . trim($line);
            }
        }
        $file->dump_contents_from_array($contents);
        $this->is_loaded = FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for window.
     *
     * @param integer $window window time in minutes
     *
     * @return mixed void if window is valid, errmsg otherwise
     */

    function validate_window($window)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_numeric($window) || $window <= 0)
            return lang('firewall_dynamic_window') . ' - ' . lang('base_invalid');
    }

    /**
     * Validation routine for a group.
     *
     * @param string $group a system group
     *
     * @return mixed void if group is valid, errmsg otherwise
     */

    function validate_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($group == -1)
            return;

        $group = Group_Factory::create($group);

        if (! $group->exists())
            return lang('base_group') . ' - ' . lang('base_invalid');
    }

}
