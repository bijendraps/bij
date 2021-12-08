<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Table Model
 */
class Table_model extends CI_Model
{
    /**
     * @var array A list of field names that need to be backticked if used
     */
    public $reserved_columns = [
        'key',
    ];

    /**
     * Table_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->dbforge();
    }

    /**
     * Update Tables
     *
     * Updates tables based on an array of tables => fields => attributes
     *
     * array(
     *    table_name => array(
     *        field_name => array(
     *            attribute => value,
     *            attribute => value,
     *        ),
     *        field_name => array( etc...
     *    ),
     *    table_name => array( etc...
     *
     * @param array $tables
     */
    public function update_tables(array $tables)
    {
        foreach ($tables as $table_name => $fields) {
            $this->update_table($table_name, $fields);
        }
    }

    /**
     * @param $table_name
     * @param $fields
     */
    public function update_table($table_name, $fields)
    {
        // create the table if it doesn't exist yet
        if (!$this->db->table_exists($table_name)) {
            $primary_key = null;

            $indexes = [];

            // check for our custom key and primary_key attributes
            foreach ($fields as $field => $attributes) {
                if (!$attributes) {
                    unset($fields[$field]);
                    continue;
                }

                if (!empty($attributes['primary_key'])) {
                    $primary_key = $field;
                }

                if (!empty($attributes['key'])) {
                    $this->dbforge->add_key($field);
                }

                if (!empty($attributes['index'])) {
                    $indexes[$field] = $attributes['index'];
                }
            }

            $this->dbforge->add_field($fields);

            if (!is_null($primary_key)) {
                $this->dbforge->add_key($primary_key, true);
            }

            $this->dbforge->create_table($table_name, true);

            foreach ($indexes as $field => $index) {
                $this->create_index($table_name, $field, $index);
            }
        } else {
            // get all the existing fields for this table
            $existing_fields = $this->table_to_array($table_name);

            /* this is too heavy handed
            // drop any existing fields that aren't in the table array
            foreach ($existing_fields as $field_name => $attributes)
            {
                if ( ! isset($fields[$field_name]))
                {
                    $this->dbforge->drop_column($table_name, $field_name);
                }
            }
            */

            // traverse through table array's fields
            foreach ($fields as $field_name => $attributes) {
                if (!$attributes) {
                    if ($this->db->field_exists($field_name, $table_name)) {
                        $this->dbforge->drop_column($table_name, $field_name);
                    }
                } // add the field if it doesn't already exist
                else {
                    if (!isset($existing_fields[$field_name])) {
                        if (!$this->db->field_exists($field_name, $table_name)) {
                            $this->dbforge->add_column($table_name, [$field_name => $attributes]);
                        }
                    } // or modify the field if necessary
                    else {
                        // only modify the column when this->table and existing table array (culled from table_to_array()) don't match
                        if (count(array_diff($attributes, $existing_fields[$field_name])) !== 0) {
                            $attributes['name'] = $field_name;

                            if ($this->db->field_exists($field_name, $table_name)) {
                                $this->dbforge->modify_column($table_name, [$field_name => $attributes]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Create an index on a MySQL table and column
     *
     * @param string $table_name
     * @param string $field_name
     * @param mixed $index_name optional. the name of index to be created. if blank, $field_name will be used
     */
    public function create_index($table_name, $field_name, $index_name = null)
    {
        if (!is_string($index_name) || !$index_name) {
            $index_name = $field_name;
        }

        $table_name = $this->db->dbprefix($table_name);

        $this->db->query("CREATE INDEX {$index_name} ON {$table_name}({$field_name});");
    }

    /**
     * Table to array
     *
     * Creates an array suitable for use with dbforge
     *
     * @param string $table_name
     *
     * @return array
     */
    public function table_to_array($table_name)
    {
        $indexes = $this->indexes($table_name);

        $query = $this->db->query("SHOW COLUMNS FROM `{$this->db->dbprefix}$table_name`");

        $fields = [];

        foreach ($query->result() as $row) {
            $field = [];

            if (preg_match('/^(.*) unsigned$/', $row->Type, $match)) {
                $field['unsigned'] = true;

                $row->Type = $match[1];
            }

            if (preg_match('/^(.*)\((\d+)\)$/', $row->Type, $match)) {
                $field['constraint'] = (int)$match[2];

                $row->Type = $match[1];
            }

            if ($row->Null === 'YES') {
                $field['null'] = true;
            }

            if (strpos($row->Extra, 'auto_increment') !== false) {
                $field['auto_increment'] = true;
            }

            if ($row->Key) {
                if ($row->Key === 'PRI') {
                    $field['primary_key'] = true;
                } else {
                    $field['key'] = true;
                }
            }

            if (!is_null($row->Default)) {
                $field['default'] = $row->Default;
            }

            $field['type'] = $row->Type;

            if (isset($indexes[$row->Field])) {
                $field['index'] = $indexes[$row->Field];
            }

            $name = (in_array($row->Field, $this->reserved_columns)) ? "`{$row->Field}`" : $row->Field;

            $fields[$name] = $field;
        }

        return $fields;
    }

    /**
     * @param $table_name
     * @return array
     */
    public function indexes($table_name)
    {
        $query = $this->db->query("SHOW INDEX FROM `{$this->db->dbprefix}$table_name`");

        $indexes = [];

        foreach ($query->result() as $row) {
            if ($row->Key_name === 'PRIMARY') {
                continue;
            }

            $indexes[$row->Column_name] = ($row->Key_name === $row->Column_name) ? true : $row->Key_name;
        }

        return $indexes;
    }

    /**
     * @param array $tables
     * @return array
     */
    public function tables_to_array(array $tables)
    {
        $tables_array = [];

        foreach ($tables as $table_name) {
            $tables_array[$table_name] = $this->table_to_array($table_name);
        }

        return $tables_array;
    }
}
