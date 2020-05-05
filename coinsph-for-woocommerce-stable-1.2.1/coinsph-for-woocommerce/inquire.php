<?php





    // THIS SERVE TO: have a simple page to show coins.ph invoice detail






          // get coins.ph invoice id    
            // the info is sent from woo admin when admin click to get coins.ph invoice detail (in admin order note);

      
        $invoiceID = $_GET["invoiceID"] ; 

        


         


                    
          

       //   $ch = curl_init("https://collector.coins.ph/v1/invoices/$invoiceID");  // N.B:    no "?" used here, just direct invoice id     // OLD  URL

           $ch = curl_init("https://api.coins.asia/v1/invoices/$invoiceID");  // N.B:    no "?" used here, just direct invoice id



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






 


echo "Coins.ph Invoice: <font color=\"blue\">" . $invoiceID .  "</font><br /><br />";



echo "<br /><br />" . "Inquire - Coins.ph - Invoice Status:" . "<hr>" . " <big><font color=\"blue\">" .$result['invoice']['status'] . "</font></big><br /><br /><br />";



        
echo '<br><br>Inquire - Coins.ph - Invoice Details:<hr>';
  echo '<pre>';
     print_r( $result );
  echo '</pre>';
 
  

    
     
     
     
?>
