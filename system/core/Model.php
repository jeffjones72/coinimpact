<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * CodeIgniter Model Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/config.html
 */
class CI_Model {
    
    private $skip_fields = array();
    public static $supported_ids = array('account', 'player', 'boss', 'mission', 'item',
        'thing', 'combatant', 'player_boss', 'player_boss_combatant', 'event', 'modifier');
    private $valid = false;
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($data = null) {
        $repo = Repo::getInstance();
        $class = get_class($this);
        foreach(get_object_vars($this) as $field => $value) {
            $this->skip_fields[] = $field;
        }
        if(is_array($data)) {
            foreach($data as $name => $val) {
                if($class == 'PlayerCombatant' && $name == 'place_id') {
                    $this->player_place = new PlayerPlace($val);
                } /*else if(substr($name, -3) == "_id" && in_array(substr($name, 0, -3), self::$supported_ids)) {
                    $this->{substr($name, 0, -3)} = $repo->getByID(getClassFromEntityName(substr($name, 0, -3)), $val);
                }*/
                if(!isset($this->{$name})) {
                    $this->{$name} = $val;
                }
            }
            $this->valid = true;
        }
        else if(is_numeric($data)) {
            $table_name = getTableFromClass($class);
            //$this->load->model(strtolower($table_name).'s_model');
            $this->db->select('*');
            $this->db->where('id', $data);
            $this->db->from($table_name);   
            $query = $this->db->get();

            if ($query->num_rows == 1) {
                $result = $query->row_array();
                CI_MODEL::__construct($result);
                $this->valid = true;
            } else {
                $this->valid = false;
                //throw new Exception('not found');
            }
        }
    }
    /**
     * __get
     *
     * Allows models to access CI's loaded classes using the same
     * syntax as controllers.
     *
     * @param	string
     * @access private
     */
    function __get($key) {
        if(in_array($key, self::$supported_ids)) {
            if(!isset($this->{$key.'_id'})) {
                throw new Exception('Invalid var requested '.$key.'_id');
            }
            $query = $this->db->query('SELECT * FROM '.getTableFromEntityName($key).' WHERE id="'.$this->{$key.'_id'}.'"');
            if($query->num_rows == 0) {
                throw new Exception('No result found for '.$key.' and id='.$this->{$key.'_id'}.'.');
            }
            $class = getClassFromEntityName($key);
            $this->{$key} = new $class($query->row_array());
            return $this->{$key};
        }
        $CI = & get_instance();
        return $CI->$key;
    }
    public function is($thing) {
        $class = ucfirst($thing);
        return $this instanceof $class;
    }
    function addFields($pairs) {
        foreach($pairs as $key => $value) {
            $this->{$key} = $value;
        }
    }
    public static function add($data) {
        $db = get_instance()->db;
        $table = getTableFromClass(get_called_class());
        
        foreach($data as $v => $k) {
            $this->db->set($v, $k);
        }
        $this->db->insert($table);
    }
    public function save() {
        $vars = get_object_vars($this);
        $have_set = false;
        foreach($vars as $name => $value) {
            if(in_array($name, $this->skip_fields) || is_object($value)) {
                continue;
            }
            $prop = new ReflectionProperty($this, $name);
            if($prop->isStatic()) {
                continue;
            }
            $this->db->set($name, $value);
            $have_set = true;
        }
        if($have_set) {
            try {
                $this->db->replace(getTableFromClass(get_class($this)));
            } catch (Exception $e) {
                throw new Exception('Save query failed with query: '.$this->db->last_query().' and message '.$e->getMessage());
            }
        }
    }
    public function delete() {
        $table = getTableFromClass(get_class($this));
        $this->db->where('id', $this->id);
        $this->db->delete($table);
    }
    public function equals($object) {
        if(get_class($object) != get_class($this)) {
            return false;
        }
        $vars1 = get_object_vars($this);
        $vars2 = get_object_vars($object);
        // Uncompleted
    }
    public function __toString() {
        $str = "<pre>";
        foreach (get_object_vars($this) as $key => $val) {
            if(is_object($val) || is_array($val)) {
                continue;
            }
            $str .= $key."=".$val."\n";
        }
        $str .= "</pre>";
        return $str;
    }
    public function isValid() {
        return $this->valid;
    }
    public static function getAll() {
        $entities = array();
        $db = get_instance()->db;
        $class = get_called_class();
        $table = getTableFromClass($class);
        
        $db->select('*');
        $db->from($table);
        
        $query = $db->get();
        $rows = $query->result_array();
        
        foreach($rows as $row) {
            $entities[] = new $class($row);
        }
        
        return $entities;
    }
}

// END Model Class

/* End of file Model.php */
/* Location: ./system/core/Model.php */