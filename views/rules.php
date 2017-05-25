<?php

/**
 * Firewall Dynamic for Webconfig rules view.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage views
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('firewall_dynamic');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('firewall_dynamic_name'),
    lang('firewall_dynamic_trigger'),
    lang('firewall_dynamic_window'),
    lang('base_description'),
    lang('firewall_dynamic_root_account'),
    lang('base_group')
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($rules as $id => $rule) {
    $state = ($rule['enabled']) ? 'disable' : 'enable';
    $state_anchor = 'anchor_' . $state;

    $item['title'] = $rule['description'];
    $item['current_state'] = (bool)$rule['enabled'];

    $item['details'] = array(
        $rule['name'],
        $rule['trigger'],
        $rule['window'] . " " . lang('base_minutes'),
        $rule['description'],
        ($rule['root'] ? lang('base_yes') : lang('base_no')),
        $rule['group'],
    );
    $item['anchors'] = button_set(
        array(
            $state_anchor("/app/firewall_dynamic/rules/$state/$id"),
            anchor_edit('/app/firewall_dynamic/rules/edit/' . $id)
        )
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options = array (
    'responsive' => array(1 => 'none', 2 => 'none', 3 => 'none'),
    'row-enable-disable' => TRUE
);

echo summary_table(
    lang('firewall_dynamic_rules'),
    NULL,
    $headers,
    $items,
    $options
);
