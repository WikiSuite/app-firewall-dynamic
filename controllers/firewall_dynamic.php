<?php

/**
 * Firewall Dynamic controller.
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
 * Firewall Dynamic controller.
 *
 * @category   apps
 * @package    firewall-dynamic
 * @subpackage controllers
 * @author     eGloo <team@egloo.ca>
 * @copyright  2017 Avantech
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/firewall_dynamic/
 */

class Firewall_Dynamic extends ClearOS_Controller
{
    /**
     * Firewall Dynamic default view.
     *
     * @return view
     */

    function index()
    {
        // Load libraries
        //---------------

        $this->lang->load('firewall_dynamic');
        $this->load->library('firewall_dynamic/Firewall_Dynamic');

        // Load views
        //-----------

        $views = array('firewall_dynamic/rules');

        $this->page->view_forms($views, lang('firewall_dynamic_app_name'));
    }

}
