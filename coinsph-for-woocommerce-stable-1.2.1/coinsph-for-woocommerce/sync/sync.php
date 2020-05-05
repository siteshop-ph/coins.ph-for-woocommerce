<?php







// this sync is very needed for cancel woo order with EXPIRED transaction since coins.ph do not send notify for EXPIRED transaction.
//    the sync serve also for other transaction status, as a second update method in complement to notify











////////////// create wp cron to run syncronization  /////////////
if ( ! wp_next_scheduled( 'woocommerce_coinsph_synchronization' ) ) {
  wp_schedule_event( time(), 'daily', 'woocommerce_coinsph_synchronization' );  // alternative: twicedaily
}

     // cron tasks are stored in wp_options table option_name=cron

add_action( 'woocommerce_coinsph_synchronization', 'synchronization_coinsph' );
///////////////////////////////////////////////////////////////












   function synchronization_coinsph() {       
        // for test ECHO:  Disable  1/ here   and  2/ at closing of this function  3/enable debug mode in wp config file





$new_instance = new WC_Controller_coinsph();

// call this function within the class
$new_instance->__construct();  





      
          echo PHP_EOL . 'START CRON' . PHP_EOL . PHP_EOL;

        

                     if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug']) {

                             $new_instance->log->add( 'coinsph', 'START CRON: Synchronization' );  
                  
                     }















    // select all woo order having status "on-hold'" since this is the very first status we have for order when vrush tracking id is created (pickup order created)


//global $wp;                       //seem not needed
//global $woocommerce, $post;       //seem not needed



global $wpdb;
$order_to_checks = $wpdb->get_results( "SELECT ID FROM {$wpdb->base_prefix}posts WHERE post_status = 'wc-on-hold'" );
  // can work as well
   //$order_to_checks = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}posts WHERE post_status = 'wc-on-hold'" ); 







//for test
//var_dump($order_to_checks) . "<br><br>";












     /*   // Serve Nothing because coins.ph only have LIVE account


             if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {


                   if(get_option('woocommerce_coinsph_settings')['test_mode'] == 'yes'){

			   $new_instance->log->add( 'coinsph', 'CRON: coins.ph - TEST ACCOUNT USED' );
	           
                     }else{

                           $new_instance->log->add( 'coinsph', 'CRON: coins.ph - PRODUCTION ACCOUNT USED' );

                   }


             }


    */
  















