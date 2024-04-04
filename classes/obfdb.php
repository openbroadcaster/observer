<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', '');
}

/**
 * Database class. Manages SQL connections and queries, keeps track of last query
 * and errors. Also hosts some utility methods.
 *
 * @package Class
 */
class OBFDB
{
    private $connection;
    private $result;
    private $last_query;

    private $profiles = null;
    private $profiling = false;

    // helper variables for higher-level functions
    private $hl_what;
    private $hl_where;
    private $hl_where_string;
    private $hl_orderby;
    private $hl_limit;
    private $hl_offset;
    private $hl_leftjoin;
    private $hl_foundrows;

    private $hl_where_mode = 'AND';

    /**
     * OBFDB constructor. Adds connection information to the instance based on what's
     * defined in OB global setting (OB_DB_HOST, OB_DB_USER, OB_DB_PASS, OB_DB_NAME),
     * and fires a few queries to set the SQL mode.
     */
    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->connection = mysqli_connect(OB_DB_HOST, OB_DB_USER, OB_DB_PASS);
        mysqli_select_db($this->connection, OB_DB_NAME);
        mysqli_query($this->connection, 'SET NAMES \'utf8\'');


        // remove session strict mode for now, until code and database are updated to support it.
        // get current modes
        $result = mysqli_query($this->connection, "SHOW SESSION VARIABLES LIKE 'sql_mode'");
        $row = mysqli_fetch_assoc($result);

        // explode current modes into array and remove strict modes
        $modes = explode(',', $row['Value']);
        foreach ($modes as $index => $mode) {
            if ($mode == 'STRICT_TRANS_TABLES' || $mode == 'STRICT_ALL_TABLES') {
                unset($modes[$index]);
            }
        }

