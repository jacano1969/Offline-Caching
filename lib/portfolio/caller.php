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
 * This file contains the base classes for places in moodle that want to
 * add export functionality to subclass from.
 * See http://docs.moodle.org/en/Development:Adding_a_Portfolio_Button_to_a_page
 */

/**
* base class for callers
*
* See http://docs.moodle.org/en/Development:Adding_a_Portfolio_Button_to_a_page
* {@see also portfolio_module_caller_base}
*/
abstract class portfolio_caller_base {

    /**
    * stdclass object
    * course that was active during the caller
    */
    protected $course;

    /**
    * named array of export config
    * use{@link  set_export_config} and {@link get_export_config} to access
    */
    protected $exportconfig;

    /**
    * stdclass object
    * user currently exporting content
    */
    protected $user;

    /**
    * a reference to the exporter object
    */
    protected $exporter;

    /**
    * this can be overridden in subclasses constructors if they want
    */
    protected $supportedformats;


    public function __construct($callbackargs) {
        $expected = call_user_func(array(get_class($this), 'expected_callbackargs'));
        foreach ($expected as $key => $required) {
            if (!array_key_exists($key, $callbackargs)) {
                if ($required) {
                    $a = (object)array('key' => $key, 'class' => get_class($this));
                    throw new portfolio_caller_exception('missingcallbackarg', 'portfolio', null, $a);
                }
                continue;
            }
            $this->{$key} = $callbackargs[$key];
        }
    }

    /**
    * if this caller wants any additional config items
    * they should be defined here.
    *
    * @param array $mform moodleform object (passed by reference) to add elements to
    * @param object $instance subclass of portfolio_plugin_base
    * @param integer $userid id of user exporting content
    */
    public function export_config_form(&$mform, $instance) {}


    /**
    * whether this caller wants any additional
    * config during export (eg options or metadata)
    *
    * @return boolean
    */
    public function has_export_config() {
        return false;
    }

    /**
    * just like the moodle form validation function
    * this is passed in the data array from the form
    * and if a non empty array is returned, form processing will stop.
    *
    * @param array $data data from form.
    * @return array keyvalue pairs - form element => error string
    */
    public function export_config_validation($data) {}

    /**
    * how long does this reasonably expect to take..
    * should we offer the user the option to wait..
    * this is deliberately nonstatic so it can take filesize into account
    * the portfolio plugin can override this.
    * (so for exmaple even if a huge file is being sent,
    * the download portfolio plugin doesn't care )
    *
    * @return string (see PORTFOLIO_TIME_* constants)
    */
    public abstract function expected_time();

    /**
    * used for displaying the navigation during the export screens.
    *
    * this function must be implemented, but can really return anything.
    * an Exporting.. string will be added on the end.
    * @return array of $extranav and $cm
    *
    * to pass to build_navigation
    *
    */
    public abstract function get_navigation();

    /**
    *
    */
    public abstract function get_sha1();

    /*
    * generic getter for properties belonging to this instance
    * <b>outside</b> the subclasses
    * like name, visible etc.
    */
    public function get($field) {
        if (property_exists($this, $field)) {
            return $this->{$field};
        }
        $a = (object)array('property' => $field, 'class' => get_class($this));
        throw new portfolio_export_exception($this->get('exporter'), 'invalidproperty', 'portfolio', $this->get_return_url(), $a);
    }

    /**
    * generic setter for properties belonging to this instance
    * <b>outside</b> the subclass
    * like name, visible, etc.
    *
    */
    public final function set($field, &$value) {
        if (property_exists($this, $field)) {
            $this->{$field} =& $value;
            $this->dirty = true;
            return true;
        }
        $a = (object)array('property' => $field, 'class' => get_class($this));
        throw new portfolio_export_exception($this->get('exporter'), 'invalidproperty', 'portfolio', $this->get_return_url(), $a);
    }

