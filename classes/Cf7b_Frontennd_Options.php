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
        
        if($_POST['submit_purchaser_form']){
            $this->submitAbvpForm($_POST);
        }
        $this->pluginDir = WP_PLUGIN_DIR.'/ameriabank-vPOS-v3';      
        
        add_shortcode('purchaser-form', array($this, 'getAbvpForm'));
    }

    public function getAbvpForm(){        
        $formContent = file_get_contents($this->pluginDir.'/includes/partials/_purchaser_form.php');
        echo $formContent;
    }
    
    public function setTable($table_name) {
        $this->table_name = $table_name;
    }

    public function submitAbvpForm($data){
        global $wpdb;
        
        unset($data['submit_purchaser_form']);
        
        $data['created'] = date("Y-m-d H:i:s");
        $data['updated'] = date("Y-m-d H:i:s");
        $data['comments'] = "Data Inserted From Contributation Form";
        
        $wpdb->insert($wpdb->prefix.'abvp_purchasers',$data);
        $purchaserID = $wpdb->insert_id;
        
        $this->proceedAbvpAPICall($data, $purchaserID);        
        
    }
    
    public function proceedAbvpAPICall($data, $purchaserID) {
        global $wpdb;
        //API Url
        $url = "https://servicestest.ameriabank.am/VPOS/api/VPOS/InitPayment"; 
        
        //InitPaymentRequest fields
        $params = array(
            'ClientID', 
            'Username', 
            'Password',
            'Currency',
            'Description',
            'Amount',
            'BackURL',
            'Opaque'            
        );
        foreach ($params as $paramID) {  
            $dataArray[$paramID] = get_option('abvp_'.$paramID);
        }
        $mode = 'test';
        
        if($mode=='test'){
            //should be removed after test mode
            $dataArray['Amount'] = '10';  
            //should be unique and should canged dynamically
            $dataArray['OrderID'] = '2305118';  
            $dataArray['CardHolderID'] = '128';  
        } else {
            //should be removed after test mode
            $dataArray['Amount'] = $data['amount'];  
            //should be unique and should canged dynamically
            $dataArray['OrderID'] = '2305118';  
            $dataArray['CardHolderID'] = '128'; 
        }
        

        //init curl
        $curl = curl_init();  
        //curl setopt
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,     
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($dataArray),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $response = json_decode(curl_exec($curl));
        //var_dump($response);
        $getInfo = curl_getinfo($curl);        
        $PaymentID = $response->PaymentID; 
        
        curl_close($curl);
        
        if(isset($PaymentID) && $PaymentID !='' ){ 
            $updateData['paymentID'] = $PaymentID;
            $updateData['status'] = 'Submited';
            $updateData['updated'] = date("Y-m-d H:i:s");
            $updateData['comments'] = serialize(array('response'=>$response, 'curl_getinfo'=>$getInfo));
            $paymentURL = "https://servicestest.ameriabank.am/VPOS/Payments/Pay?id=".$PaymentID."&lang=en";
        } else {            
            $updateData['comments'] = serialize(array('response'=>$response, 'curl_getinfo'=>$getInfo));
        }
        $wpdb->update($wpdb->prefix.'abvp_purchasers', $updateData, array('id'=>$purchaserID)); 
        
        if(isset($paymentURL) && $paymentURL!=""){
            
            //wp_redirect( $paymentURL );
            ?>
            <script type="text/javascript">
                document.location.href="<?php echo $paymentURL; ?>";
            </script>
            <?php
        }
    }
    
    
}