        // set new modes
        $new_modes = implode(',', $modes);
        mysqli_query($this->connection, "SET SESSION sql_mode = '" . mysqli_real_escape_string($this->connection, $new_modes) . "'");
    }

    /**
     * Create an instance of OBFDB or return the already created instance.
     *
     * @return instance
     */
    public static function &get_instance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        $instance = new OBFDB();

        return $instance;
    }

    /**
     * Enable database profiling. Note that this clears any pre-existing profiles
     * set for this instance by assigning a new empty array to it.
     */
    public function enable_profiling()
    {
        $this->profiling = true;
        $this->profiles = array();
    }

    /**
     * Disable database profiling.
     */
    public function disable_profiling()
    {
        $this->profiling = false;
    }

    /**
     * Get database profiles.
     */
    public function get_profiles()
    {
        return $this->profiles;
    }

    /**
     * Add a database profile. Returns FALSE if profiling is disabled for this
     * instance.
     *
     * @param query
     * @param duration
     *
     * @return status
     */
    public function add_profile($query, $duration)
    {
        if (!$this->profiling) {
            return false;
        }

        $this->profiles[] = array('query' => $query, 'duration' => (float) $duration);
        return true;
    }

    // basic sql query
    /**
     * Basic SQL query. Pass SQL to the method which will be run on the configured
     * database.
     *
     * Note that this method does NOT provide the safeties in place in query-builders.
     * As a result, be EXTRA sure to strip any possible problematic characters from
     * queries, and just entirely avoid using this with user-controlled input if at
     * all possible.
     *
     * @param sql The SQL query to run on the database.
     */
    public function query($sql)
    {
        $this->last_query = $sql;

        $start_time = microtime(true);
        $this->result = mysqli_query($this->connection, $sql);
        $duration = round(((microtime(true) - $start_time)), 4);

        if (defined('OB_LOG_SLOW_QUERIES') && OB_LOG_SLOW_QUERIES == true && $duration > 1) {
            error_log('SLOW SQL (' . $duration . 's): ' . $sql);
        }
        if ($this->profiling) {
            $this->add_profile($sql, $duration);
        }

        if ($this->result) {
            return true;
        }
    }

    /**
     * Get the last query.
     *
     * @return last_query
     */
    public function last_query()
    {
        return $this->last_query;
    }

    /**
     * Get a string description of the last error.
     *
     * @return error
     */
    public function error()
    {
        return mysqli_error($this->connection);
    }

    /**
     * Get an associative list from the previous query as an array.
     *
     * @return assoc_list
     */
    public function assoc_list()
    {
        if (empty($this->result)) {
            return false;
        }

        $return = array();

        for ($i = 0; $i < $this->num_rows(); $i++) {
            $return[$i] = mysqli_fetch_assoc($this->result);
        }

        return $return;
    }

    /**
     * Get the next associative array from the last query. Useful when there is
     * only one presumed row (e.g. when selecting with WHERE ID = x).
     *
     * @return assoc_row
     */
    public function assoc_row()
    {
        if (empty($this->result)) {
            return false;
        }
        return mysqli_fetch_assoc($this->result);
    }

    /**
     * Get an indexed array from the last query.
     *
     * @return index_list
     */
    public function indexed_list()
    {
        if (empty($this->result)) {
            return false;
        }

        $return = array();

        for ($i = 0; $i < $this->num_rows(); $i++) {
            $return[$i] = mysqli_fetch_array($this->result);
        }

        return $return;
    }

    /**
     * Get the next indexed row from the last query.
     *
     * @return index_row
     */
    public function indexed_row()
    {
        if (empty($this->result)) {
            return false;
        }
        return mysqli_fetch_array($this->result);
    }

    /**
     * Get the ID of the last insert query.
     *
     * @return id
     */
    public function insert_id()
    {
        return mysqli_insert_id($this->connection);
    }

    /**
     * Get the number of affected rows from the last query.
     *
     * @return num_affected
     */
    public function affected_rows()
    {
        return mysqli_affected_rows($this->connection);
    }

    /**
     * Get the number of rows from the last select query.
     *
     * @return num_rows
     */
    public function num_rows()
    {
        return mysqli_num_rows($this->result);
    }

    /**
     * Escape a string.
     *
     * @param str
     *
     * @return escaped_str
     */
    public function escape($str)
    {
        return mysqli_real_escape_string($this->connection, $str);
    }

    /* HIGHER-LEVEL HELPER FUNCTIONS - Simple Active-Record Style System.  Room for improvement, but covers the basics. */
    /* not designed to replace every function, only most. */

    /**
     * Active-Record, add WHAT to query.
     *
     * @param column
     * @param as
     * @param escape Escape column string, default TRUE.
     */
    public function what($column, $as = null, $escape = true)
    {
        if (!is_array($this->hl_what)) {
            $this->hl_what = array();
        }

        if ($escape) {
            $what = $this->format_table_column($column);
        } else {
            $what = $column;
        }

        if (!empty($as)) {
            $what .= ' AS ' . $this->format_backticks($as);
        }

        $this->hl_what[] = $what;

        return true;
    }

    /**
     * Active-Record, add WHERE to query.
     *
     * @param column
     * @param value
     * @param operator Comparison operator, default '='.
     */
    public function where($column, $value, $operator = '=')
    {
        if (!is_array($this->hl_where)) {
            $this->hl_where = array();
        }

        if (array_search($operator, array('=','>','<','>=','<=','!=')) === false) {
            $operator = '=';
        }

        // handle null
        if ($value === null && $operator = '=') {
            $where = $this->format_table_column($column) . ' IS NULL';
        } elseif ($value === null && $operator = '!=') {
            $where = $this->format_table_column($column) . ' IS NOT NULL';
        } else {
            // default
            $where = $this->format_table_column($column) . $operator . $this->format_value($value);
        }

        $this->hl_where[] = $where;

        return true;
    }

    /**
     * Active-Record, add WHERE LIKE to query.
     *
     * @param column
     * @param value
     */
    public function where_like($column, $value)
    {
        if (!is_array($this->hl_where)) {
            $this->hl_where = array();
        }

        $where = $this->format_table_column($column) . ' LIKE ' . $this->format_value('%' . $value . '%');
        $this->hl_where[] = $where;

        return true;
    }

    /**
     * Active-Record, add WHERE NOT LIKE to query.
     *
     * @param column
     * @param value
     */
    public function where_not_like($column, $value)
    {
        if (!is_array($this->hl_where)) {
            $this->hl_where = array();
        }

        $where = $this->format_table_column($column) . ' NOT LIKE ' . $this->format_value('%' . $value . '%');
        $this->hl_where[] = $where;

        return true;
    }

    /**
     * Set the WHERE mode for the query to OR or AND. Returns FALSE when an invalid
     * mode is selected.
     *
     * @param mode
     *
     * @return status
     */
    public function where_mode($mode)
    {
        $mode = strtoupper($mode);

        if ($mode != 'OR' && $mode != 'AND') {
            return false;
        }

        $this->hl_where_mode = $mode;
        return true;
    }

    /**
     * Equivalent to where().
     *
     * @param column
     * @param value
     * @param operator Comparison operator, default '='.
     */
    public function where_col($column, $value, $operator = '=')
    {
        if (!is_array($this->hl_where)) {
            $this->hl_where = array();
        }

        if (array_search($operator, array('=','>','<','>=','<=','!=')) === false) {
            $operator = '=';
        }

        $where = $this->format_table_column($column) . $operator . $this->format_table_column($value);

        $this->hl_where[] = $where;

        return true;
    }

    /**
     * Set a custom WHERE string. Is NOT escaped, so needs to be used with care.
     *
     * @param string
     */
    public function where_string($string)
    {
        $this->hl_where_string = trim($string);
        return true;
    }

    /**
     * Return query result in random order.
     */
    public function random_order()
    {
        $this->hl_orderby = 'rand()';
    }

    /**
     * Active-Record, add ORDER BY to query.
     *
     * @param column
     * @param dir Direction, default 'asc'.
     */
    public function orderby($column, $dir = 'asc')
    {
        if (!preg_match('/asc/i', $dir) && !preg_match('/desc/i', $dir)) {
            $dir = 'asc';
        }

        $this->hl_orderby = $this->format_table_column($column) . ' ' . $dir;
        return true;
    }

    /**
     * Set a custom ORDER BY string. Is NOT escaped, so needs to be used with care.
     *
     * @param string
     */
    public function orderby_string($string)
    {
        $this->hl_orderby = trim($string);
        return true;
    }

    /**
     * Active-Record, add LIMIT to query. Returns FALSE if no valid integer provided.
     *
     * @param limit
     *
     * @return status
     */
    public function limit($limit)
    {
        if (!preg_match('/^[0-9]+$/', $limit)) {
            return false;
        }
        $this->hl_limit = $limit;
        return true;
    }

    /**
     * Active-Record, add OFFSET to query. Returns FALSE if no valid integer provided.
     *
     * @param offset
     *
     * @return status
     */
    public function offset($offset)
    {
        if (!preg_match('/^[0-9]+$/', $offset)) {
            return false;
        }
        $this->hl_offset = $offset;
        return true;
    }

    /**
     * Active-Record, add LEFT JOIN to query.
     *
     * @param table
     * @param column1
     * @param column2
     */
    public function leftjoin($table, $column1, $column2)
    {
        $table = $this->format_backticks($table);
        $column1 = $this->format_table_column($column1);
        $column2 = $this->format_table_column($column2);

        if (!is_array($this->hl_leftjoin)) {
            $this->hl_leftjoin = array();
        }

        $this->hl_leftjoin[] = array($table,$column1,$column2);
    }

    /**
     * Reset the Active-Record query.
     */
    private function reset_hlvars()
    {
        $this->hl_table = null;
        $this->hl_what = null;
        $this->hl_where = null;
        $this->hl_where_string = null;
        $this->hl_orderby = null;
        $this->hl_limit = null;
        $this->hl_offset = null;
        $this->hl_leftjoin = null;
        $this->hl_foundrows = null;
    }

    /**
     * Generate a SQL-formatted datetime from a timestamp.
     *
     * @param timestamp
     *
     * @return sql_timestamp
     */
    public function format_datetime($timestamp = null)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Format a value to be used in SQL queries, escaping it and wrapping quotes
     * around it if it's not an integer.
     *
     * @param value
     *
     * @return formatted_value
     */
    public function format_value($value)
    {
        if (!is_int($value)) {
            $value = '"' . $this->escape($value) . '"';
        }
        return $value;
    }

    /**
     * Format a value by escaping it and wrapping backticks around it.
     *
     * @param value
     *
     * @return formatted_value
     */
    public function format_backticks($value)
    {
        return '`' . $this->escape($value) . '`';
    }

    /**
     * Format a table.column value by wrapping backticks around both table and
     * column.
     *
     * @param value
     *
     * @return formatted_value
     */
    public function format_table_column($value)
    {
        $table_column = explode('.', $value);

        if (count($table_column) > 1) {
            $table = $this->format_backticks($table_column[0]);
            unset($table_column[0]);
            $column = implode('.', $table_column);

            if ($column != '*') {
                $column = $this->format_backticks($column);
            }

            return $table . '.' . $column;
        } else {
            return $this->format_backticks($table_column[0]);
        }
    }


    /**
     * High-level get function, using previously set parameters in Active-Record.
     * Returns associative list with results or FALSE.
     *
     * @param table
     *
     * @return assoc_list
     */
    public function get($table)
    {
        // param function in approximate order of use-frequency.
        /* select WHAT from TABLE left join LEFTJOIN on COLUMN1=COLUMN2 where WHERE order by ORDERBY limit LIMIT offset OFFSET. */


        if (empty($table)) {
            return false;
        } // we need a table!

        // get our table name.
        $table = $this->format_backticks($table);

        // put together our left join text.
        $leftjoin = '';
        if (is_array($this->hl_leftjoin)) {
            foreach ($this->hl_leftjoin as $lj) {
                $leftjoin .= ' LEFT JOIN ' . $lj[0] . ' ON ' . $lj[1] . '=' . $lj[2];
            }
        }

        $sql = 'SELECT ' . ($this->hl_foundrows ? 'SQL_CALC_FOUND_ROWS' : '') .
                      (!empty($this->hl_what) ? ' ' . implode(',', $this->hl_what) : ' *') .
                      ' FROM ' . TABLE_PREFIX . $table .
                      $leftjoin;

        if (!empty($this->hl_where_string)) {
            $sql .= ' WHERE ' . $this->hl_where_string;
        } elseif (!empty($this->hl_where)) {
            $sql .= ' WHERE ' . implode(' ' . $this->hl_where_mode . ' ', $this->hl_where);
        }

        $sql .= (!empty($this->hl_orderby) ? ' ORDER BY ' . $this->hl_orderby : '') .
                      (!empty($this->hl_limit) ? ' LIMIT ' . $this->hl_limit : '') .
                      (!empty($this->hl_offset) ? ' OFFSET ' . $this->hl_offset : '');

        $this->query($sql);

        $this->reset_hlvars(); // resets all higher level variables for the next query data.

        return $this->assoc_list();
    }

    /**
     * Same as get(), but returns only the first result. Useful when selecting based
     * on a specific ID. Returns FALSE if no row is returned.
     *
     * @param table
     *
     * @return assoc_list
     */
    public function get_one($table)
    {
        $this->limit(1); // just need one.
        $assoc_list = $this->get($table);

        if (!is_array($assoc_list) || count($assoc_list) < 1) {
            return false;
        }

        return $assoc_list[0];
    }

    /**
     * Call this to be able to use found_rows() after the select.
     */
    public function calc_found_rows()
    {
        $this->hl_foundrows = true;
    }

    /**
     * Get the number of 'found rows' (ignoring limit/offset) from the last query.
     * Returns FALSE if no rows were found or calc_found_rows() wasn't used.
     *
     * @return num_rows
     */
    public function found_rows()
    {
        if ($this->query('SELECT FOUND_ROWS()')) {
            $row = $this->indexed_row();
            return $row[0];
        }

        return false;
    }


    /**
     * High-level insert function using parameters set with Active-Record methods.
     * Returns insertion ID or FALSE if the query failed.
     *
     * @param table
     * @param data
     *
     * @return insert_id
     */
    public function insert($table, $data)
    {
        if (!is_array($data) || count($data) == 0) {
            return false;
        }

        foreach ($data as $item => $value) {
            if ($value === null) {
                $setsql[] = $this->format_table_column($item) . '=NULL';
            } else {
                $setsql[] = $this->format_table_column($item) . '=' . $this->format_value($value);
            }
        }

        $sql = 'INSERT INTO ' . TABLE_PREFIX . $this->format_backticks($table) . ' SET ' .
                      implode(', ', $setsql);

        $this->reset_hlvars(); // resets all higher level variables for the next query data. (hl vars not used, done for consistency)

        if ($this->query($sql)) {
            return $this->insert_id();
        } else {
            return false;
        }
    }

    /**
     * High-level update function using parameters set with Active-Record methods.
     * Returns the result from query(), or TRUE if nothing to update.
     *
     * @param table
     * @param data
     *
     * @return result
     */
    public function update($table, $data)
    {

    // nothing to update, but this isn't an error.
        if (!is_array($data) || count($data) == 0) {
            $this->reset_hlvars();
            return true;
        }

        // avoid inadvertantly updating all rows.
        if ((!is_array($this->hl_where) || count($this->hl_where) < 1) && empty($this->hl_where_string)) {
            return false;
        }

        foreach ($data as $item => $value) {
            if ($value === null) {
                $setsql[] = $this->format_table_column($item) . '=NULL';
            } else {
                $setsql[] = $this->format_table_column($item) . '=' . $this->format_value($value);
            }
        }

        // put together our left join text.
        $leftjoin = '';
        if (is_array($this->hl_leftjoin)) {
            foreach ($this->hl_leftjoin as $lj) {
                $leftjoin .= ' LEFT JOIN ' . $lj[0] . ' ON ' . $lj[1] . '=' . $lj[2];
            }
        }

        $query = 'UPDATE' .
                  ' ' . TABLE_PREFIX . $this->format_backticks($table) .
                  $leftjoin .
                  ' SET ' . implode(', ', $setsql) .
                  ' WHERE ' . (!empty($this->hl_where_string) ? $this->hl_where_string : implode(' ' . $this->hl_where_mode . ' ', $this->hl_where)) .
                  (!empty($this->hl_orderby) ? ' ORDER BY ' . $this->hl_orderby : '') .
                  (!empty($this->hl_limit) ? ' LIMIT ' . $this->hl_limit : '');

        $this->reset_hlvars(); // resets all higher level variables for the next query data.

        return $this->query($query);
    }

    /**
     * High-level delete function using parameters set by Active-Record methods.
     * Returns the result of query() or FALSE if no WHERE is set (to avoid
     * inadvertently deleting all rows).
     *
     * @param table
     *
     * @return result
     */
    public function delete($table)
    {
        if ((!is_array($this->hl_where) || count($this->hl_where) < 1) && empty($this->hl_where_string)) {
            return false;
        } // avoid inadvertantly updating all rows.

        // put together our left join text.
        $leftjoin = '';
        if (is_array($this->hl_leftjoin)) {
            foreach ($this->hl_leftjoin as $lj) {
                $leftjoin .= ' LEFT JOIN ' . $lj[0] . ' ON ' . $lj[1] . '=' . $lj[2];
            }
        }

        $query = 'DELETE' .
                  ' FROM ' . TABLE_PREFIX . $this->format_backticks($table) .
                  $leftjoin .
                  ' WHERE ' . (!empty($this->hl_where_string) ? $this->hl_where_string : implode(' ' . $this->hl_where_mode . ' ', $this->hl_where)) .
                  (!empty($this->hl_orderby) ? ' ORDER BY ' . $this->hl_orderby : '') .
                  (!empty($this->hl_limit) ? ' LIMIT ' . $this->hl_limit : '');

        $this->reset_hlvars(); // resets all higher level variables for the next query data.

        return $this->query($query);
    }

  /**
   * Quick query to check if an ID exists in a table.
   *
   * @param table
   * @param id
   */
    public function id_exists($table, $id)
    {
        $this->where('id', $id);
        $test = $this->get_one($table);
        if ($test) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * Check if a table column exists.
   */
    public function column_exists($table, $column)
    {
        $escaped_table = $this->escape($table);
        $escaped_column = $this->escape($column);
        $result = $this->query("SHOW COLUMNS FROM `$escaped_table` LIKE '$escaped_column'");
        if ($this->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

  /**
   * Enable or disable autocommit. Useful for limiting model methods to validation only, or otherwise testing before writing changes to the database.
   *
   * @param bool value
   */
    public function autocommit(bool $value)
    {
        $value = $value ? '1' : '0';
        $this->query('SET autocommit=' . $value);
    }

  /**
   * Commit changes and re-enable autocommit.
   */
    public function commit()
    {
        $this->query('COMMIT();');
        $this->autocommit(true);
    }

  /**
   *
   * Cancel database updates and re-enable autocommit.
   */
    public function rollback()
    {
        $this->query('ROLLBACK();');
        $this->autocommit(true);
    }
}
