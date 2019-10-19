<?php
/**
 * The core plugin class.
 * 
 * @package     contactForm7Backup
 * @subpackage  contactForm7Backup/classes
 * @author      E.Sinani <info.esinani@gmail.com>
 */
class Cf7b_Admin_Settings {
    
    private $table_name;
    private $db;
    
    public $errors = array();
    public $pluginDir;
    public $globalSettingsDir;

    public function __construct() {         
        global $wpdb;
        $this->table_name = $table_name;
        $this->db = $wpdb;
        $this->pluginDir = WP_PLUGIN_DIR.'/contactForm7Backup';
        $this->globalSettingsDir = "contactForm7Backup/includes/partials/_manage_general_settings.php";
       
        add_action( 'admin_menu', array( $this, 'cf7b_global_settings' ) );  
        add_action( 'admin_menu', array( $this, 'cf7b_backup_fields_connection'));
    }
    function cf7b_global_settings () {
        add_menu_page( 'Contact Form7 Backup Settings','CF7 Backup','manage_options','cf7b-backup-global-options', array($this,'cf7b_backup_global_options_callback') );
    }  
    
    function cf7b_backup_fields_connection() {
        add_submenu_page('cf7b-backup-global-options', 'Backup Fields Connection', 'Show DB and Form Fields Connection', 'manage_options', 'cf7b-backup-fields-connection', array($this,'cf7b_backup_fields_connection_callback') );
    }
    
    public function setTable($table_name) {
        $this->table_name = $table_name;
    }
    public function getRow($id) {
        return $this->db->get_row("SELECT * FROM " . $this->table_name . " WHERE id= " . (int) $id);
    }  
     public function findAll() {
        return $this->db->get_results("SELECT * FROM " . $this->table_name);
    }    
    public function updateRow($data, $where) {        
       return $this->db->update($this->table_name, $data, $where );
    }
    public function deleteRow( $where ){
        $this->db->delete($this->table_name, $where);
    }
    
    function cf7b_backup_global_options_callback(){
        global $wpdb;
        $tableBackup = $wpdb->prefix.'contact_form7_backup';
        
        $fields = $this->db->get_results("SELECT * FROM " . $tableBackup);
        $tableInfo = $this->db->get_results("DESCRIBE " . $tableBackup);
       
        $content = '<h3 class="title">Contact Form 7 Backup Data</h3>
                    <hr/>
                    <table class="connections_list">
                        <thead>
                            <tr>';
        foreach ($tableInfo as $k=>$v){
                $content .= '<th>'. ucwords(str_replace('_',' ',$v->Field)).'</th>';
        }
        
        $content .=         '</tr>
                        </thead>
                    <tbody> ';
        
        if(isset($fields[0])){
            $fieldsCount = count((array)$fields[0]);            
            foreach ($fields as $key => $value) { 
                $content .= '<tr>';                
                foreach ($value as $k => $v) {
                    $content .= '<td align="center">'.$v.'</td>';
                }
                $content .= '</tr>';
            }
        }
        $content .= '</tbody></table>';            
        echo $content;
    }

    function cf7b_backup_fields_connection_callback() {
        global $wpdb;
        $tableConnections = $wpdb->prefix.'contact_form7_backup_fields';
        $tableBackup = $wpdb->prefix.'contact_form7_backup';
        $tableInfo = $this->db->get_results("DESCRIBE " . $tableBackup);
        
        $fields = $this->db->get_results("SELECT * FROM " . $tableConnections);
        
        if (isset($_POST['save'])){
            extract($_POST);            
            //$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
            $postData= array(
                'title'=>$title,
                'cf7_field_name'=> $tag_name,
                'cf7_backup_column'=> $column_name
            );
            
            //check if field exists in db tabel
            $checkField = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '".$tableBackup."' AND column_name = '".$column_name."'"  );

            if(empty($checkField)){
               //insert info to connection table
               $this->db->insert($tableConnections, $postData);
               //add new column into backup data table
               $wpdb->query("ALTER TABLE $tableBackup ADD $column_name VARCHAR(255) DEFAULT NULL AFTER $afterColumn");
               $url = $_SERVER['PHP_SELF'].'?page=cf7b-backup-fields-connection';
               wp_redirect($url);
               exit;
            }
        }
        
        $content .= '<h3 class="title">Add New Connection</h3>
                    <hr/>
                    <form class="addConnection" action="" method="POST">
                        <label><span>Field Title</span> <input type="text" name="title" /></label>
                        <label><span>Contact Form 7 Tag name</span> <input type="text" name="tag_name" /></label>
                        <label><span>DB Table column Name</span> <input type="text" name="column_name" /></label>
                        <label><span>Add Column After</span><select name=afterColumn>'; 
                        foreach ($tableInfo as $k=>$v){
                            $content .= '<option value="'.$v->Field.'">'.$v->Field.'</option>';
                        }                        
        $content .=     '</select></label>
                        <label class="save"><input type="submit" name="save" value="Save" /></label>
                    </form>
                    <hr/>';

         $content .= '<h3 class="title">Contact Form 7 Fields Connection</h3>
                    <hr/>
                    <table class="connections_list">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Field Title</th>
                                <th>Contact Form 7 tag name</th>
                                <th>Databes Tabel Column Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody> ';
        
        foreach ($fields as $key => $value) { 
            $content .= '<tr>';
            $content .= '<td align="center">'.$value->id.'</td>';
            $content .= '<td>'.$value->title.'</td>';
            $content .= '<td>'.$value->cf7_field_name.'</td>';
            $content .= '<td>'.$value->cf7_backup_column.'</td>';            
            $content .= '<td  align="center"><a href="">Refresh</a> | <a href="">Edit</a> | <a href="">Delete</a></td>';            
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';
            
        echo $content;
        
    } 
   
    
}