    /**
    * stores the config generated at export time.
    * subclasses can retrieve values using
    * {@link get_export_config}
    *
    * @param array $config formdata
    */
    public final function set_export_config($config) {
        $allowed = array_merge(
            array('wait', 'hidewait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        foreach ($config as $key => $value) {
            if (!in_array($key, $allowed)) {
                $a = (object)array('property' => $key, 'class' => get_class($this));
                throw new portfolio_export_exception($this->get('exporter'), 'invalidexportproperty', 'portfolio', $this->get_return_url(), $a);
            }
            $this->exportconfig[$key] = $value;
        }
    }

    /**
    * returns a particular export config value.
    * subclasses shouldn't need to override this
    *
    * @param string key the config item to fetch
    */
    public final function get_export_config($key) {
        $allowed = array_merge(
            array('wait', 'hidewait', 'format', 'hideformat'),
            $this->get_allowed_export_config()
        );
        if (!in_array($key, $allowed)) {
            $a = (object)array('property' => $key, 'class' => get_class($this));
            throw new portfolio_export_exception($this->get('exporter'), 'invalidexportproperty', 'portfolio', $this->get_return_url(), $a);
        }
        if (!array_key_exists($key, $this->exportconfig)) {
            return null;
        }
        return $this->exportconfig[$key];
    }

    /**
    * Similar to the other allowed_config functions
    * if you need export config, you must provide
    * a list of what the fields are.
    *
    * even if you want to store stuff during export
    * without displaying a form to the user,
    * you can use this.
    *
    * @return array array of allowed keys
    */
    public function get_allowed_export_config() {
        return array();
    }

    /**
    * after the user submits their config
    * they're given a confirm screen
    * summarising what they've chosen.
    *
    * this function should return a table of nice strings => values
    * of what they've chosen
    * to be displayed in a table.
    *
    * @return array array of config items.
    */
    public function get_export_summary() {
        return false;
    }

    /**
    * called before the portfolio plugin gets control
    * this function should copy all the files it wants to
    * the temporary directory, using {@see copy_existing_file}
    * or {@see write_new_file}
    */
    public abstract function prepare_package();

    /**
    * array of formats this caller supports
    * the intersection of what this function returns
    * and what the selected portfolio plugin supports
    * will be used
    * use the constants PORTFOLIO_FORMAT_*
    * if $caller is passed, that can be used for more specific guesses
    * as this function <b>must</b> be called statically.
    *
    * @return array list of formats
    */
    public static function supported_formats($caller=null) {
        if ($caller && $formats = $caller->get('supportedformats')) {
            if (is_array($formats)) {
                return $formats;
            }
            debugging(get_class($caller) . ' has set a non array value of member variable supported formats - working around but should be fixed in code');
            return array($formats);
        }
        return array(PORTFOLIO_FORMAT_FILE);
    }


    /**
    * this is the "return to where you were" url
    *
    * @return string url
    */
    public abstract function get_return_url();

    /**
    * callback to do whatever capability checks required
    * in the caller (called during the export process
    */
    public abstract function check_permissions();

    /**
    * nice name to display to the user about this caller location
    */
    public abstract static function display_name();

    /**
    * return a string to put at the header summarising this export
    * by default, just the display name (usually just 'assignment' or something unhelpful
    */
    public function heading_summary() {
        return get_string('exportingcontentfrom', 'portfolio', $this->display_name());
    }

    public abstract function load_data();

    public static abstract function expected_callbackargs();

}

/**
* base class for module callers
* this just implements a few of the abstract functions
* from portfolio_caller_base so that caller authors
* don't need to.
*
* See http://docs.moodle.org/en/Development:Adding_a_Portfolio_Button_to_a_page
* {@see also portfolio_caller_base}
*/
abstract class portfolio_module_caller_base extends portfolio_caller_base {

    /**
    * coursemodule object
    * set this in the constructor like
    * $this->cm = get_coursemodule_from_instance('forum', $this->forum->id);
    */
    protected $cm;

    /**
    *
    * int cmid
    */
    protected $id;

    /**
    * stdclass course object
    */
    protected $course;

    /**
    * navigation passed to print_header
    * override this to do something more specific than the module view page
    */
    public function get_navigation() {
        $extranav = array('name' => $this->cm->name, 'link' => $this->get_return_url());
        return array($extranav, $this->cm);
    }

    /**
    * the url to return to after export or on cancel
    * defaults to the module 'view' page
    * override this if it's deeper inside the module
    */
    public function get_return_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/' . $this->cm->modname . '/view.php?id=' . $this->cm->id;
    }

    /**
    * override the parent get function
    * to make sure when we're asked for a course
    * we retrieve the object from the database as needed
    */
    public function get($key) {
        if ($key != 'course') {
            return parent::get($key);
        }
        global $DB;
        if (empty($this->course)) {
            $this->course = $DB->get_record('course', array('id' => $this->cm->course));
        }
        return $this->course;
    }

    /**
    * return a string to put at the header summarising this export
    * by default, just hte display name and the module instance name
    * override this to do something more specific
    */
    public function heading_summary() {
        return get_string('exportingcontentfrom', 'portfolio', $this->display_name() . ': ' . $this->cm->name);
    }
}

?>