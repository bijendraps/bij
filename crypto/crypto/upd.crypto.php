<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 */
class crypto_upd
{
    public $module_name;
    public $version;
    public $current;
    private $mod_actions = [
        'crypto_encrypted_action',
        'crypto_decrypted_action'
    ];
 
    /**
     * Tables
     *
     * List of custom tables to be used with table_model->update_tables() on install/update
     *
     * Notes about field attributes:
     * -use an int, not a string, for constraint
     * -use custom attributes, key, index and primary_key set to TRUE
     * -don't set null => false unneccessarily
     * -default values MUST be strings
     *
     * But really, use the console and run the table model table_to_array() method
     *
     * @var array
     */
    private $tables = [
        'crypto_settings' => [
            'site_id' => [
                'type' => 'int',
                'constraint' => 4,
                'default' => '1',
            ],
            '`key`' => [
                'type' => 'varchar',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'text',
                'null' => true,
            ],
            'serialized' => [
                'type' => 'int',
                'constraint' => 1,
                'null' => true,
            ],
        ],
    ];
    private $fieldtypes = [
        'crypto_encrypted_text'
    ];
   
    private $sites = [];
    private $settings = [];

    public function __construct()
    {
        ee()->load->dbforge();

        $this->module_name = strtolower(str_replace(['_ext', '_mcp', '_upd'], '', __CLASS__));
        $this->version = "1.0.0";

        /*
         * Get Site IDs
         */
        $query = ee()->db->select('site_id')->get('sites');

        foreach ($query->result() as $row) {
            $this->sites[] = $row->site_id;
        }

        $query->free_result();

        /*
         * Get Settings
         */
        if (ee()->db->table_exists('crypto_settings')) {
            $query = ee()->db->get('crypto_settings');

            foreach ($query->result() as $row) {
                $this->settings[$row->site_id][$row->key] = $row->serialized ? @unserialize($row->value) : $row->value;
            }

            $query->free_result();
        }
    }

    public function install()
    {
        ee()->db->insert('modules', [
            'module_name' => 'Crypto',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n',
        ]);


        ee()->load->model('table_model');

        ee()->table_model->update_tables($this->tables);

        // install the module actions from $this->mod_actions
        foreach ($this->mod_actions as $method) {
  
                ee()->db->insert('actions', ['class' => 'Crypto', 'method' => $method]);
        }


        $this->install_fieldtypes();

        return true;
    }

    /**
     * @return bool
     */
    protected function install_fieldtypes()
    {
        require_once APPPATH . 'fieldtypes/EE_Fieldtype.php';

        foreach ($this->fieldtypes as $fieldtype) {
            // check if already installed
            if (ee()->db->where('name', $fieldtype)->count_all_results('fieldtypes') > 0) {
                ee()->db->update('fieldtypes', ['version' => $this->version], ['name' => $fieldtype]);

                continue;
            }

            $class = ucwords($fieldtype . '_ft');
            $fieldTypeClassVars = get_class_vars($class);

            ee()->db->insert('fieldtypes', [
                'name' => $fieldtype,
                'version' => $this->version,
                'settings' => base64_encode(serialize([])),
                'has_global_settings' => method_exists($class, 'display_global_settings') ? 'y' : 'n',
            ]);
        }

        return true;
    }
    /**
     * @param string $current
     * @return bool
     */
    public function update($current = '')
    {
        $this->current = $current;

        if ($this->current == $this->version) {
            return false;
        }


    }
    /**
     * @return bool
     */
    public function uninstall()
    {
        ee()->db->delete('modules', ['module_name' => 'Crypto']);
        ee()->db->like('class', 'Crypto', 'after')->delete('actions');

        return true;
    }
}
