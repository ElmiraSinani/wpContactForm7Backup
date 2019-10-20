<?php
/**
 * The core plugin class.
 * 
 * @package     contactForm7Backup
 * @subpackage  contactForm7Backup/classes
 * @author      E.Sinani <info.esinani@gmail.com>
 */
class Cf7b_Admin_Settings {
       
    private $backup_table;
    private $connection_table;
    private $db;
    
    public function __construct() {         
        global $wpdb;
        
        $this->db = $wpdb;
        $this->backup_table = $wpdb->prefix.'contact_form7_backup';;
        $this->connection_table = $wpdb->prefix.'contact_form7_backup_fields';   
                
        //Add Admin menue items
        add_action( 'admin_menu', array( $this, 'cf7b_global_settings' ) );  
        add_action( 'admin_menu', array( $this, 'cf7b_backup_fields_connection'));
        //Add plugin Styles and Scripts
        add_action( 'admin_init', array( $this, 'load_cf7b_script_css' ) );
    }
    //Load plugin Styles and Scripts
    function load_cf7b_script_css(){
        wp_enqueue_script( 'cf7bjs', CF7B_URL.'/js/adminScripts.js', array( 'jquery' ), null, true );
        wp_enqueue_style( 'cf7bcss', CF7B_URL.'/css/adminStyles.css', array(), null );		
    }
    //Add Admin Menu Item
    function cf7b_global_settings () {
        add_menu_page( 'Contact Form7 Backup Settings','CF7 Backup','manage_options','cf7b-backup-global-options', array($this,'cf7b_backup_global_options_callback') );
    }  
    //Add Submenu to Admin Menu Item
    function cf7b_backup_fields_connection() {
        add_submenu_page('cf7b-backup-global-options', 'Backup Fields Connection', 'Show DB and Form Fields Connection', 'manage_options', 'cf7b-backup-fields-connection', array($this,'cf7b_backup_fields_connection_callback') );
    }    

    function cf7b_backup_global_options_callback(){ 
        $fields = $this->db->get_results("SELECT * FROM " .  $this->backup_table);
        $tableInfo = $this->db->get_results("DESCRIBE " .  $this->backup_table);
                
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
        $tableInfo = $this->db->get_results("DESCRIBE " .  $this->backup_table);        
        $fields = $this->db->get_results("SELECT * FROM " . $this->connection_table);        
        $titleVal = "";
        $tagNameVal = "";
        $columnNameVal = "";  
        //Getting Edit Record Values
        if(isset($_GET['edit']) && $_GET['edit']!=""){
            $oldData = $this->db->get_results("SELECT * FROM " . $this->connection_table ." WHERE id='".$_GET['edit']."'");
            $titleVal = $oldData[0]->title;
            $tagNameVal = $oldData[0]->cf7_field_name;
            $columnNameVal = $oldData[0]->cf7_backup_column;
        }
        //Delete Functionality
        if(isset($_GET['delete']) && $_GET['delete']!=""){
            $oldData = $this->db->get_results("SELECT `cf7_backup_column` FROM " . $this->connection_table ." WHERE id='".$_GET['delete']."'");
            $deleteColumnName =  $oldData[0]->cf7_backup_column;           
            //check if column exists in db tabel
            $checkField = $this->db->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '". $this->backup_table."' AND column_name = '".$deleteColumnName."'"  );
            var_dump($deleteColumnName);
            var_dump($checkField);
            if(!empty($checkField)){
                //Drop Column
                $this->db->query("ALTER TABLE  $this->backup_table DROP $deleteColumnName");
            }
            //delete connection table record
            $this->db->delete($this->connection_table, array('id'=>$_GET['delete']));
            wp_redirect(CF7B_CONNECTION_URL);
            exit;
        }
        //Save New Record in Connections Table And Create New Column in Backup Table
        if (isset($_POST['save'])){
            extract($_POST);            
            $postData= array(
                'title'=>$title,
                'cf7_field_name'=> $tag_name,
                'cf7_backup_column'=> $column_name
            );              
            //check if field exists in db tabel
            $checkField = $this->db->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '". $this->backup_table."' AND column_name = '".$column_name."'"  );
            if(empty($checkField)){
                $this->db->insert($this->connection_table, $postData);
                //add new column into backup data table
                $this->db->query("ALTER TABLE  $this->backup_table ADD $column_name VARCHAR(255) DEFAULT NULL AFTER $afterColumn");
                wp_redirect(CF7B_CONNECTION_URL);
                exit;
            }
        }
        $content = '<h3 class="title">Add New Connection Between Database Tabel Column and Form</h3>
                    <hr/>
                    <form class="addConnection" action="" method="POST">
                        <label><span>Field Title</span> <input type="text" name="title" value="'.$titleVal.'"/></label>
                        <label><span>Contact Form 7 Tag name</span> <input type="text" name="tag_name" value="'.$tagNameVal.'" /></label>
                        <label><span>DB Table column Name</span> <input type="text" name="column_name" value="'.$columnNameVal.'" /></label>
                        <label><span>Add Column After</span><select name=afterColumn>'; 
            foreach ($tableInfo as $k=>$v){
                $content .= '<option value="'.$v->Field.'">'.$v->Field.'</option>';
            }                        
        $content .=  '</select></label>
                     <label class="save"><input type="submit" name="save" value="Save" /></label>
                    </form>
                    <hr/>';
         $content .= '<h3 class="title">Contact Form 7 Fields Connection</h3>
                    <hr/>
                    <table class="connections_list">
                        <thead>
                            <tr>
                                <th>Field Title</th>
                                <th>Contact Form 7 tag name</th>
                                <th>Databes Tabel Column Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody> ';        
        foreach ($fields as $key => $value) { 
            $content .= '<tr>';
            $content .= '<td>'.$value->title.'</td>';
            $content .= '<td>'.$value->cf7_field_name.'</td>';
            $content .= '<td>'.$value->cf7_backup_column.'</td>';            
            $content .= '<td  align="center">'
                    . '<a href="'.CF7B_CONNECTION_URL.'&delete='.$value->id.'">Delete</a></td>';            
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';
        
        echo $content;        
    } 
}
