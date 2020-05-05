<?php
/*
	Plugin Name: coins.ph Payment Gateway For WooCommerce
	Plugin URI: https://github.com/siteshop-ph?tab=repositories
	Description: coins.ph Payment Gateway for accepting payments with settlement in PHP currency. Because credit card and banking penetration is too low ; coins.ph makes accessible payment options to the masses! This plugin require a coins.ph BUSINESS Account you can get: <a href="https://coins.ph/business/">HERE</a> and later your account need to be approved/verified by coin.ph support; 
	Version: 1.2.1
	Author: Serge Frankin  SiteShop.ph (Netpublica.com Corp.)
*/ 






//Load the function
add_action( 'plugins_loaded', 'woocommerce_coinsph_init', 0 );

/**
 * Load coinsph gateway plugin function
 * 
 * @return mixed
 */
function woocommerce_coinsph_init() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
         return;
    }












error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);


     // to prevent such notice when wp debug mode is enabled
          /*

          Notice: Constant CRYPT_AES_MODE_MCRYPT already defined in /home/demo-coinsph-woocommerce/public_html/wp-content/plugins/dragonpay-for-woocommerce/lib/Crypt/AES.php on line 123

          Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; Crypt_Hash has a deprecated constructor in /home/demo-coinsph-woocommerce/public_html/wp-content/plugins/dragonpay-for-woocommerce/lib/Crypt/Hash.php on line 94


          */






  
    




    /**
     * Define the coinsph gateway
     * 
     */
    class WC_Controller_Coinsph extends WC_Payment_Gateway {

        /**
         * Construct the coinsph gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {

            global $woocommerce;

            $this->id = 'coinsph';
            $this->icon = plugins_url( 'assets/coinsph.png', __FILE__ );
            $this->has_fields = false;
          
            $this->method_title = __( 'coins.ph', 'woocommerce_coinsph' );


            // Load the form fields.
            $this->init_form_fields();


            // Load the settings.
            $this->init_settings();





            // Define user setting variables.
            $this->enabled = $this->settings['enabled'];
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];


                       
   


            // Actions.
           // add_action( 'woocommerce_receipt_coinsph', array( &$this, 'receipt_page' ) );



            // Active logs.
		if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}

            


          //save setting configuration
          add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
               
         // Payment API hook
         add_action( 'woocommerce_api_wc_controller_coinsph', array( $this, 'coinsph_response' ) );

        
         add_action( 'woocommerce_receipt_coinsph', array( $this, 'receipt_page' ) );
        

        }



        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options() {
            ?>
            <h3><?php _e( 'coins.ph', 'woocommerce_coinsph' ); ?></h3>
            <p><?php _e( 'coins.ph is the most popular Bitcoin e-commerce Payment Gateway in the Philippines.', 'woocommerce_coinsph' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }



        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce_coinsph' ),
                    'type' => 'checkbox',
                    'label' => __( '  Enable/Disable Plugin' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'woocommerce_coinsph' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_coinsph' ),
                    'default' => __( 'Coins.ph | Cash or Online Secure Payments', 'woocommerce_coinsph' )
                ),
                'description' => array(
                    'title' => __( 'Description', 'woocommerce_coinsph' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce_coinsph' ),
                    'default' => __( 'Pay Cash at M Lhuillier, 7-ELEVEN, BDO branches OR Pay Online<br /><u><b>Important:</b></u> Only submit order if you are able to pay within the next 15 mins.', 'woocommerce_coinsph' )
                ),
                'coinsph_secret_key' => array(
                    'title' => __( 'Business/Merchant API Token', 'woocommerce_coinsph' ),
                    'type' => 'text',
                    'description' => __( 'Enter your Business/Merchant API Token: You can find it within your coins.ph Account at "Settings" section (top right corner drop-down menu where you have your coins.php email\'s account), next go at "Merchant API Access" section. <a href="https://siteshop.ph/app/views/client/siteshop/images/coinsph_merchant_account.png" target="_blank">Important: See Screenshot</a>', 'woocommerce_coinsph' ),
                    'default' => 'my-coins.ph-merchant-api-secret'
                ),                
		'debug' => array(
		     'title' => __( 'Debug Log', 'woocommerce-coinsph' ),
	             'type' => 'checkbox',
	             'label' => __( 'Enable Debug log', 'woocommerce-coinsph' ),
		     'default' => 'yes',
                     'description' => sprintf( __( 'Log coins.ph events, such as Web Redirection, Notification, inside: log file %s', 'woocommerce-coinsph' ), '<code>wp-content/uploads/wc-logs/coinsph-' . sanitize_file_name( wp_hash( 'coinsph' ) ) . '.log</code>&nbsp;&nbsp;&nbsp;<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs" target="_blank">See log file content</a>&nbsp;&nbsp;&nbsp;<br><br>If the file do not exist, please check that 1/ "wc-logs" folder exist and is Writable and 2/ "Log Directory Writable" item is fine:&nbsp;&nbsp;&nbsp;<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status" target="_blank"> Here</a> <br><br> For Common API Errors and Solutions, check section 11 <a href="https://siteshop.ph/plugin/support_manager/knowledgebase/view/6/setup-guide-coins-ph-for-woocommerce/9/" target="_blank">Here</a>' )
		), 
		'last_ran_cron_synchronization' => array(
		     'title' => __( 'Daily Order Status Synchronization', 'woocommerce_coinsph' ),
	             'type' => 'text',
	             'label' => __( 'Last Sync timestamp', 'woocommerce_coinsph' ),
		     'default' => '',
                     'disabled' => true,
	             'description' => __( 'Last Successful Sync - Timestamp<br><br><u>Explanation:</u><br>WP-Cron, it s an auto scheduled task run every 24 hours to synchronize your order status with your Coins.ph Merchant Account. It\'s usefull in case on the fly order status update failled with Coins.ph notification. Also because Coins.ph do not send notify when transaction EXPIRED, so this daily syncronization is helpful to Cancel wooCommerce order associated with a Coins.ph EXPIRED transaction.', 'woocommerce_coinsph' )
		),
		'display_callback_url' => array(
		     'title' => 'Callback URL',
                     'type' => 'title',
		     'description' => 'This URL must be set in your coins.ph Account at "Settings" section (top right corner drop-down menu where you have your coins.php email\'s account), next go at "Merchant API Access" section. <a href="https://siteshop.ph/app/views/client/siteshop/images/coinsph_merchant_account.png" target="_blank">Important: See Screenshot</a><br><font color="red"><code>'.WC()->api_request_url('WC_Controller_Coinsph').'</code></font>',
		     'desc_tip' => false,
                     'default' => ''                     
		),
		'display_return_url' => array(
		     'title' => 'Return URL',
                     'type' => 'title',
		     'description' => 'This URL must be set in your coins.ph Account at "Settings" section (top right corner drop-down menu where you have your coins.php email\'s account), next go at "Merchant API Access" section. <a href="https://siteshop.ph/app/views/client/siteshop/images/coinsph_merchant_account.png" target="_blank">Important: See Screenshot</a><br><font color="red"><code>'.WC()->api_request_url('WC_Controller_Coinsph').'</code></font>',
		     'desc_tip' => false,
                     'default' => ''
		)          
            );
        }













        /**
         * Generate the form.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_form( $order_id ) {

            global $woocommerce;
        
            try{

	       $order = new WC_Order( $order_id ); 
            
    
    
    
             // to prevent to create more than one transaction code for same order, since it's will give error at coins.ph is $txnid was ever send
          if($order->status != 'pending') {




               // nothing to do



     }else{


              // Case order/transaction to create


    
    
    
    
    
    
    
    
    
            ## Hostname of woocommerce install
            $hostname = $_SERVER['HTTP_HOST']; 


                   





           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {

                   // take CUSTOM order id string from WooCommerce Plugin "Custom Order Numbers" (there can be prefix, etc)
	           $txnid = $order->custom_order_number; 

           }else{

                   // just use regular woocommerce order id as txnid for coinsph
                   $txnid = $order->id;
 
           }







	    //$amount = $order->order_total;        
        $amount = number_format ($order->order_total, 2, '.' , $thousands_sep = '');
	$ccy = get_woocommerce_currency();
        
        
	$coinsph_secret_key = html_entity_decode(get_option('woocommerce_coinsph_settings')['coinsph_secret_key']);
 
         






               // this plugin only use production account as coins.ph do no supply test/sandbox account
		  // $url = 'https://collector.coins.ph/v1/invoices';    // OLD  URL

	             $url = 'https://api.coins.asia/v1/invoices/';




	





          $request_body = array(
                  "amount"=> $amount,
                  "currency"=> $ccy,
                  "external_transaction_id"=> $txnid
              );



          $data_string = json_encode($request_body);





          $ch = curl_init($url);  
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string); 
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
              'Content-Type: application/json',             
              'Authorization: Token ' . $coinsph_secret_key)  //API Secret
          );                                                                                                                   
           


          $result = curl_exec($ch);
          $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $result = json_decode($result, true);






/////////////////////////////////////////////



/*

good answer reult example from coins.ph API:


(
    [invoice] => Array
        (
            [id] => ce472cf3f5c5469094e87ab87ffeb450
            [note] => 
            [note_scope] => private
            [status] => pending
            [category] => merchant
            [amount] => 10000.00000000
            [currency] => PHP
            [amount_due] => 10000.00000000
            [locked_rate] => 28243.00000000
            [initial_rate] => 28243.00000000
            [incoming_address] => 3MorgoVDuARN8ZYgHez12NxNK9Zq7CZai8
            [external_transaction_id] => azerty002
            [payment_url] => https://coins.ph/payment/invoice/ce472cf3f5c5469094e87ab87ffeb450
            [metadata] => Array
                (
                )

            [created_at] => 2016-09-28T08:16:48.046980Z
            [updated_at] => 2016-09-28T08:16:48.047820Z
            [expires_at] => 2016-09-28T08:31:47.783791Z
            [sender_name] => 
            [sender_email] => 
            [sender_mobile_number] => 
            [payment_collector_fee_placement] => top
            [supported_payment_collectors] => Array
                (
                    [0] => cash_payment
                    [1] => coins_bitcoin_wallet
                    [2] => external_bitcoin_wallet
                    [3] => coins_peso_wallet
                )

            [payments] => Array
                (
                )

            [receiver] => 0dc76b6f60d644fcad210d9a917a1fd7
            [btc_amount_due] => 0.35407004
            [expires_in_seconds] => 899
        )

)


/////////  wrong answer example:

Array
(
    [error_codes] => Array
        (
        )

    [errors] => Array
        (
            [non_field_errors] => Array
                (
                    [0] => Invalid token.
                )

        )

)


///////


(
    [error_codes] => Array
        (
            [0] => duplicate_external_transaction_id
        )

    [errors] => Array
        (
            [external_transaction_id] => Array
                (
                    [0] => External transaction ID has already been used for an invoice.
                )

        )

)


/////


(
    [error_codes] => Array
        (
        )

    [errors] => Array
        (
            [amount] => Array
                (
                    [0] => Amount should be at most 10000 PHP.
                )

        )

)



*/
                            






          // update order status only if there is transaction code in answer
          if ( isset( $result['invoice']['id'] ) ) {



                 // case a transaction code was generated



                         ///// cookie //////////////

                             // this is needed for the coins.ph redirection back to shop since coins.ph do not send back txnid in GET data

                                // delete previous cookie if there is
                                unset( $_COOKIE['txnid'] );
                                setcookie( 'txnid', '', time() - ( 15 * 60 ) );  // by setting past date it's will delete cookie


                                // create cookie
                                setcookie( 'txnid', $txnid, 1 * 'DAYS_IN_SECONDS', COOKIEPATH, COOKIE_DOMAIN );

                         ///////////////////////////



                                        

                                                  if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
                                                                   

                                                             $this->log->add( 'coinsph', 'coins.ph - Invoice CREATED - ' . $result['invoice']['id'] . ' - For Order ' . $order->get_order_number() );                                                        


                                                    }  
                                           




         
                                         // no reduce order stock for now needed


                                         //no empty cart needed for now
                                        





                 $pay_widget_url = $result['invoice']['payment_url'];                


                 header("Location: $pay_widget_url");  

                 exit;







      
      }else{

  
          // no coins.ph invoice generated (ERROR ?)






          // Display error description at customer frontend
          echo '<br><div><big><big><font color="#e02b2b"><center><u>Error:</u></big>  ' ;


                                        if ( isset( $result['error_codes'] ) ) {
                                               echo "<br> For error detail please check your coins.ph logs at your shop panel: WooCommerce >> System Status >> Logs >> coins.ph - xxxxxxxxxxxxxxxxxx.log  >> View" ;
                                         }






      echo  '</font><br><br><font color="blue">IF YOU ARE A SHOPPER PLEASE COPY ABOVE MESSAGE AND SEND IT BY EMAIL TO THE SHOP ADMIN</font></b>       </center></big></div> ';

     














           
                              if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
                                                                 
                                                                                              

                                        if ( isset( $result['error_codes'] ) ) {
                                               $this->log->add( 'coinph', 'coins.ph - API - *** ERROR *** ' . print_r($result, TRUE) );
                                         }



      
                              }  




            // empty cart
            // not needed as cart was make empty when coins.ph ID code is generated 




    }




















