<?php
class ControllerExtensionPaymentCoinToPay extends Controller 
{
	public function index() 
    {
		$data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['action'] = $this->url->link('extension/payment/cointopay/sendCointopay', '', '');

        $data['mid'] = $this->config->get('payment_cointopay_merchantID');
        $data['sid'] = $this->config->get('payment_cointopay_security_code');
        $data['payment_cointopay_display'] = 'Cointopay';//$this->config->get('payment_cointopay_merchantID');
        $data['currency_code'] = $order_info['currency_code'];
        $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $data['cart_order_id'] = $this->session->data['order_id'];
        $data['card_holder_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
        $data['street_address'] = $order_info['payment_address_1'];
        $data['city'] = $order_info['payment_city'];

        if ($order_info['payment_iso_code_2'] == 'US' || $order_info['payment_iso_code_2'] == 'CA') {
            $data['state'] = $order_info['payment_zone'];
        } else {
            $data['state'] = 'XX';
        }

        $data['zip'] = $order_info['payment_postcode'];
        $data['country'] = $order_info['payment_country'];
        $data['email'] = $order_info['email'];
        $data['phone'] = $order_info['telephone'];

        if ($this->cart->hasShipping()) {
            $data['ship_street_address'] = $order_info['shipping_address_1'];
            $data['ship_city'] = $order_info['shipping_city'];
            $data['ship_state'] = $order_info['shipping_zone'];
            $data['ship_zip'] = $order_info['shipping_postcode'];
            $data['ship_country'] = $order_info['shipping_country'];
        } else {
            $data['ship_street_address'] = $order_info['payment_address_1'];
            $data['ship_city'] = $order_info['payment_city'];
            $data['ship_state'] = $order_info['payment_zone'];
            $data['ship_zip'] = $order_info['payment_postcode'];
            $data['ship_country'] = $order_info['payment_country'];
        }

        $data['products'] = array();

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $data['products'][] = array(
                'product_id'  => $product['product_id'],
                'name'        => $product['name'],
                'description' => $product['name'],
                'quantity'    => $product['quantity'],
                'price'       => $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false)
            );
        }


        $data['lang'] = $this->session->data['language'];

        $data['return_url'] = $this->url->link('extension/payment/cointopay/callback', '', true);

        return $this->load->view('extension/payment/cointopay', $data);
	}
    public function sendCointopay()
    {
        $this->load->model('checkout/order');
        $callbackUrl = $this->url->link('extension/payment/cointopay/callback', '', 'SSL');
        $merchantID = $this->config->get('payment_cointopay_merchantID');
        $securityCode = $this->config->get('payment_cointopay_security_code');
        // order data
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://app.cointopay.com/MerchantAPI?Checkout=true',
        //CURLOPT_USERPWD => $this->apikey,
        CURLOPT_POSTFIELDS => 'SecurityCode='.$securityCode.'&MerchantID='.$merchantID.'&Amount=' . number_format($order_info['total'], 2, '.', '').'&AltCoinID=1&output=json&inputCurrency=USD&CustomerReferenceNr='.$order_info['order_id'].'&transactionconfirmurl='.$callbackUrl.'&transactionfailurl='.$callbackUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $params,
        CURLOPT_USERAGENT => 1,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
        )
        );
        $redirect = curl_exec($ch);
        $results = json_decode($redirect);
        if($results->RedirectURL)
        {
           //fn_create_payment_form($results->RedirectURL, '', 'Cointopay', false);
            header("Location: ".$results->RedirectURL."");
        }
        echo $redirect;
        exit;
    }
	public function callback() 
    {
        $data = array();
        $paymentStatus = isset($_GET['status']) ? $_GET['status'] : 'failed'; 
        $notEngough = isset($_GET['notenough']) ? $_GET['notenough'] : '2';
        $transactionID = isset($_GET['TransactionID']) ? $_GET['TransactionID'] : '';
        $orderID = isset($_GET['CustomerReferenceNr']) ? $_GET['CustomerReferenceNr'] : '';
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/cointopay_invoice');
		
        if(isset($_GET['ConfirmCode']))
        {
            $data = [ 
                        'mid' => $this->config->get('payment_cointopay_merchantID') , 
                        'TransactionID' =>  $transactionID ,
                        'ConfirmCode' => $_GET['ConfirmCode']
                    ];
            $response = $this->validateOrder($data);
            
            if($response->Status !== $_GET['status'])
            {
                echo "We have detected different order status. Your order has been halted.";
                exit;
            }
            if($response->CustomerReferenceNr == $_GET['CustomerReferenceNr'])
            {
                //if paid 
                if($paymentStatus == 'paid' && $notEngough == '0')
                {
                    $order_status = 5;
                    $comment = "Successfully paid!!";
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
                    $this->session->data['msg']='Your payment transaction has been completed';
                }
           
                else if ($paymentStatus == 'paid' || $notEngough == '1')
                {
                    $order_status = 15;
                    $comment = "Low balance!!";
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
                    $this->session->data['msg']= 'Your payment transaction has been processed but failed to pay(Low balance).';
                }
                elseif ($paymentStatus == 'failed')
                {
                    $order_status = 10;
                    $comment = "Transaction failed";
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
                    $this->session->data['msg']= 'Your payment transaction has been filed.';
                }
                else
                {
                    $order_status = 10;
                    $comment = "Transaction failed";
                    $this->model_checkout_order->addOrderHistory($orderID, $order_status, $comment);
                    $this->session->data['msg'] = 'Your payment transaction has been filed.';
                }
                echo '<html>' . "\n";
                echo '<head>' . "\n";
                echo '  <meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/cointopay') . '">' . "\n";
                echo '</head>' . "\n";
                echo '<body>' . "\n";
                echo '  <p>Please follow <a href="' . $this->url->link('checkout/cointopay') . '">link</a>!</p>' . "\n";
                echo '</body>' . "\n";
                echo '</html>' . "\n";
                exit();
            } 
            else
            {
                echo "We have detected changes in order info. Your order has been halted.";
                exit;
            }
        }
	}

    function  validateOrder($data)
    {
       
        https://cointopay.com/v2REAPI?MerchantID=14351&Call=QA&APIKey=_&output=json&TransactionID=230196&ConfirmCode=YGBMWCNW0QSJVSPQBCHWEMV7BGBOUIDQCXGUAXK6PUA
        $params = array( 
        "authentication:1",
        'cache-control: no-cache',
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
        CURLOPT_URL => 'https://app.cointopay.com/v2REAPI?',
        //CURLOPT_USERPWD => $this->apikey,
        CURLOPT_POSTFIELDS => 'MerchantID='.$data['mid'].'&Call=QA&APIKey=_&output=json&TransactionID='.$data['TransactionID'].'&ConfirmCode='.$data['ConfirmCode'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $params,
        CURLOPT_USERAGENT => 1,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
        )
        );
        $response = curl_exec($ch);
        $results = json_decode($response);
        if($results->CustomerReferenceNr)
        {
            return $results;
        }
        echo $response;
    }
    
}

