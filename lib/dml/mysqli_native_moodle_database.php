<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Native mysqli class representing moodle database interface.
 *
 * @package    moodlecore
 * @subpackage DML
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/dml/moodle_database.php');
require_once($CFG->libdir.'/dml/mysqli_native_moodle_recordset.php');

/**
 * Native mysqli class representing moodle database interface.
 */
class mysqli_native_moodle_database extends moodle_database {

    protected $mysqli = null;

    /**
     * Attempt to create the database
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @return bool success
     * @throws dml_exception if error
     */
    public function create_database($dbhost, $dbuser, $dbpass, $dbname, array $dboptions=null) {
        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        ob_start();
        $conn = new mysqli($dbhost, $dbuser, $dbpass); /// Connect without db
        $dberr = ob_get_contents();
        ob_end_clean();
        $errorno = @$conn->connect_errno;

        if ($errorno !== 0) {
            throw new dml_connection_exception($dberr);
        }

        $result = $conn->query("CREATE DATABASE $dbname DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");

        $conn->close();

        if (!$result) {
            throw new dml_exception('cannotcreatedb');
        }

        return true;
    }

    /**
     * Detects if all needed PHP stuff installed.
     * Note: can be used before connect()
     * @return mixed true if ok, string if something
     */
    public function driver_installed() {
        if (!extension_loaded('mysqli')) {
            return get_string('mysqliextensionisnotpresentinphp', 'install');
        }
        return true;
    }

    /**
     * Returns database family type - describes SQL dialect
     * Note: can be used before connect()
     * @return string db family name (mysql, postgres, mssql, oracle, etc.)
     */
    public function get_dbfamily() {
        return 'mysql';
    }

    /**
     * Returns more specific database driver type
     * Note: can be used before connect()
     * @return string db type mysql, mysqli, postgres7
     */
    protected function get_dbtype() {
        return 'mysqli';
    }

    /**
     * Returns general database library name
     * Note: can be used before connect()
     * @return string db type adodb, pdo, native
     */
    protected function get_dblibrary() {
        return 'native';
    }

    /**
     * Returns localised database type name
     * Note: can be used before connect()
     * @return string
     */
    public function get_name() {
        return get_string('nativemysqli', 'install');
    }

    /**
     * Returns localised database configuration help.
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_help() {
        return get_string('nativemysqlihelp', 'install');
    }

    /**
     * Returns localised database description
     * Note: can be used before connect()
     * @return string
     */
    public function get_configuration_hints() {
        return get_string('databasesettingssub_mysqli', 'install');
    }

    /**
     * Connect to db
     * Must be called before other methods.
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param mixed $prefix string means moodle db prefix, false used for external databases where prefix not used
     * @param array $dboptions driver specific options
     * @return bool success
     */
    public function connect($dbhost, $dbuser, $dbpass, $dbname, $prefix, array $dboptions=null) {
        $driverstatus = $this->driver_installed();

        if ($driverstatus !== true) {
            throw new dml_exception('dbdriverproblem', $driverstatus);
        }

        $this->store_settings($dbhost, $dbuser, $dbpass, $dbname, $prefix, $dboptions);
        unset($this->dboptions['dbsocket']);

        ob_start();
        $this->mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        $dberr = ob_get_contents();
        ob_end_clean();
        $errorno = @$this->mysqli->connect_errno;

        if ($errorno !== 0) {
            throw new dml_connection_exception($dberr);
        }

        $this->query_start("--set_charset()", null, SQL_QUERY_AUX);
        $this->mysqli->set_charset('utf8');
        $this->query_end(true);

        return true;
    }

    /**
     * Close database connection and release all resources
     * and memory (especially circular memory references).
     * Do NOT use connect() again, create a new instance if needed.
     */
    public function dispose() {
        parent::dispose(); // Call parent dispose to write/close session and other common stuff before clossing conn
        if ($this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
        }
    }

    /**
     * Returns database server info array
     * @return array
     */
    public function get_server_info() {
        return array('description'=>$this->mysqli->server_info, 'version'=>$this->mysqli->server_info);
    }

