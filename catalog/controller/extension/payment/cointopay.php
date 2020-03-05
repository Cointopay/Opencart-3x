<?php

class ControllerExtensionPaymentCoinToPay extends Controller 
{
	public function index() 
    {
		$data['button_confirm'] = $this->language->get('button_confirm');
                
		$this->load->model('checkout/order');

        $order_info = "Session order_id is empty cannot proceed with your request";

		if (isset($this->session->data['order_id'])) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		}
		else
		{
			echo $order_info;
			return;
		}
		try {
	
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) 
        {
        
            $formData = $this->request->post;
			$currencyOutput = $this->getInputCurrencyList();
			if (null !== $this->config->get('payment_cointopay_merchantID')) {
				if (!in_array($this->config->get('config_currency'), $currencyOutput['currency'])) {
				echo 'Your Store currency '.$this->config->get('config_currency').' not supported. Please contact <a href="mailto:support@cointopay.com">support@cointopay.com</a> to resolve this issue.';exit();
				}
			}
			
            $url = trim($this->c2pCreateInvoice($this->request->post));
			if (is_string(json_decode($url))){
				echo json_decode($url);exit();
			}
			$url_components = parse_url(json_encode($url));
			if(isset($url_components['query'])){
				parse_str($url_components['query'], $params); 
				if ($params['MerchantID'] == 'null'){
					echo "Your API key did not result in a correct transaction order, please update your plugin api key";exit();
				}
			}
            /*$ch = curl_init($url);
            //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
			if (curl_errno($ch)) {
    echo $error_msg = curl_error($ch);die();
}
            curl_close($ch);*/
            $php_arr = json_decode($url);
			
			if(!isset($php_arr->TransactionID) || !isset($php_arr->QRCodeURL)){
				echo "Transaction not completed, please check your cointopay settings.";exit();
			}
            
            $data1 = array();    
            
            $this->load->language('extension/payment/cointopay_invoice');   
            
            if($php_arr->error == '' || empty($php_arr->error))
            {
                $this->model_checkout_order->addOrderHistory($php_arr->CustomerReferenceNr, $this->config->get('payment_cointopay_order_status_id'));
				
								//print_r($php_arr);
            
				$data1['TransactionID'] = $php_arr->TransactionID;
				$data1['AltCoinID'] = $php_arr->AltCoinID;
                $data1['coinAddress'] = $php_arr->coinAddress;
                $data1['Amount'] = $php_arr->Amount;
                $data1['CoinName'] = $php_arr->CoinName;
                $data1['QRCodeURL'] = $php_arr->QRCodeURL;
                $data1['RedirectURL'] = $php_arr->RedirectURL;
				$data1['ExpiryTime'] = $php_arr->ExpiryTime;
				$data1['CalExpiryTime'] = date("m/d/Y h:i:s T",strtotime($php_arr->ExpiryTime));
				$data1['OrderID'] = $this->session->data['order_id'];
				$data1['CustomerReferenceNr'] = $php_arr->CustomerReferenceNr;
				$data1['status'] = $php_arr->Status;
                $data1['text_title'] = $this->language->get('text_title');
                $data1['text_transaction_id'] = $this->language->get('text_transaction_id');
                $data1['text_address'] = $this->language->get('text_address');
                $data1['text_amount'] = $this->language->get('text_amount');
                $data1['text_coinname'] = $this->language->get('text_coinname');
				$data1['text_checkout_number'] = $this->language->get('text_checkout_number');
                $data1['text_expiry'] = $this->language->get('text_expiry');
                $data1['text_pay_with_other'] = $this->language->get('text_pay_with_other');
                $data1['text_clickhere'] = $this->language->get('text_clickhere');
            }
            else
            {
                $data1['error'] = $php_arr->error;
            }
            if (isset($this->session->data['order_id'])) 
            {
                $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$this->session->data['order_id'] . "' AND order_status_id > 0");

                if ($query->num_rows) 
                {
                    $this->cart->clear();

                    unset($this->session->data['shipping_method']);
                    unset($this->session->data['shipping_methods']);
                    unset($this->session->data['payment_method']);
                    unset($this->session->data['payment_methods']);
                    unset($this->session->data['guest']);
                    unset($this->session->data['comment']);
                    unset($this->session->data['order_id']);	
                    unset($this->session->data['coupon']);
                    unset($this->session->data['reward']);
                    unset($this->session->data['voucher']);
                    unset($this->session->data['vouchers']);
                }
            }
            $data1['footer'] = $this->load->controller('common/footer');
            $data1['header'] = $this->load->controller('common/header');
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_invoice')) 
            {
                $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_invoice', $data1));
            } 
            else 
            {
                $this->response->setOutput($this->load->view('extension/payment/cointopay_invoice', $data1));
            }
		}
        else
        {    
            $this->load->language('extension/payment/cointopay');    
            
            $data['action'] = $this->url->link('extension/payment/cointopay', '', true);

            $data['price'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $data['key'] = $this->config->get('payment_cointopay_api_key');
            $data['AltCoinID'] = $this->config->get('payment_cointopay_crypto_coin');
            $data['crypto_coins'] = $this->getMerchantCoins($this->config->get('payment_cointopay_merchantID'));
            $data['OrderID'] = $this->session->data['order_id'];
            $data['currency'] = $order_info['currency_code'];
            
            $data['text_crypto_coin_lable'] = $this->language->get('text_crypto_coin_lable');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay')) 
            {
                return $this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay', $data);
            } 
            else 
            {
                return $this->load->view('extension/payment/cointopay', $data);
            }
        }
        
        } catch (Exception $e) {
    			echo 'Caught exception: ',  $e->getMessage(), "\n";
    			return;
			}
	}

	public function callback() 
    {
        $data = array();
        $this->load->language('extension/payment/cointopay_invoice');
		$this->load->model('checkout/order');
        if(isset($_REQUEST['status']))
        {
			
            $data = [ 
                        'mid' => $this->config->get('payment_cointopay_merchantID') , 
                        'TransactionID' => $_GET['TransactionID'] ,
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
                
				
            
                if($_REQUEST['status'] == 'paid')
                {
					
                $this->model_checkout_order->addOrderHistory($_REQUEST['CustomerReferenceNr'], $this->config->get('payment_cointopay_callback_success_order_status_id','Successfully Paid'));
				$data['text_success'] = $this->language->get('text_success');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');
                
                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_success')) 
                {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_success', $data));
                } 
                else 
                {
                    $this->response->setOutput($this->load->view('extension/payment/cointopay_success', $data));
                }
                } 
                /*elseif($_GET['status'] == 'paid' AND  $_GET['notenough'] == '1')
                {
                    $statusProcessed = 15;
                  */  //$this->model_checkout_order->addOrderHistory($_GET['CustomerReferenceNr'], $statusProcessed,'Low Balanace');
               // }  
                elseif ($_REQUEST['status'] == 'failed') 
                {
                    $this->model_checkout_order->addOrderHistory($_REQUEST['CustomerReferenceNr'], $this->config->get('payment_cointopay_callback_failed_order_status_id','Transaction payment failed'));
                
                $data['text_failed'] = $this->language->get('text_failed');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');
                
                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_failed')) 
                {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_failed', $data));
                } 
                else 
                {
                    $this->response->setOutput($this->load->view('extension/payment/cointopay_failed', $data));
                }
				}
				elseif ($_REQUEST['status'] == 'expired') 
                {
                    $this->model_checkout_order->addOrderHistory($_REQUEST['CustomerReferenceNr'], $this->config->get('payment_cointopay_callback_failed_order_status_id','Transaction payment failed'));
                
                $data['text_failed'] = $this->language->get('text_expired');
                $data['footer'] = $this->load->controller('common/footer');
                $data['header'] = $this->load->controller('common/header');
                
                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_failed')) 
                {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_failed', $data));
                } 
                else 
                {
                    $this->response->setOutput($this->load->view('extension/payment/cointopay_failed', $data));
                }
				}
            }
            else
            {
                echo "We have detected changes in order status. Your order has been halted.";
                exit;
         }
        }
	}
        
    function c2pCreateInvoice($data) 
    {
		$merchantid = $this->config->get('payment_cointopay_merchantID');
		$payment_cointopay_securitycode = $this->config->get('payment_cointopay_securitycode');
        $response = $this->c2pCurl('SecurityCode='.$payment_cointopay_securitycode.'&MerchantID=' . $merchantid . '&Amount='.$data['price'].'&AltCoinID='.$data['AltCoinID'].'&inputCurrency=' . $data['currency'] . '&output=json&CustomerReferenceNr=' . $data['OrderID'] . '&returnurl=' . $this->url->link('extension/payment/cointopay/callback') .'&transactionconfirmurl='.$this->url->link('extension/payment/cointopay/callback').'&transactionfailurl='.$this->url->link('extension/payment/cointopay/callback'), $data['key']);        

       // $response = $this->c2pCurl('key='.$data['key'].'&price='.$data['price'].'&AltCoinID='.$data['AltCoinID'].'&OrderID='.$data['OrderID'].'&inputCurrency='.$data['currency'].'&transactionconfirmurl='.$this->url->link('extension/payment/cointopay/callback').'&output=json&transactionfailurl='.$this->url->link('extension/payment/cointopay/callback'), $data['key']);        
		return $response;
    }
    
    public function c2pCurl($data, $apiKey, $post = false) 
    {
        //$curl = curl_init($url);
        $length = 0;
        if ($post)
        {	
            $formData = $post;
            $formData['transactionconfirmurl'] = $this->url->link('extension/payment/cointopay/callback');
            $formData['transactionfailurl'] = $this->url->link('extension/payment/cointopay/callback');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $formData);
            $length = strlen($post);
        }
		

        $params = array(
			   "authentication:1",
			   'cache-control: no-cache',
			   );
		
				$ch = curl_init();
				curl_setopt_array($ch, array(
				//CURLOPT_URL => 'https://app.cointopay.com/REAPI',
				CURLOPT_URL => 'https://app.cointopay.com/MerchantAPI?Checkout=true',
				//CURLOPT_USERPWD => $this->apikey,
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPHEADER => $params,
				CURLOPT_USERAGENT => 1,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC
				)
				);
				$output = curl_exec($ch);
			
            //curl_close($ch);
			
            $php_arr = json_decode($output);

        if($output == false) {
               $response = curl_error($ch);
        } else {
              $response = $output;//json_decode($responseString, true);
        }
        curl_close($ch);
        return $response;
    }
        
    function getMerchantCoins($merchantId)
    {
        $url = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID='.$merchantId.'&output=json';
        $ch = curl_init($url);
        //print_r($ch);
        /*curl_setopt($ch, CURLOPT_RETURNTRANSFER, 3);
        $output = curl_exec($ch);
        curl_close($ch);*/


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $output=curl_exec($ch);
        curl_close($ch);

        $php_arr = json_decode($output);
        $new_php_arr = array();
        
        if(count($php_arr)>0)
        {
            for($i=0;$i<count($php_arr)-1;$i++)
            {
                if(($i%2)==0)
                {
                    $new_php_arr[$php_arr[$i+1]] = $php_arr[$i];
                }
            }
        }
        return $new_php_arr;
    }
	public function getCoinsPaymentUrl() 
    {
        $data = array();
        $this->load->language('extension/payment/cointopay_invoice');
        if(isset($_REQUEST['TransactionID']))
        {
			 $url = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID='.$this->config->get("payment_cointopay_merchantID").'&TransactionID='.$_REQUEST["TransactionID"].'&output=json';
        $ch = curl_init($url);


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $output=curl_exec($ch);
        curl_close($ch);
		$decoded = json_decode($output);
        echo $output;
		}
	}

    function  validateOrder($data)
    {
       //$this->pp($data);
       //https://cointopay.com/v2REAPI?MerchantID=14351&Call=QA&APIKey=_&output=json&TransactionID=230196&ConfirmCode=YGBMWCNW0QSJVSPQBCHWEMV7BGBOUIDQCXGUAXK6PUA
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
       // if($results->CustomerReferenceNr)
       // {
            return $results;
       // }
       // echo $response;
    }
	function getInputCurrencyList()
	{
		$merchantId = $this->config->get('payment_cointopay_merchantID');
	    $url = 'https://cointopay.com/v2REAPI?MerchantID='.$merchantId.'&Call=inputCurrencyList&output=json&APIKey=_';
	    $ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL,$url);
		$output=curl_exec($ch);
		
		curl_close($ch);

		$php_arr = json_decode($output);
		$new_php_arr = array();

		if(!empty($php_arr))
		{
			foreach($php_arr as $c)
			{
				if (array_key_exists('ShortName', $c)) {
				$new_php_arr['currency'][] = $c->ShortName;
				}
				
			}
		}
		
		return $new_php_arr;
	}
}