////////////////////////////////////////////////







 
 
 // for test :  full response from transaction request
        
   //  echo '<br><br><big>Full Gateway Answer for Debug:</big><hr><pre>';
   //  print_r( $result );
   //  echo '</pre>';
     
  
       
       
       
                
      
        } // end of "try"



 } // end case     order/transaction to create






        catch(Exception $e) {

          echo '<div><span>An Error occurred. Please try again.</span></div>';
        
        } // end exeption


       
     







        } // end generate form


























        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function coinsph_order_error( $order ) {
            $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce_coinsph' ) . '</p>';
            $html .='<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'woocommerce_coinsph' ) . '</a>';
            return $html;
        }










        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */

    
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);

      }

   











        /**
         * Output for the order received page.
         * 
         */
        public function receipt_page( $order ) {
            echo $this->generate_form( $order );
            
        }































function coinsph_response($txnid_to_use){


// Example of response from coinsph:
//     


/*

Callback URL, POST DATA in body:
{"event": {"data": {"currency": "PHP", "amount": "10", "external_transaction_id": "azertyA017", "id": "e8092308b052456a9c6e5c2dd8b9d007", "amount_received": "0"}, "name": "invoice.created"}}


Redirect URL, GET DATA in URL:
invoice_id=e8092308b052456a9c6e5c2dd8b9d007


Callback URL, POST DATA in body:
{"event": {"data": {"currency": "PHP", "amount": "10", "external_transaction_id": "azertyA017", "id": "e8092308b052456a9c6e5c2dd8b9d007", "amount_received": "10"}, "name": "invoice.fully_paid"}}
 
*/	
   


        global $woocommerce;


    
        $body_response_callback = file_get_contents('php://input');

        $body_response_callback = json_decode( $body_response_callback, true );





 




// IMPORTANT: for info coins.ph send very different data depending:


     // if redirection back to shop (GET):  
                //no authentication (no header signature), no woo order id send back in URL, no transaction status given, but redirection normaly only happen when transaction is paid or when invoice is expired (after about 10 mins)



     // if IPN (POST): 
              //authentication by header signature + complete json POST data in body


     







                        



                               if ( isset( $_GET['invoice_id'] ) ) {


                                        // CASE :  Redirect URL case from coins.ph to shop:  




                                            // e.g.:   http://demo-coinsph-woocommerce.siteshop.ph/test/back.php?invoice_id=a2adee490871496d94149e166a9d2bfa

                                            // coins.ph invoice ID (it's not woo order ID!)), so since coins.ph do not send other parameters for redirection back to shop   

                                             $response_case = "GET"; 


                                             $coinsph_response_refno = $_GET['invoice_id'];  // coins.ph invoice ID (internal ref at coins.ph)   



                                                      if ( isset( $_COOKIE['txnid'] ) ) {


                                                             $txnid = $_COOKIE['txnid'];  // order ID at woo, sincecoins.pg do not send it back for GET data
                                             
                                                             $coinsph_response_txnid = $txnid;  // not given from coins.ph response but bellow we use same variable name "$coinsph_response_txnid" for GET  OR  POST  Data from coins.ph to get "$txnid_to_use"


                                                      } 


                                             
                                }

















                              if ( isset( $body_response_callback['event']['data']['external_transaction_id'] ) ) {


                                        // CASE:  Post data response (notify)  // POST json in body // Callback URL case



                                             $response_case = "POST";  

                                                 
                                             $coinsph_response_txnid = $body_response_callback['event']['data']['external_transaction_id'];
    
                                             $coinsph_response_refno = $body_response_callback['event']['data']['id']; // coins.ph invoice ID (internal ref at coins.ph)   
                                             $coinsph_response_status = $body_response_callback['event']['name'];




                                             $headers =  $this->parse_request_headers();   // this function is defined very bellow                                           
                                             
                                            
                                             // Received signature  
                                             $header_authorisation = $headers['Authorization'] ;  // give such string :       Token 4jkjkjkjkjk7878tretrvbhjh
  
                                             $header_authorisation =  str_replace("Token ", "", $header_authorisation);   // to remove "token " prefix




                                             // true signature
                                             $coinsph_secret_key = html_entity_decode(get_option('woocommerce_coinsph_settings')['coinsph_secret_key']);



                               
                              }   
























           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {


                 global $wpdb;

                 $wpdb->postmeta = $wpdb->base_prefix . 'postmeta';

		$retrieved_order_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_custom_order_number' AND meta_value = '$coinsph_response_txnid'" );		
		  
                 $txnid_to_use = $retrieved_order_id;

                  //for test
                  //echo 'case custom order number plugin used';
                  //echo $txnid_to_use;



           }else{

                  // just use regular woocommerce order (real woocommerce order = txnid used with coinsph)
                  $txnid_to_use = $coinsph_response_txnid;
 

                  // for test
                  // echo 'case no custom order number plugin used';
                  //echo $txnid_to_use;

           }



















//////////////// START:  "order confirmation" page  ////////////////////////////////


// This is for not having log writting for non-related to coinsph data received
if ( $response_case == "GET" AND isset( $_GET['invoice_id'] ) ) {   



        // case GET data :  redirection from coins.ph back to shop

        // coins.ph redirection to shop normaly only happen when transaction is paid, or after invoice is expired (about 10 mins)

        // by coins.ph API: in this case, no authentication (no header signature), no woo order id send back in GET parameter in URL, no transaction status given,







$order = new WC_Order( $txnid_to_use );







                                         ///////////// Do the redirection

                                         // hard coded redirection way for the ending point name "order-received":
                                         //$redirect = add_query_arg('key', $order->order_key, add_query_arg('order-received', $txnid_to_use, $this->get_return_url($order)));
                                         
                                      
                                        // dynamic redirection way for ending point name
                                        // (in case it was renamed from woocommerce general checkout settings):
                                         global $wpdb;

                                         $wpdb->options = $wpdb->base_prefix . 'options';

		                         $order_received_endpoint = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'woocommerce_checkout_order_received_endpoint'" );



                                     




                                                   if ( isset( $_COOKIE['txnid'] ) ) {


                                                         // CASE:  $txnid IS GIVEN in cookie (needed to find $txnid_to_use) 


                                                          $redirect = add_query_arg('key', $order->order_key, add_query_arg($order_received_endpoint, $txnid_to_use, $this->get_return_url($order)));   // so "order confimation" page WILL INCLUDE order detail recap 



                                                          // Example of retrieved url  ($redirect value)
                                                          // N.B.: wc_order_553a37860ff34 can also be found from postmeta table, but we do not used that way
                                                          // http://demo-woocommerce.siteshop.ph/checkout/order-received/?order-received=131&key=wc_order_553a37860ff34





                                                          wp_redirect($redirect); //do the redirect






                                                                   if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {

                                                                         $this->log->add( 'coinsph', 'coins.ph - Web Redirection back to shop Received - coins.ph invoice -  '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );

	                                                            }





                                                   } else {




                                                          // CASE:  $txnid IS NOT GIVEN in cookie (so we can no find : $txnid_to_use) 

             
                                                                  $redirect = $this->get_return_url($order);   // so "order confimation" page WILL NOT INCLUDE order detail recap



                                                                   wp_redirect($redirect); //do the redirect





                                                                          if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {

                                                                                  $this->log->add( 'coinsph', 'coins.ph - Received web redirection back to shop - coins.ph invoice -  '.$coinsph_response_refno );

	                                                                  }






                                                   }







                                         exit;	


}


//////////////// END:  "order confirmation" page  ////////////////////////////////






















   




// This is for not having log writting for non-related to coinsph data received
if ( $response_case == "POST" AND isset( $headers['Authorization'] ) ) {        // these value come from above


        
           // case POST data notification from coins.ph




$order = new WC_Order( $txnid_to_use );  // important this must be located here for being also able to get log for very bellow case when digest is wrong 








        // check if coins.ph POST NOTIFICATION IS AUTHENTIC
         // Disable this line when testing all gateway type of response 
    if( $header_authorisation == $coinsph_secret_key ) {     










   /// check if order status exist in woocommerce


   //////////////////////////////////////////////


   // IMPORTANT  NONE OF THIS OTHER WY WAS WORKING:

//if(!is_null($txnid_to_use)) {                 // ok with custom_order_numbers plugin used       NO without plugin
//if(isset($txnid_to_use)) {                    // ok with custom_order_numbers plugin used     NO without plugin
//if(!is_null($txnid_to_use) AND isset($txnid_to_use) ) {
//$order = if(new WC_Order( $txnid_to_use )){   // ;
// only continue if 
// if (!is_null(new WC_Order( $txnid_to_use))) {
// only continue with order existing in woocommerce
//$status = $order->status;
//if(isset($tatus)) {
//if(is_bool($status)) {

   ///////////////////////////////////////////










   /////////  check if order status exist in woocommerce

   $post_status = "";

   global $wpdb;  
   $wpdb->posts = $wpdb->base_prefix . 'posts';      
   $post_status = $wpdb->get_var( "SELECT post_status FROM $wpdb->posts WHERE post_type = 'shop_order' AND ID = '$txnid_to_use'");

   // for test
   //echo "post_status:  ".$post_status;
   //echo "strlen:  " .strlen($post_status);



   // only continue with existing order in woocommerce (that have an existing order status)
   // for info: when custom_order_numbers plugin used and if no order is found, this custom_orders_numbers plugin set post_id (real woocomerce order id) to zero "0"
   // if status have at least 2 characters long it's exist 
   if(strlen($post_status) > 2 ) {

   ////////////////////













///////////////// Available WooCommerce order status   //////////////////////////////
////////////////////////////////////////////////////////////////////////////////////
////    Pending     – Order received (unpaid)
////    Failed      – Payment failed or was declined (unpaid)
////    Processing  – Payment received and stock has been reduced- the order is awaiting fulfilment
////    Completed   – Order fulfilled and complete – requires no further action
////    On-Hold     – Awaiting payment  
////    Cancelled   – Cancelled by an admin or the customer – no further action required
////    Refunded    – Refunded by an admin – no further action required
/////////////////////////////////////////////////////////////////////////////////////





    




      



  
                                 
     	


	      switch ( $coinsph_response_status ) {
			








                   #################### Case transaction is "invoice.created"  waiting deposit for OTC or online payment ####################
                   case 'invoice.created':                    
                                  

				   if($order->status == 'on-hold'){
                                  				         
                                         //No update needed (to prevent double notification from GET and POST)


                                         //No add note needed   (to prevent double notification from GET and POST)

                                       


                                                   if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
                                                           $this->log->add( 'coinsph', 'coins.ph - Notification received - invoice.created - '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );
                                                    }




                                         //exit;	

                                      



				    }else{


	         

                                         // update order status (an admin note will be also created)
                                         $order->update_status('on-hold'); 




                                          // create a meta_key & meta_value
                                          // coins.ph have no parameter for we send woo order ID to API, so we need to create a record for store in db the coins.ph billing ID, to be associate/linked with the woo order      

                                                   $coinsph_invoice_id = $coinsph_response_refno;

                                                   // nevermind "WooCommerce Plugin "Custom Order Numbers" is used or not,
                                                   // we use native woo order ID 
                                                      $woo_order_id = $order->id; 


                                               // ref: https://codex.wordpress.org/Function_Reference/add_post_meta
                                               add_post_meta( $woo_order_id, 'coinsph_invoice_id', $coinsph_invoice_id, true ); 






                                         // Add Admin and Customer note
                                            // $order->add_order_note(' -> coins.ph invoice.created:<br/>'.$coinsph_response_refno.'<br/> -> Order Status Updated to ON-HOLD', 1);   
                                         $order->add_order_note(' -> coins.ph invoice.created:<br/>' . $coinsph_response_refno . '<a href=\"/wp-content/plugins/coinsph-for-woocommerce/inquire.php?invoiceID=' . $coinsph_response_refno . ' \" target=\"_blank\"><br/> -> Inquire Raw Transaction Details</a><br/> -> Order Status Updated to ON-HOLD', 1); 




	         
                                         // no reduce order stock needed


                                         //empty cart
                                         $woocommerce->cart->empty_cart();

 


                                                    if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {

                                                           $this->log->add( 'coinsph', 'coins.ph - Notification Received - invoice.created - '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );

                                                           $this->log->add( 'coinsph', 'Order Status Updated to ON-HOLD - coins.ph - invoice.created - '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );
                                                    
	                                            }




                                         //exit;
	

				    }  



                                    break;












                      
                          #################### CASE:  transaction is "invoice.fully_paid" (SUCCESS/PAID) ####################
                          case 'invoice.fully_paid':
						

				   if($order->status == 'processing' OR $order->status == 'completed'){
                                   				         
                                            //No update needed


                                            //No add note needed





                                             //exit;	



                                      
				    }else{



                                         // update order status (an admin note will be also created)
                                         $order->update_status('processing'); 

                                         // Add Admin and Customer note
                                            //$order->add_order_note(' -> coins.ph Payment SUCCESSFUL<br/> -> coins.ph invoice: '.$coinsph_response_refno.'<br/> -> Order status updated to PROCESSING<br/> -> We will be shipping your order to you soon', 1); 

                                          $order->add_order_note(' -> coins.ph Payment SUCCESSFUL<br/> -> coins.ph invoice: ' . $coinsph_response_refno . '<a href=\"/wp-content/plugins/coinsph-for-woocommerce/inquire.php?invoiceID=' . $coinsph_response_refno . ' \" target=\"_blank\"><br/> -> Inquire Raw Transaction Details</a><br/> -> Order status updated to PROCESSING<br/> -> We will be shipping your order to you soon', 1); 



                                         // reduce stock
				                        $order->reduce_order_stock(); // if physical product vs downloadable product

                                         //empty cart
                                         $woocommerce->cart->empty_cart();




                                                    if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {

                                                          $this->log->add( 'coinsph', 'coins.ph - Notification received - invoice.fully_paid - '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );

                                                          $this->log->add( 'coinsph', 'Order Status updated to PROCESSING - invoice.fully_paid - '.$coinsph_response_refno.' - For Order ' . $order->get_order_number() );

	                                            }




                                               //exit;	



				    }  



                                   break;


	             









                   #################### Case  transaction is  NO ERROR CODE OR STATUS GIVEN IN BACK ####################
                   default :                                                    
 
                                    // Do the redirection

                                    wp_redirect(home_url('/')); //redirect to homepage


                                    exit;

                                    break;


















}     //END:      Switch           

   












}      // END:        if order exist in woocommerce:    if(strlen($post_status) > 2 )














//            /*           // Enable this line when testing all gateway type of response









}else{               // end:     check if coins.ph POST NOTIFICATION IS AUTHENTIC    





             // Case  AUTHENTICATION of notify was false


                            
                        // nothing to do
                               

                          

                                  if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
                                             $this->log->add( 'coinsph', 'coins.ph - Notification Received - *** WRONG AUTHENTICATION *** - coins.ph invoice - '.$coinsph_response_refno.' - For order - ' .$coinsph_response_txnid );
	                           }





                        //exit;




}