    /**
     * Returns supported query parameter types
     * @return bitmask
     */
    protected function allowed_param_types() {
        return SQL_PARAMS_QM;
    }

    /**
     * Returns last error reported by database engine.
     */
    public function get_last_error() {
        return $this->mysqli->error;
    }

    /**
     * Return tables in database WITHOUT current prefix
     * @return array of table names in lowercase and without prefix
     */
    public function get_tables($usecache=true) {
        if ($usecache and $this->tables !== null) {
            return $this->tables;
        }
        $this->tables = array();
        $sql = "SHOW TABLES";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($result) {
            while ($arr = $result->fetch_assoc()) {
                $tablename = reset($arr);
                if ($this->prefix !== '') {
                    if (strpos($tablename, $this->prefix) !== 0) {
                        continue;
                    }
                    $tablename = substr($tablename, strlen($this->prefix));
                }
                $this->tables[$tablename] = $tablename;
            }
            $result->close();
        }
        return $this->tables;
    }

    /**
     * Return table indexes - everything lowercased
     * @return array of arrays
     */
    public function get_indexes($table) {
        $indexes = array();
        $sql = "SHOW INDEXES FROM {$this->prefix}$table";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);
        if ($result) {
            while ($res = $result->fetch_object()) {
                if ($res->Key_name === 'PRIMARY') {
                    continue;
                }
                if (!isset($indexes[$res->Key_name])) {
                    $indexes[$res->Key_name] = array('unique'=>empty($res->Non_unique), 'columns'=>array());
                }
                $indexes[$res->Key_name]['columns'][$res->Seq_in_index-1] = $res->Column_name;
            }
            $result->close();
        }
        return $indexes;
    }

    /**
     * Returns datailed information about columns in table. This information is cached internally.
     * @param string $table name
     * @param bool $usecache
     * @return array array of database_column_info objects indexed with column names
     */
    public function get_columns($table, $usecache=true) {
        if ($usecache and isset($this->columns[$table])) {
            return $this->columns[$table];
        }

        $this->columns[$table] = array();

        $sql = "SHOW COLUMNS FROM {$this->prefix}$table";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result === false) {
            return array();
        }

        while ($rawcolumn = $result->fetch_assoc()) {
            $rawcolumn = (object)array_change_key_case($rawcolumn, CASE_LOWER);

            $info = new object();
            $info->name = $rawcolumn->field;
            $matches = null;

            if (preg_match('/varchar\((\d+)\)/i', $rawcolumn->type, $matches)) {
                $info->type          = 'varchar';
                $info->meta_type     = 'C';
                $info->max_length    = $matches[1];
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->null === 'NO');
                $info->default_value = $rawcolumn->default;
                $info->has_default   = is_null($info->default_value) ? false : true;
                $info->primary_key   = ($rawcolumn->key === 'PRI');
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/([a-z]*int[a-z]*)\((\d+)\)/i', $rawcolumn->type, $matches)) {
                $info->type = $matches[1];
                $info->primary_key       = ($rawcolumn->key === 'PRI');
                if ($info->primary_key) {
                    $info->meta_type     = 'R';
                    $info->max_length    = $matches[2];
                    $info->scale         = null;
                    $info->not_null      = ($rawcolumn->null === 'NO');
                    $info->default_value = $rawcolumn->default;
                    $info->has_default   = is_null($info->default_value) ? false : true;
                    $info->binary        = false;
                    $info->unsigned      = (stripos($rawcolumn->type, 'unsigned') !== false);
                    $info->auto_increment= true;
                    $info->unique        = true;
                } else {
                    $info->meta_type     = 'I';
                    $info->max_length    = $matches[2];
                    $info->scale         = null;
                    $info->not_null      = ($rawcolumn->null === 'NO');
                    $info->default_value = $rawcolumn->default;
                    $info->has_default   = is_null($info->default_value) ? false : true;
                    $info->binary        = false;
                    $info->unsigned      = (stripos($rawcolumn->type, 'unsigned') !== false);
                    $info->auto_increment= false;
                    $info->unique        = null;
                }

            } else if (preg_match('/(decimal|double|float)\((\d+),(\d+)\)/i', $rawcolumn->type, $matches)) {
                $info->type          = $matches[1];
                $info->meta_type     = 'N';
                $info->max_length    = $matches[2];
                $info->scale         = $matches[3];
                $info->not_null      = ($rawcolumn->null === 'NO');
                $info->default_value = $rawcolumn->default;
                $info->has_default   = is_null($info->default_value) ? false : true;
                $info->primary_key   = ($rawcolumn->key === 'PRI');
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/([a-z]*text)/i', $rawcolumn->type, $matches)) {
                $info->type          = $matches[1];
                $info->meta_type     = 'X';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->null === 'NO');
                $info->default_value = $rawcolumn->default;
                $info->has_default   = is_null($info->default_value) ? false : true;
                $info->primary_key   = ($rawcolumn->key === 'PRI');
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/([a-z]*blob)/i', $rawcolumn->type, $matches)) {
                $info->type          = $matches[1];
                $info->meta_type     = 'B';
                $info->max_length    = -1;
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->null === 'NO');
                $info->default_value = $rawcolumn->default;
                $info->has_default   = is_null($info->default_value) ? false : true;
                $info->primary_key   = false;
                $info->binary        = true;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;

            } else if (preg_match('/enum\((.*)\)/i', $rawcolumn->type, $matches)) {
                $info->type          = 'enum';
                $info->meta_type     = 'C';
                $info->enums         = array();
                $info->max_length    = 0;
                $values = $matches[1];
                $values = explode(',', $values);
                $textlib = textlib_get_instance();
                foreach ($values as $val) {
                    $val = trim($val, "'");
                    $length = $textlib->strlen($val);
                    $info->enums[] = $val;
                    $info->max_length = ($info->max_length < $length) ? $length : $info->max_length;
                }
                $info->scale         = null;
                $info->not_null      = ($rawcolumn->null === 'NO');
                $info->default_value = $rawcolumn->default;
                $info->has_default   = is_null($info->default_value) ? false : true;
                $info->primary_key   = ($rawcolumn->key === 'PRI');
                $info->binary        = false;
                $info->unsigned      = null;
                $info->auto_increment= false;
                $info->unique        = null;
            }

            $this->columns[$table][$info->name] = new database_column_info($info);
        }

        $result->close();

        return $this->columns[$table];
    }

    /**
     * Is db in unicode mode?
     * @return bool
     */
    public function setup_is_unicodedb() {
        $sql = "SHOW LOCAL VARIABLES LIKE 'character_set_database'";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result) {
            $result->close();
            return true;
        }
        return false;
    }

    /**
     * Do NOT use in code, to be used by database_manager only!
     * @param string $sql query
     * @return bool true
     * @throws dml_exception if error
     */
    public function change_database_structure($sql) {
        $this->reset_caches();

        $this->query_start($sql, null, SQL_QUERY_STRUCTURE);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        return true;
    }

    /**
     * Very ugly hack which emulates bound parameters in queries
     * because prepared statements do not use query cache.
     */
    protected function emulate_bound_params($sql, array $params=null) {
        if (empty($params)) {
            return $sql;
        }
        /// ok, we have verified sql statement with ? and correct number of params
        $return = strtok($sql, '?');
        foreach ($params as $param) {
            if (is_bool($param)) {
                $return .= (int)$param;
            } else if (is_null($param)) {
                $return .= 'NULL';
            } else if (is_number($param)) { // we can not use is_numeric() because it eats leading zeros from strings like 0045646
                $return .= $param;
            } else if (is_float($param)) {
                $return .= $param;
            } else {
                $param = $this->mysqli->real_escape_string($param);
                $return .= "'$param'";
            }
            $return .= strtok('?');
        }
        return $return;
    }

    /**
     * Execute general sql query. Should be used only when no other method suitable.
     * Do NOT use this to make changes in db structure, use database_manager::execute_sql() instead!
     * @param string $sql query
     * @param array $params query parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function execute($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);

        if (strpos($sql, ';') !== false) {
            throw new coding_exception('moodle_database::execute() Multiple sql statements found or bound parameters not used properly in query!');
        }

        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        if ($result === true) {
            return true;

        } else {
            $result->close();
            return true;
        }
    }

    /**
     * Get a number of records as a moodle_recordset using a SQL statement.
     *
     * Since this method is a little less readable, use of it should be restricted to
     * code where it's possible there might be large datasets being returned.  For known
     * small datasets use get_records_sql - it leads to simpler code.
     *
     * The return type is as for @see function get_recordset.
     *
     * @param string $sql the SQL select query to execute.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return mixed an moodle_recordset object
     * @throws dml_exception if error
     */
    public function get_recordset_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        $limitfrom = (int)$limitfrom;
        $limitnum  = (int)$limitnum;
        $limitfrom = ($limitfrom < 0) ? 0 : $limitfrom;
        $limitnum  = ($limitnum < 0)  ? 0 : $limitnum;

        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitfrom, $limitnum";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        // no MYSQLI_USE_RESULT here, it would block write ops on affected tables
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        return $this->create_recordset($result);
    }

    protected function create_recordset($result) {
        return new mysqli_native_moodle_recordset($result);
    }

    /**
     * Get a number of records as an array of objects using a SQL statement.
     *
     * Return value as for @see function get_records.
     *
     * @param string $sql the SQL select query to execute. The first column of this SELECT statement
     *   must be a unique value (usually the 'id' field), as it will be used as the key of the
     *   returned array.
     * @param array $params array of sql parameters
     * @param int $limitfrom return a subset of records, starting at this point (optional, required if $limitnum is set).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return mixed an array of objects, or empty array if no records were found
     * @throws dml_exception if error
     */
    public function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0) {
        $limitfrom = (int)$limitfrom;
        $limitnum  = (int)$limitnum;
        $limitfrom = ($limitfrom < 0) ? 0 : $limitfrom;
        $limitnum  = ($limitnum < 0)  ? 0 : $limitnum;

        if ($limitfrom or $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitfrom, $limitnum";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        $return = array();

        while($row = $result->fetch_assoc()) {
            $row = array_change_key_case($row, CASE_LOWER);
            $id  = reset($row);
            if (isset($return[$id])) {
                $colname = key($row);
                debugging("Did you remember to make the first column something unique in your call to get_records? Duplicate value '$id' found in column '$colname'.", DEBUG_DEVELOPER);
            }
            $return[$id] = (object)$row;
        }
        $result->close();

        return $return;
    }

    /**
     * Selects records and return values (first field) as an array using a SQL statement.
     *
     * @param string $sql The SQL query
     * @param array $params array of sql parameters
     * @return mixed array of values
     * @throws dml_exception if error
     */
    public function get_fieldset_sql($sql, array $params=null) {
        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        $result = $this->mysqli->query($rawsql, MYSQLI_STORE_RESULT);
        $this->query_end($result);

        $return = array();

        while($row = $result->fetch_assoc()) {
            $return[] = reset($row);
        }
        $result->close();

        return $return;
    }

    /**
     * Insert new record into database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool $returnit return it of inserted record
     * @param bool $bulk true means repeated inserts expected
     * @param bool $customsequence true if 'id' included in $params, disables $returnid
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record_raw($table, $params, $returnid=true, $bulk=false, $customsequence=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }

        if ($customsequence) {
            if (!isset($params['id'])) {
                throw new coding_exception('moodle_database::insert_record_raw() id field must be specified if custom sequences used.');
            }
            $returnid = false;
        } else {
            unset($params['id']);
        }

        if (empty($params)) {
            throw new coding_exception('moodle_database::insert_record_raw() no fields found.');
        }

        $fields = implode(',', array_keys($params));
        $qms    = array_fill(0, count($params), '?');
        $qms    = implode(',', $qms);

        $sql = "INSERT INTO {$this->prefix}$table ($fields) VALUES($qms)";
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_INSERT);
        $result = $this->mysqli->query($rawsql);
        $id = @$this->mysqli->insert_id; // must be called before query_end() which may insert log into db
        $this->query_end($result);

        if (!$id) {
            throw new dml_write_exception('unknown error fetching inserted id');
        }

        if (!$returnid) {
            return true;
        } else {
            return (int)$id;
        }
    }

    /**
     * Insert a record into a table and return the "id" field if required.
     *
     * Some conversions and safety checks are carried out. Lobs are supported.
     * If the return ID isn't required, then this just reports success as true/false.
     * $data is an object containing needed data
     * @param string $table The database table to be inserted into
     * @param object $data A data object with values for one or more fields in the record
     * @param bool $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
     * @return true or new id
     * @throws dml_exception if error
     */
    public function insert_record($table, $dataobject, $returnid=true, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);

        unset($dataobject->id);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $column = $columns[$field];
            if (is_bool($value)) {
                $value = (int)$value; // prevent "false" problems
            }
            if (!empty($column->enums)) {
                // workaround for problem with wrong enums in mysql
                if (is_null($value) and !$column->not_null) {
                    // ok - nulls allowed
                } else {
                    if (!in_array((string)$value, $column->enums)) {
                        throw new dml_write_exception('Enum value '.s($value).' not allowed in field '.$field.' table '.$table.'.');
                    }
                }
            }
            $cleaned[$field] = $value;
        }

        return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);
    }

    /**
     * Import a record into a table, id field is required.
     * Safety checks are NOT carried out. Lobs are supported.
     *
     * @param string $table name of database table to be inserted into
     * @param object $dataobject A data object with values for one or more fields in the record
     * @return bool true
     * @throws dml_exception if error
     */
    public function import_record($table, $dataobject) {
        $dataobject = (object)$dataobject;

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            $cleaned[$field] = $value;
        }

        return $this->insert_record_raw($table, $cleaned, false, true, true);
    }

    /**
     * Update record in database, as fast as possible, no safety checks, lobs not supported.
     * @param string $table name
     * @param mixed $params data record as object or array
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception if error
     */
    public function update_record_raw($table, $params, $bulk=false) {
        if (!is_array($params)) {
            $params = (array)$params;
        }
        if (!isset($params['id'])) {
            throw new coding_exception('moodle_database::update_record_raw() id field must be specified.');
        }
        $id = $params['id'];
        unset($params['id']);

        if (empty($params)) {
            throw new coding_exception('moodle_database::update_record_raw() no fields found.');
        }

        $sets = array();
        foreach ($params as $field=>$value) {
            $sets[] = "$field = ?";
        }

        $params[] = $id; // last ? in WHERE condition

        $sets = implode(',', $sets);
        $sql = "UPDATE {$this->prefix}$table SET $sets WHERE id=?";
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        return true;
    }

    /**
     * Update a record in a table
     *
     * $dataobject is an object containing needed data
     * Relies on $dataobject having a variable "id" to
     * specify the record to update
     *
     * @param string $table The database table to be checked against.
     * @param object $dataobject An object with contents equal to fieldname=>fieldvalue. Must have an entry for 'id' to map to the table specified.
     * @param bool true means repeated updates expected
     * @return bool true
     * @throws dml_exception if error
     */
    public function update_record($table, $dataobject, $bulk=false) {
        if (!is_object($dataobject)) {
            $dataobject = (object)$dataobject;
        }

        $columns = $this->get_columns($table);
        $cleaned = array();

        foreach ($dataobject as $field=>$value) {
            if (!isset($columns[$field])) {
                continue;
            }
            if (is_bool($value)) {
                $value = (int)$value; // prevent "false" problems
            }
            $cleaned[$field] = $value;
        }

        return $this->update_record_raw($table, $cleaned, $bulk);
    }

    /**
     * Set a single field in every table record which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $newfield the field to set.
     * @param string $newvalue the value to set the field to.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call.
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function set_field_select($table, $newfield, $newvalue, $select, array $params=null) {
        if ($select) {
            $select = "WHERE $select";
        }
        if (is_null($params)) {
            $params = array();
        }
        list($select, $params, $type) = $this->fix_sql_params($select, $params);

        if (is_bool($newvalue)) {
            $newvalue = (int)$newvalue; // prevent "false" problems
        }
        if (is_null($newvalue)) {
            $newfield = "$newfield = NULL";
        } else {
            $newfield = "$newfield = ?";
            array_unshift($params, $newvalue);
        }
        $sql = "UPDATE {$this->prefix}$table SET $newfield $select";
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        return true;
    }

    /**
     * Delete one or more records from a table which match a particular WHERE clause.
     *
     * @param string $table The database table to be checked against.
     * @param string $select A fragment of SQL to be used in a where clause in the SQL call (used to define the selection criteria).
     * @param array $params array of sql parameters
     * @return bool true
     * @throws dml_exception if error
     */
    public function delete_records_select($table, $select, array $params=null) {
        if ($select) {
            $select = "WHERE $select";
        }
        $sql = "DELETE FROM {$this->prefix}$table $select";

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_UPDATE);
        $result = $this->mysqli->query($rawsql);
        $this->query_end($result);

        return true;
    }

    public function sql_cast_char2int($fieldname, $text=false) {
        return ' CAST(' . $fieldname . ' AS SIGNED) ';
    }

    public function sql_concat() {
        $arr = func_get_args();
        $s = implode(', ', $arr);
        if ($s === '') {
            return "''";
        }
        return "CONCAT($s)";
    }

    public function sql_concat_join($separator="' '", $elements=array()) {
        $s = implode(', ', $elements);

        if ($s === '') {
            return "''";
        }
        return "CONCAT_WS($separator, $s)";
    }

    /**
     * Returns the SQL text to be used to calculate the length in characters of one expression.
     * @param string fieldname or expression to calculate its length in characters.
     * @return string the piece of SQL code to be used in the statement.
     */
    public function sql_length($fieldname) {
        return ' CHAR_LENGTH(' . $fieldname . ')';
    }

    /**
     * Does this driver suppoer regex syntax when searching
     */
    public function sql_regex_supported() {
        return true;
    }

    /**
     * Return regex positive or negative match sql
     * @param bool $positivematch
     * @return string or empty if not supported
     */
    public function sql_regex($positivematch=true) {
        return $positivematch ? 'REGEXP' : 'NOT REGEXP';
    }

    public function sql_cast_2signed($fieldname) {
        return ' CAST(' . $fieldname . ' AS SIGNED) ';
    }

