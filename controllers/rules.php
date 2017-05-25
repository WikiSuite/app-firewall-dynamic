<?php

/**
 * Firewall Dynamic rules controller.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Firewall Dynamic rules controller.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage controllers
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

class Rules extends ClearOS_Controller
{
    /**
     * Firewall Dynamic rules view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('base');
        $this->lang->load('firewall_dynamic');
        $this->load->library('firewall_dynamic/Firewall_Dynamic');

        $data['rules'] = $this->firewall_dynamic->get_rules();
        $data['window_options'] = $this->firewall_dynamic->get_window_options();
        $this->page->view_form('rules', $data, lang('firewall_dynamic_rules'));
    }

    /**
     * Enables rule.
     *
     * @param string  $rule rule
     *
     * @return view
     */

    function enable($rule)
    {
        $this->load->library('firewall_dynamic/Firewall_Dynamic');
        $this->lang->load('firewall_dynamic');
        try {
            $this->firewall_dynamic->set_rule_state(TRUE, $rule);

            $this->page->set_status_enabled();
            redirect('/firewall_dynamic');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
    /**
     * Disables rule.
     *
     * @param string  $rule rule
     *
     * @return view
     */

    function disable($rule)
    {
        $this->load->library('firewall_dynamic/Firewall_Dynamic');
        $this->lang->load('firewall_dynamic');
        try {
            $this->firewall_dynamic->set_rule_state(FALSE, $rule);

            $this->page->set_status_disabled();
            redirect('/firewall_dynamic');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Rule edit controller
     *
     * @param string $rule rule name
     *
     * @return view
     */

    function edit($rule)
    {
        // Load dependencies
        //------------------

        $this->load->library('firewall_dynamic/Firewall_Dynamic');
        $this->lang->load('firewall_dynamic');
        $this->load->library('groups/Group_Engine');
        $this->load->factory('groups/Group_Manager_Factory');

        try {
            $metadata = $this->firewall_dynamic->get_rule($rule);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('window', 'firewall_dynamic/Firewall_Dynamic', 'validate_window', TRUE);
        $this->form_validation->set_policy('group', 'firewall_dynamic/Firewall_Dynamic', 'validate_group', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                $this->firewall_dynamic->set_window($rule, $this->input->post('window'));
                $this->firewall_dynamic->set_root($rule, $this->input->post('root'));
                $this->firewall_dynamic->set_group($rule, $this->input->post('group'));

                $this->page->set_status_updated();

                redirect('/firewall_dynamic');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        $data['rule'] = $rule;
        $data['metadata'] = $metadata;

        // Window
        $data['window_options'] = $this->firewall_dynamic->get_window_options();

        // Groups
        $groups = $this->group_manager->get_details();
        $group_options[-1] = lang('base_select');

        foreach ($groups as $name => $group) {
            $description = (empty($group['description'])) ? '' : ' - ' . $group['description'];
            $group_options[$name] = $name . $description;
        }

        $data['group_options'] = $group_options;

        // Load views
        //-----------

        $this->page->view_form('firewall_dynamic/edit_rule', $data, lang('base_edit'));
    }
}