//            */             // Here enable this line when testing all gateway type of response
















}else{  

              // end :        if ( $response_case == "POST" AND isset( $headers['Authorization'] ) ) {    
               
                      // case there was no POST data & no $headers['Authorization']  data



                               // nothing to do
                       




}

}





////////////////////////////////////////////////////////////////////////////////
                                            
                                             
      /**
      * Request header
      */
      function parse_request_headers() {
        
        $headers = array();
        foreach($_SERVER as $key => $value) {
        
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
        
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        
        }
        
        return $headers;
      
      }


                                             
///////////////////////////////////////////////////////////////////////////////////////////






}   // end:      class WC_Controller_Coinsph extends WC_Payment_Gateway {











	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/	
		

		function coinsph_plugin_action_links($links, $file) {
		    static $this_plugin;

		    if (!$this_plugin) {
		        $this_plugin = plugin_basename(__FILE__);
		    }

		    if ($file == $this_plugin) {
		        $settings_link = '<a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_controller_coinsph">Settings</a>';

		        array_unshift($links, $settings_link);
		    }
		    return $links;
		}
	

               add_filter('plugin_action_links', 'coinsph_plugin_action_links', 10, 2);














//////////////// START:  wrong currency notice ////////////////////////////////


	function wrong_currency_notice_coinsph(){
		
                        
                         if( get_woocommerce_currency() != 'PHP' ){

   
                              // end of php to start html
	                      ?>

		               <div class="update-nag">
		                  <b>coins.ph Payment Gateway require that WooCommerce Currency be set to Philippine Peso</b><br>                                       
     	                      </div>


	                      <?php 
                              // re-start of php

		         }
	
         }





