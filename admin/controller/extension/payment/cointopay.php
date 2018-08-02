<?php
class ControllerextensionPaymentCoinToPay extends Controller 
{
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/cointopay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) 
		{
			$this->model_setting_setting->editSetting('payment_cointopay', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			if (isset($this->request->post['language_reload'])) 
			{
				$this->response->redirect($this->url->link('payment/cointopay', 'user_token=' . $this->session->data['user_token'], true));
			} 
			else 
			{
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']. '&type=payment', true));
			}
		}

		$data['heading_title'] = $this->language->get('heading_title');
        $data['entry_api_key'] = $this->language->get('entry_api_key');
        $data['entry_crypto_coin']	= $this->language->get('entry_crypto_coin');
       // $data['entry_redirect_url']	= $this->language->get('entry_redirect_url');
        $data['entry_status']		= $this->language->get('entry_status');
        $data['entry_merchantID']	= $this->language->get('entry_merchantID');
        $data['entry_display_name']	= $this->language->get('entry_display_name');
        $data['entry_order_status']	= $this->language->get('entry_order_status');
      
        
        $data['error_permission']= $this->language->get('error_permission');
        
        $data['button_save']    = $this->language->get('button_save');
		$data['button_cancel']  = $this->language->get('button_cancel');
        
        $data['help_api_key_hint']  = $this->language->get('help_api_key_hint');
        $data['help_crypto_coin_hint'] = $this->language->get('help_crypto_coin_hint');
        $data['help_redirect_url_hint'] = $this->language->get('help_redirect_url_hint');
        $data['help_display_name_hint'] = $this->language->get('help_display_name_hint');
        $data['help_merchantID_hint']   = $this->language->get('help_merchantID_hint');
        $data['tab_general'] = 'General';
        $data['token'] = $this->session->data['user_token'];
          
		if (isset($this->error['warning'])) 
		{
			$data['error_warning'] = $this->error['warning'];
		} 
		else 
		{
			$data['error_warning'] = '';
		}

        if (isset($this->error['api_key'])) 
        {
			$data['error_api_key'] = $this->error['api_key'];
		} 
		else 
		{
			$data['error_api_key'] = '';
		}
                
        if (isset($this->error['display_name'])) 
        {
			$data['error_display_name'] = $this->error['display_name'];
		} 
		else 
		{
			$data['error_display_name'] = '';
		}
                
        if (isset($this->error['merchantID'])) 
        {
			$data['error_merchantID'] = $this->error['merchantID'];
		} 
		else 
		{
			$data['error_merchantID'] = '';
		}
                
        if (isset($this->error['crypto_coin'])) 
        {
			$data['error_crypto_coin'] = $this->error['crypto_coin'];
		} 
		else 
		{
			$data['error_crypto_coin'] = '';
		}
                
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/cointopay', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);
                
		$data['action'] = $this->url->link('extension/payment/cointopay', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');
                
        if (isset($this->request->post['payment_cointopay_display'])) {
			$data['payment_cointopay_display'] = $this->request->post['payment_cointopay_display'];
		} else {
			$data['payment_cointopay_display'] = $this->config->get('payment_cointopay_display');
		}
                
		if (isset($this->request->post['payment_cointopay_api_key'])) {
			$data['payment_cointopay_api_key'] = $this->request->post['payment_cointopay_api_key'];
		} else {
			$data['payment_cointopay_api_key'] = $this->config->get('payment_cointopay_api_key');
		}

        if (isset($this->request->post['payment_cointopay_status'])) {
			$data['payment_cointopay_status'] = $this->request->post['payment_cointopay_status'];
		} else {
			$data['payment_cointopay_status'] = $this->config->get('payment_cointopay_status');// ? $this->config->get('cointopay_status') : '1';
		}
                
        if (isset($this->request->post['payment_cointopay_merchantID'])) {
			$data['payment_cointopay_merchantID'] = $this->request->post['payment_cointopay_merchantID'];
		} else {
			$data['payment_cointopay_merchantID'] = $this->config->get('payment_cointopay_merchantID');
                        
            $data['crypto_coins'] = $this->getMerchantCoins($this->config->get('payment_cointopay_merchantID'));              
		}
		 if (isset($this->request->post['payment_cointopay_security_code'])) {
			$data['payment_cointopay_security_code'] = $this->request->post['payment_cointopay_security_code'];
		} else {
			$data['payment_cointopay_security_code'] = $this->config->get('payment_cointopay_security_code');              
		}
                   
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/payment/cointopay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/cointopay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_cointopay_api_key']) {
			$this->error['api_key'] = $this->language->get('error_api_key');
		}
                
        if (!$this->request->post['payment_cointopay_display']) {
			$this->error['display_name'] = $this->language->get('error_display_name');
		}
                
                if (!$this->request->post['payment_cointopay_merchantID']) {
			$this->error['merchantID'] = $this->language->get('error_merchantID');
		}
                
                if (!$this->request->post['payment_cointopay_security_code']) {
			$this->error['cointopay_security_code'] = 'Security code required!!';
		}

		return !$this->error;
	}
        
        function getMerchantCoinsByAjax()
        {
            if($this->request->post['merchantId'])
            {
                $option = '<option value="">Select Default Coin</option>';
                $arr = $this->getMerchantCoins($this->request->post['merchantId']);
                foreach($arr as $key => $value)
                {
                    $option .= '<option value="'.$key.'">'.$value.'</option>';
                }
                //print_r($arr);
                echo $option;
            }
        }
        
        function getMerchantCoins($merchantId)
        {
            $url = 'https://cointopay.com/CloneMasterTransaction?MerchantID='.$merchantId.'&output=json';
            //$url = 'https://cointopay.com/CloneMasterTransaction?MerchantID=232&output=json';
           $ch = curl_init($url);
           //echo $ch;
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL,$url);
			$output=curl_exec($ch);
			//print_r($output);
			curl_close($ch);

            $php_arr = json_decode($output);
            $new_php_arr = array();
            
            if(count($php_arr)>1)
            {
                for($i=0;$i<count($php_arr)-1;$i++)
                {
                    if(($i%2)==0)
                    {
                        $new_php_arr[$php_arr[$i+1]] = $php_arr[$i];
                    }
                }
            }
            //print_r($php_arr);
            return $new_php_arr;
        }
}