/// session locking
    public function session_lock_supported() {
        return true;
    }

    public function get_session_lock($rowid) {
        parent::get_session_lock($rowid);
        $fullname = $this->dbname.'-'.$this->prefix.'-session-'.$rowid;
        $sql = "SELECT GET_LOCK('$fullname',120)";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result) {
            $arr = $result->fetch_assoc();
            $result->close();

            if (reset($arr) == 1) {
                return;
            } else {
                // try again!
                $this->get_session_lock($rowid);
            }
        }
    }

    public function release_session_lock($rowid) {
        parent::release_session_lock($rowid);
        $fullname = $this->dbname.'-'.$this->prefix.'-session-'.$rowid;
        $sql = "SELECT RELEASE_LOCK('$fullname')";
        $this->query_start($sql, null, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        if ($result) {
            $result->close();
        }
    }

/// transactions
    /**
     * on DBs that support it, switch to transaction mode and begin a transaction
     * you'll need to ensure you call commit_sql() or your changes *will* be lost.
     *
     * this is _very_ useful for massive updates
     */
    public function begin_sql() {
        if (!parent::begin_sql()) {
            return false;
        }
        $sql = "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        $sql = "BEGIN";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        return true;
    }

    /**
     * on DBs that support it, commit the transaction
     */
    public function commit_sql() {
        if (!parent::commit_sql()) {
            return false;
        }
        $sql = "COMMIT";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        return true;
    }

    /**
     * on DBs that support it, rollback the transaction
     */
    public function rollback_sql() {
        if (!parent::rollback_sql()) {
            return false;
        }
        $sql = "ROLLBACK";
        $this->query_start($sql, NULL, SQL_QUERY_AUX);
        $result = $this->mysqli->query($sql);
        $this->query_end($result);

        return true;
    }
}