if ( $order_to_checks ) {



     

                 	foreach ( $order_to_checks as $post ) {
		

                                            // Proceed in loop with all woo order found in $order_to_checks database querry
                                                  
                                                           //setup_postdata( $post ); // seem not needed


                                                                        $woo_order_id_to_check = $post->ID ;
                                                                        

                                                                                             // get the "meta_value" from table "wp_postmeta" when "meta_key"  = "_payment_method", 
                                                                          $payment_method = get_post_meta( $woo_order_id_to_check, '_payment_method', true );



                                                                                                     if ( 'coinsph' == $payment_method ) {




                                                                                                             //for test
                                                                                                             //echo "woo order ID: " . $post->ID ."<br><br>";





      
























           // Check if Active: WooCommerce Plugin "Custom Order Numbers" by unaid Bhura / http://gremlin.io/ 
           if( class_exists( 'woocommerce_custom_order_numbers' ) ) {

          
                 // for test
                 //echo "case custom_order_numbers plugin used";


                 // Retrieve real order from postmeta database table"
                 global $wpdb;

                 $wpdb->postmeta = $wpdb->base_prefix . 'postmeta';

		 $retrieved_order_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_custom_order_number' AND meta_value = '$result->merchantRef'" );
		
		  
                 $merchantTxnId_to_use = $retrieved_order_id;

                 //for test
                // echo 'case custom order number plugin used';
                //echo $merchantTxnId_to_use;

                  

           }else{

                  // just use regular woocommerce order (real woocommerce order = txnid used with coins.ph)
                  $merchantTxnId_to_use = $woo_order_id_to_check;


                  // for test
                  // echo 'case no custom order number plugin used';
                  //echo $merchantTxnId_to_use;
 
           }




















   










//////////////////////////////  START:  REQUEST  STATUS  TO  coins.ph  API  ////////////////////////////////////////////////////////////



$transactionKey = html_entity_decode(get_option('woocommerce_coinsph_settings')['coinsph_secret_key']);


// get the coins.ph billing ID associated with this woo order get the "meta_value" from table "wp_postmeta" when "meta_key"  = "coinsph_invoice_id",  
$coinsph_invoice_id_to_get_status = get_post_meta( $woo_order_id_to_check, 'coinsph_invoice_id', true );




                      // N.B.: no test API URL API with coins.ph only live available
          

         // $ch = curl_init("https://collector.coins.ph/v1/invoices/$coinsph_invoice_id_to_get_status");  // N.B:    no "?" used here, just direct invoice id   // OLD URL

        
        $ch = curl_init("https://api.coins.asia/v1/invoices/$coinsph_invoice_id_to_get_status");  // N.B:    no "?" used here, just direct invoice id


          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);                                                                 
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                   // seem transaction key (authentication) not needed to get an coins.ph invoice detail here                                                                                                          
           


          $result = curl_exec($ch);
          $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          $result = json_decode($result, true);

      






// full answer example for expired status:

/*

Array
(
    [invoice] => Array
        (
            [id] => 8a60ea7911f54e36a44325f6d6237b06
            [note] => 
            [note_scope] => private
            [status] => expired
            [category] => merchant
            [amount] => 10.00000000
            [currency] => PHP
            [amount_due] => 10.00000000
            [locked_rate] => 32966.00000000
            [initial_rate] => 29528.00000000
            [incoming_address] => 38MNGqymL4fNweiM6w1UNtL8HUn86epyHJ
            [external_transaction_id] => test-125
            [payment_url] => https://coins.ph/payment/invoice/8a60ea7911f54e36a44325f6d6237b06
            [metadata] => Array
                (
                )

            [created_at] => 2016-10-20T09:19:10.493527Z
            [updated_at] => 2016-10-20T09:34:09.716540Z
            [expires_at] => 2016-10-20T09:34:09.675323Z
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
            [btc_amount_due] => 0.00030334
            [expires_in_seconds] => 
        )

)


*/






//full answer example for pending status:

/*

{"invoice":{"id":"4fd762e91d2845c9b0226c78b9401682","note":"","note_scope":"private","status":"pending","category":"merchant","amount":"20.00000000","currency":"PHP","amount_due":"20.00000000","locked_rate":"32971.00000000","initial_rate":"32971.00000000","incoming_address":"3MJMQ5vG5w561A2KUK4fhENN5Aopz25EM9","external_transaction_id":"test-126","payment_url":"https://coins.ph/payment/invoice/4fd762e91d2845c9b0226c78b9401682","metadata":{},"created_at":"2016-10-29T08:35:58.052291Z","updated_at":"2016-10-29T08:35:58.053085Z","expires_at":"2016-10-29T08:50:57.322737Z","sender_name":"","sender_email":"","sender_mobile_number":"","payment_collector_fee_placement":"top","supported_payment_collectors":["cash_payment","coins_bitcoin_wallet","external_bitcoin_wallet","coins_peso_wallet"],"payments":[],"receiver":"0dc76b6f60d644fcad210d9a917a1fd7","btc_amount_due":"0.00060659","expires_in_seconds":"688"}}

*/










//full answer example for error in return:

/*

{"error_codes":[],"errors":{"non_field_errors":["Not found."]}}

*/














// for test
  // echo "<br /><br />Coins.ph - Invoice Status: " . $result['invoice']['status'] . "<br /><br /><br />";


/*        
  echo '<br><br>Coins.ph - Invoice Details:';
    echo '<pre>';
       print_r( $result );
    echo '</pre>';
*/





       

    






//////////////////////////////  END:  REQUEST  STATUS  TO  coins.ph  API  ////////////////////////////////////////////////////////////




















 // START:     process for each transaction




$order = new WC_Order( $woo_order_id_to_check);









             switch ( $result['invoice']['status'] ) {







                   #################### Case transaction is "expired"  ####################
                   case 'expired':      
          

                              // this CRON Sync is very needed to cancel order with expired transaction since coins.ph do not send notify for expired transaction
                                      
                                  
				   if($order->status == 'cancelled'){
                                  			         
                                       
                                        //No update needed  


                                      
				    }else{
                                       

                                         // update order status (an admin note will be also created)
                                         $order->update_status('cancelled'); 

                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> coins.ph: Invoice EXPIRED<br/> -> coins.ph Invoice: '.$result['invoice']['id'].'<br/> -> Order status updated to CANCELLED', 1); 

                                         // no reduce order stock needed


	                                 //empty cart
                                         // not needed

                                         // Do the redirection
                                         //not needed


                                                    if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug']) {

                                                           $new_instance->log->add( 'coinsph', 'CRON: Find one new coins.ph Invoice - EXPIRED: '.$result['invoice']['id'].' - For Order: ' . $order->get_order_number() );

                                                           $new_instance->log->add( 'coinsph', 'CRON: Order updated to CANCELLED - EXPIRED coins.ph Invoice: '.$result['invoice']['id'].' - For Order: ' . $order->get_order_number() );

	                                            }


                                         // no exit needed as it's will stop other order process "for each")


				    }  


                                    break;








                                            


                      #################### CASE:  transaction is "fully_paid"  ####################
                          case 'fully_paid':
             
                        

                                   if($order->status == 'processing' OR $order->status == 'completed'){
                                   				         
                                         //No update needed

                                      
				    }else{


                                         // update order status (an admin note will be also created)
                                         $order->update_status('processing'); 


                                         // Add Admin and Customer note
                                         $order->add_order_note(' -> coins.ph Payment SUCCESSFUL<br/> -> coins.ph Invoice: ' . $result['invoice']['id'] . '<br/> -> Order status updated to PROCESSING<br/> -> We will be shipping your order to you soon', 1); 

                                         // reduce stock
				         $order->reduce_order_stock(); // if physical product vs downloadable product

                                         //empty cart
                                         //not needed

                                         // no redirection needed


                                                    if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug']) {

                                                        $new_instance->log->add( 'coinsph', 'CRON: Find one new coins.ph Invoice - fully_paid: '.$result['invoice']['id'].' - For Order: ' . $order->get_order_number() );

                                                        $new_instance->log->add( 'coinsph', 'CRON: Order updated to PROCESSING - coins.ph Invoice - fully_paid: '.$result['invoice']['id'].' - For Order: ' . $order->get_order_number() );


	                                            }

                                         
                                         // no exit needed as it's will stop other order process "for each")

                                 }



                           break;










                   #################### Case transaction is "pending" waiting cash deposit at coins.ph ####################
                   case 'pending':                    
                                  

				       // nothing to do  


                                           // no exit needed as it's will stop other order process "for each")

                                         
				


                                    break;












                   #################### Case  transaction is  NO STATUS CODE GIVEN IN BACK ####################
                   default :                  


                          
                             // in case no coins.ph invoice status returned but only an error returned from the coins.ph

                                    // error are mostly for not found coins.ph invoice ID at coins.ph account


           
                                     if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug'] ) {
                                                                 
                                                                                              

                                                if ( isset( $result['error_codes'] ) ) {
                                                         $new_instance->log->add( 'coinph', 'CRON: coins.ph - API - *** ERROR *** ' . print_r($result, TRUE) . ' - For Order: ' . $order->get_order_number() );
                                                }


      
                                       }  





                                  
                                     


                                    break;











             }




//////////////////////////////  END:  REQUEST  STATUS  TO  coins.ph  API  /////////////////////////////////////////////////////////////





  


                        }	    // END:      if ( 'coinsph' == $payment_method ) {





	}	    // END:        foreach ( $order_to_checks as $post ) {






     } else {


	

                       // NOTHING TO DO


                                //for test


                                      //echo "nothing to do<br><br>";




     }    // END:        if ( $order_to_checks ) {










               echo "<br><br>CRON: Synchronization DONE<br><br>";


               echo "<br><br>END CRON <br><br>";












 // store last time ran for this cron
 $options = get_option('woocommerce_coinsph_settings');
 // update it
 date_default_timezone_set('Asia/Manila');
 $options['last_ran_cron_synchronization'] = date('Y-m-d\TH:i:s');
 // store updated data     
 update_option('woocommerce_coinsph_settings',$options);









                                     if ( 'yes' == get_option('woocommerce_coinsph_settings')['debug']) {
                            
                                                 $new_instance->log->add( 'coinsph', 'CRON: Synchronization DONE');

                                                 $new_instance->log->add( 'coinsph', 'END CRON: Synchronization');
 
                                      }
















      } // END   function synchronization_coinsph() {          // Disable here for test ECHO










?>
