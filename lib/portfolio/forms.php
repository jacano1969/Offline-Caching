<?php
/**
 * Moodle - Modular Object-Oriented Dynamic Learning Environment
 *          http://moodle.org
 * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    moodle
 * @subpackage portfolio
 * @author     Penny Leach <penny@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 * This file contains all the form definitions used by the portfolio code.
 */

// make sure we include moodleform first!
require_once ($CFG->libdir.'/formslib.php');

/**
* During-export config form.
*
* This is the form that is actually used while exporting.
* Plugins and callers don't get to define their own class
* as we have to handle form elements from both places
* See the docs here for more information:
* http://docs.moodle.org/en/Development:Writing_a_Portfolio_Plugin#has_export_config
* http://docs.moodle.org/en/Development:Adding_a_Portfolio_Button_to_a_page#has_export_config
*/
final class portfolio_export_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;
        $mform->addElement('hidden', 'stage', PORTFOLIO_STAGE_CONFIG);
        $mform->addElement('hidden', 'instance', $this->_customdata['instance']->get('id'));

        if (array_key_exists('formats', $this->_customdata) && is_array($this->_customdata['formats'])) {
            if (count($this->_customdata['formats']) > 1) {
                $options = array();
                foreach ($this->_customdata['formats'] as $key) {
                    $options[$key] = get_string('format_' . $key, 'portfolio');
                }
                $mform->addElement('select', 'format', get_string('availableformats', 'portfolio'), $options);
            } else {
                $f = array_shift($this->_customdata['formats']);
                $mform->addElement('hidden', 'format', $f);
            }
        }

        // only display the option to wait or not if it's applicable
        if (array_key_exists('expectedtime', $this->_customdata)
            && $this->_customdata['expectedtime'] != PORTFOLIO_TIME_LOW
            && $this->_customdata['expectedtime'] != PORTFOLIO_TIME_FORCEQUEUE) {
            $radioarray = array();
            $radioarray[] = &MoodleQuickForm::createElement('radio', 'wait', '', get_string('wait', 'portfolio'), 1);
            $radioarray[] = &MoodleQuickForm::createElement('radio', 'wait', '', get_string('dontwait', 'portfolio'),  0);
            $mform->addGroup($radioarray, 'radioar', get_string('wanttowait_' . $this->_customdata['expectedtime'], 'portfolio') , array(' '), false);
            $mform->setDefault('wait', 0);
        } else {
            if ($this->_customdata['expectedtime'] == PORTFOLIO_TIME_LOW) {
                $mform->addElement('hidden', 'wait', 1);
            } else {
                $mform->addElement('hidden', 'wait', 0);
            }
        }

        if (array_key_exists('plugin', $this->_customdata) && is_object($this->_customdata['plugin'])) {
            $this->_customdata['plugin']->export_config_form($mform, $this->_customdata['userid']);
        }

        if (array_key_exists('caller', $this->_customdata) && is_object($this->_customdata['caller'])) {
            $this->_customdata['caller']->export_config_form($mform, $this->_customdata['instance'], $this->_customdata['userid']);
        }

        $this->add_action_buttons(true, get_string('next'));
    }

    public function validation($data) {

        $errors = array();

        if (array_key_exists('plugin', $this->_customdata) && is_object($this->_customdata['plugin'])) {
            $pluginerrors = $this->_customdata['plugin']->export_config_validation($data);
            if (is_array($pluginerrors)) {
                $errors = $pluginerrors;
            }
        }
        if (array_key_exists('caller', $this->_customdata) && is_object($this->_customdata['caller'])) {
            $callererrors = $this->_customdata['caller']->export_config_validation($data);
            if (is_array($callererrors)) {
                $errors = array_merge($errors, $callererrors);
            }
        }
        return $errors;
    }
}

/**
* Admin config form
*
* This form is extendable by plugins who want the admin to be able to configure more than just the name of the instance.
* This is NOT done by subclassing this class, see the docs for portfolio_plugin_base for more information:
* http://docs.moodle.org/en/Development:Writing_a_Portfolio_Plugin#has_admin_config
*/
final class portfolio_admin_form extends moodleform {

    protected $instance;
    protected $plugin;

