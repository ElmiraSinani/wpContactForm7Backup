<?php
/**
 * The core plugin class.
 * 
 * @package     ameriabank-vPOS-v3
 * @subpackage  ameriabank-vPOS-v3/classes
 * @author      E.Sinani <info.esinani@gmail.com>
 */
class Cf7b_Frontennd_Options {
    
    private $backup_table;
    private $connection_table;
    private $db;
       
    public function __construct(){    
        global $wpdb;
        
        $this->db = $wpdb;
        $this->backup_table = $wpdb->prefix.'contact_form7_backup';;
        $this->connection_table = $wpdb->prefix.'contact_form7_backup_fields';
        
        add_action('wpcf7_before_send_mail',  array( &$this,'saveCF7BData') );
    }
    
    /** 
    * Save Form Data Before Contact Form Send Mail 
    **/ 
    function saveCF7BData( $wpcf7 ) {  
       $submission = WPCF7_Submission::get_instance();
       
       if ( $submission ) {
            $submited = array();
            $submited['title'] = $wpcf7->title();
            $submited['posted_data'] = $submission->get_posted_data();
            
            $tableInfo = $this->db->get_results("DESCRIBE " . $this->backup_table);
            $backupFields = array();
            foreach($tableInfo as $k=>$v){
                array_push($backupFields, $v->Field);
            }
            
            $data = array(
                'formTitle'  => $wpcf7->title(), 
                'formID'  => $wpcf7->id(),
                'date' => date('Y-m-d H:i:s')
            );
            $connectionTableData = $this->db->get_results("Select * FROM " . $this->connection_table);
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
            $this->db->insert($this->backup_table, $data);
        }         
    }      
}
