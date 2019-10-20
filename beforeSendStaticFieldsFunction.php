<?php

add_action('wpcf7_before_send_mail', 'cf7backup_form_data' );
 
function cf7backup_form_data( $wpcf7 ) {
   global $wpdb; 
   $submission = WPCF7_Submission::get_instance();
 
   if ( $submission ) { 
       $submited = array();
       $submited['title'] = $wpcf7->title();
       $submited['posted_data'] = $submission->get_posted_data();  
 
        $data = array(
                'first_name'  => $submited['posted_data']['fname'],
                'last_name'  => $submited['posted_data']['lname'],
                'email' => $submited['posted_data']['email'],
                'form'  => $submited['title'], 
                'date' => date('Y-m-d H:i:s')
        ); 
        $wpdb->insert($wpdb->prefix . 'contact_form_backup', $data);
     }
}