    public function definition() {
        global $CFG;
        $this->plugin = $this->_customdata['plugin'];
        $this->instance = (isset($this->_customdata['instance'])
                && is_subclass_of($this->_customdata['instance'], 'portfolio_plugin_base'))
            ? $this->_customdata['instance'] : null;

        $mform =& $this->_form;
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'edit',  ($this->instance) ? $this->instance->get('id') : 0);
        $mform->addElement('hidden', 'new',   $this->plugin);
        $mform->addElement('hidden', 'plugin', $this->plugin);

        if (!$this->instance) {
            $insane = portfolio_instance_sanity_check($this->instance);
        } else {
            $insane = portfolio_plugin_sanity_check($this->plugin);
        }

        if (isset($insane) && is_array($insane)) {
            $insane = array_shift($insane);
        }
        if (isset($insane) && is_string($insane)) { // something went wrong, warn...
            $mform->addElement('warning', 'insane', null, get_string($insane, 'portfolio_' . $this->plugin));
        }

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="100" size="30"');
        $mform->addRule('name', $strrequired, 'required', null, 'client');

        // let the plugin add the fields they want (either statically or not)
        if (portfolio_static_function($this->plugin, 'has_admin_config')) {
            if (!$this->instance) {
                portfolio_static_function($this->plugin, 'admin_config_form', $mform);
            } else {
                $this->instance->admin_config_form($mform);
            }
        }

        // and set the data if we have some.
        if ($this->instance) {
            $data = array('name' => $this->instance->get('name'));
            foreach ($this->instance->get_allowed_config() as $config) {
                $data[$config] = $this->instance->get_config($config);
            }
            $this->set_data($data);
        } else {
            $this->set_data(array('name' => portfolio_static_function($this->plugin, 'get_name')));
        }

        $this->add_action_buttons(true, get_string('save', 'portfolio'));
    }

    public function validation($data) {
        global $DB;

        $errors = array();
        if ($DB->count_records('portfolio_instance', array('name' => $data['name'], 'plugin' => $data['plugin'])) > 1) {
            $errors = array('name' => get_string('err_uniquename', 'portfolio'));
        }

        $pluginerrors = array();
        if ($this->instance) {
            $pluginerrors = $this->instance->admin_config_validation($data);
        }
        else {
            $pluginerrors = portfolio_static_function($this->plugin, 'admin_config_validation', $data);
        }
        if (is_array($pluginerrors)) {
            $errors = array_merge($errors, $pluginerrors);
        }
        return $errors;
    }
}

/**
* User config form.
*
* This is the form for letting the user configure an instance of a plugin.
* In order to extend this, you don't subclass this in the plugin..
* see the docs in portfolio_plugin_base for more information:
* http://docs.moodle.org/en/Development:Writing_a_Portfolio_Plugin#has_user_config
*/
final class portfolio_user_form extends moodleform {

    protected $instance;
    protected $userid;

    public function definition() {
        $this->instance = $this->_customdata['instance'];
        $this->userid = $this->_customdata['userid'];

        $this->_form->addElement('hidden', 'config', $this->instance->get('id'));

        $this->instance->user_config_form($this->_form, $this->userid);

        $data = array();
        foreach ($this->instance->get_allowed_user_config() as $config) {
            $data[$config] = $this->instance->get_user_config($config, $this->userid);
        }
        $this->set_data($data);
        $this->add_action_buttons(true, get_string('save', 'portfolio'));
    }

    public function validation($data) {

        $errors = $this->instance->user_config_validation($data);

    }
}


/**
* Form that just contains the dropdown menu of available instances
*
* This is not used by portfolio_add_button, but on the first step of the export
* if the plugin instance has not yet been selected.
*/
class portfolio_instance_select extends moodleform {

    private $caller;

    function definition() {
        $this->caller = $this->_customdata['caller'];
        $options = portfolio_instance_select(
            portfolio_instances(),
            $this->caller->supported_formats($this->caller),
            get_class($this->caller),
            'instance',
            true,
            true
        );
        if (empty($options)) {
            debugging('noavailableplugins', 'portfolio');
            return false;
        }
        $mform =& $this->_form;
        $mform->addElement('select', 'instance', get_string('selectplugin', 'portfolio'), $options);
        $this->add_action_buttons(true, get_string('next'));
    }
}

?>