add_action( 'admin_notices', 'wrong_currency_notice_coinsph' );


//////////////// END:  wrong currency notice ////////////////////////////////





















//////////////// START:  Wrong protocol notice ////////////////////////////////


function wrong_protocol_notice_coinsph(){


//////// check protocol used ////

   $isSecure = false;


if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $isSecure = true;
}



elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    $isSecure = true;
}





$REQUEST_PROTOCOL = $isSecure ? 'https' : 'http';




//////// display notice ////

              if( $REQUEST_PROTOCOL != 'https' ){

    
              // end of php to start html
	      ?>

	     <div class="update-nag">
	        <b>your website is not using httpS , using unsecured connection is a VERY MAJOR SECURITY CONCERN when using coins.ph payment gateway plugin! </b><br>                                       
     	     </div>


	      <?php 
              // re-start of php

	     }

///////////////////////////






}





add_action( 'admin_notices', 'wrong_protocol_notice_coinsph' );


//////////////// END:  Wrong protocol notice ////////////////////////////////

















//////////////  START:    WP cron for the coins.ph syncronization   ////////////////////////////////




              // this sync is very needed for cancel woo order with EXPIRED transaction since coins.ph do not send notify for EXPIRED transaction.
                  //    the sync serve also for other transaction status, as a second update method in complement to notify from coins.ph

                    

             
             require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "sync" . DIRECTORY_SEPARATOR . "sync.php"; 

             

//////////////  END:    WP cron for the coins.ph syncronization   ////////////////////////////////















	/**
 	* Add coinsph Gateway to WC
 	**/
    function woocommerce_coinsph_add_gateway( $methods ) {
        $methods[] = 'WC_Controller_Coinsph';
        return $methods;
    }


    add_filter( 'woocommerce_payment_gateways', 'woocommerce_coinsph_add_gateway' );
















}











?>
