<?php
/**
 * The core plugin class.
 * 
 * @package     ameriabank-vPOS-v3
 * @subpackage  ameriabank-vPOS-v3/classes
 * @author      E.Sinani <info.esinani@gmail.com>
 */
class Cf7b_Frontennd_Options {
    
    private $table_name;
    private $db;
    
    public $pluginDir;
   
    public function __construct(){    
        global $wpdb;
        $this->db = $wpdb;
        //add_action( 'wpcf7_before_send_mail', array( $this, 'saveCF7BData' ) );
        add_action('wpcf7_before_send_mail',  array( &$this,'save_form') );
    }
    
    /** 
    * Save Form Data Before Contact Form Send Mail 
    **/ 
    function save_form( $wpcf7 ) {         
       
       $tableBackup = $this->db->prefix.'contact_form7_backup';
       $tableConnections = $this->db->prefix.'contact_form7_backup_fields';
       
       $submission = WPCF7_Submission::get_instance();
       if ( $submission ) {
            $submited = array();
            $submited['title'] = $wpcf7->title();
            $submited['posted_data'] = $submission->get_posted_data();
            
            $tableInfo = $this->db->get_results("DESCRIBE " . $tableBackup);
            $backupFields = array();
            foreach($tableInfo as $k=>$v){
                array_push($backupFields, $v->Field);
            }
            
            $data = array(
                'formTitle'  => $wpcf7->title(), 
                'formID'  => $wpcf7->id(),
                'date' => date('Y-m-d H:i:s')
            );
            $connectionTableData = $this->db->get_results("Select * FROM " . $tableConnections);
            foreach ($connectionTableData as $key=>$val){
                $columnName = $val->cf7_backup_column;
                $filedName = $val->cf7_field_name;
                //check if field exists in db tabel
                 if(in_array($columnName, $backupFields)){
                    //create dynamic array for data insert
                    if(isset($submited['posted_data'][$filedName])){
                        $data[$columnName] = $submited['posted_data'][$filedName];
                    }
                }
            } 
            $this->db->insert($tableBackup, $data);
        }         
    }

    public function saveCF7BData($data){
        
        global $wpdb;
        $submission = WPCF7_Submission::get_instance();

        if ( $submission ) {
            $submited = array();
            $submited['title'] = $wpcf7->title();
            $submited['posted_data'] = $submission->get_posted_data();
        }

        $data = array(
                'first_name'  => $submited['posted_data']['fname'],
                'last_name'  => $submited['posted_data']['lname'],
                'email' => $submited['posted_data']['email'],
                'formTitle'  => $submited['title'], 
                'date' => date('Y-m-d H:i:s')
         );

        // $wpdb->insert( $wpdb->prefix . 'contact_form_backup', $data
        $wpdb->insert('contact_form_backup', $data);    
        
    }
    
    
    
    
}
