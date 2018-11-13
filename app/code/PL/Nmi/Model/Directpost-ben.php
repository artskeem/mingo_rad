<?php
/**
 * PL Development.
 *
 * @category    PL
 * @author      Linh Pham <plinh5@gmail.com>
 * @copyright   Copyright (c) 2016 PL Development. (http://www.polacin.com)
 */
namespace PL\Nmi\Model;

class Directpost extends \Magento\Payment\Model\Method\Cc
{
    const METHOD_CODE = 'nmi_directpost';

    const NMI_APPROVED = 1;

    const NMI_DECLINED = 2;

    const NMI_ERROR = 3;

    protected $_code = self::METHOD_CODE;

    protected $_isGateway = true;

    protected $_canCapture = true;

    protected $_canCapturePartial = false;

    protected $_canRefund = true;

    protected $_canVoid = false;

    protected $_minOrderTotal = 0;

    //protected $_supportedCurrencyCodes = array('USD', 'GBP', 'EUR');

    protected $plLogger;

    protected $encryptor;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \PL\Nmi\Logger\Logger $plLogger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
        $this->plLogger = $plLogger;
        $this->encryptor = $encryptor;
    }
    //added by lizzy 
	$limit = 6.50;
    public function getMerchantId()
    {
	    $grandTotal = $cart->getQuote()->getGrandTotal();
	    
		if ($grandTotal >= $limit){
			getMerchantId = '107209'
		} else {
        return $this->getConfigData('merchant_id');
        }
    }

    public function getApikey()
    {
	    if ($grandTotal >= $limit){
		    $apiKey = 'gq7av9es9c';
		    } else {
		  
        $apiKey = $this->getConfigData('api_secret_key');
        return $this->encryptor->decrypt($apiKey);
        }
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $errorMessage = false;
        try {
            $result = $this->_processRequest($payment, $amount, 'auth');
            if ($result['response'] == self::NMI_APPROVED) {
                $payment
                    ->setTransactionId($result['transactionid'])
                    ->setLastTransId($result['transactionid'])
                    ->setParentTransactionId($result['transactionid'])
                    ->setIsTransactionClosed(0);
            }
            if ($result['response'] == self::NMI_DECLINED) {
                $errorMessage = $result['responsetext'];
            }
            if ($result['response'] == self::NMI_ERROR) {
                $errorMessage = 'Error in transaction data or system error';
            }

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Payment error: %1', $e->getMessage()));
        }

        if ($errorMessage) {
            throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
        }
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $errorMessage = false;
        try {
            if ($payment->getParentTransactionId()) {
                $result = $this->_doCapture($payment->getParentTransactionId(), $amount);
                if ($result['response'] == self::NMI_APPROVED) {
                    $payment
                        ->setTransactionId($result['transactionid'])
                        ->setLastTransId($result['transactionid'])
                        ->setParentTransactionId($payment->getParentTransactionId())
                        ->setIsTransactionClosed(0);
                }
                if ($result['response'] == self::NMI_DECLINED) {
                    $errorMessage = $result['responsetext'];
                }
                if ($result['response'] == self::NMI_ERROR) {
                    $errorMessage = 'Error in transaction data or system error';
                }
            } else {
                $result = $this->_processRequest($payment, $amount);
                if ($result['response'] == self::NMI_APPROVED) {
                    $payment
                        ->setTransactionId($result['transactionid'])
                        ->setLastTransId($result['transactionid'])
                        ->setParentTransactionId($result['transactionid'])
                        ->setIsTransactionClosed(0);
                }
                if ($result['response'] == self::NMI_DECLINED) {
                    $errorMessage = $result['responsetext'];
                }
                if ($result['response'] == self::NMI_ERROR) {
                    $errorMessage = 'Error in transaction data or system error';
                }
            }

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Payment error: %1', $e->getMessage()));
        }

        if ($errorMessage) {
            throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
        }
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $errorMessage = false;
        try {
            if ($payment->getParentTransactionId()) {
                $result = $this->_doRefund($payment->getParentTransactionId(), $amount);
                if ($result['response'] == self::NMI_APPROVED) {
                    $payment
                        ->setTransactionId($result['transactionid'])
                        ->setLastTransId($result['transactionid'])
                        ->setParentTransactionId($payment->getParentTransactionId())
                        ->setIsTransactionClosed(1);
                }
                if ($result['response'] == self::NMI_DECLINED) {
                    $errorMessage = $result['responsetext'];
                }
                if ($result['response'] == self::NMI_ERROR) {
                    $errorMessage = 'Error in transaction data or system error';
                }
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Payment error: %1', $e->getMessage()));
        }
        if ($errorMessage) {
            throw new \Magento\Framework\Exception\LocalizedException(__($errorMessage));
        }
        return $this;
    }

    protected function _processRequest(\Magento\Payment\Model\InfoInterface $payment, $amount, $paymentType = 'sale')
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        if ($order->getIsVirtual()) {
            $shipping = $order->getBillingAddress();
        }

        $request = [];
    
		############### ARTSKEEM'S CODE ##################################
		
		// Added by Artskeem 9/6/18
		// added here so it could refund the right merch
		
		//$amt = $amount;
		//$grandTotal = $cart->getQuote()->getGrandTotal();
		
		
		$limit = 6.50;
		$the_limit = 6;		// number_format((float)$limit*100., 0, '.', '');
		$the_amount  = 15; 	// number_format((float)$amt*100., 0, '.', '');
		
		// Default Vantage API Keys 
		$the_usr = $this->getMerchantId();
		$the_api = $this->getApikey();
	
		
		// if over 600 use NMI Gateway API Keys
		if($the_amount > $the_limit){	
		
		 $the_usr  = '107209';
		 $the_api  = 'gq7av9es9c';	
			
		}
		
		$request['password'] = $the_usr; // $this->getMerchantId();
		$request['username'] = $the_api; // $this->getApikey();
		
		########## END ARTSKEEM'S CODE ##################################
		
        // Sales Information
        $request['ccnumber'] = $payment->getCcNumber();
        $request['ccexp'] = sprintf('%02d', $payment->getCcExpMonth()).substr($payment->getCcExpYear(), 2, 2);
        $request['amount'] = $amount;
        $request['cvv'] = $payment->getCcCid();
        // Order Information
        //$request['ipaddress'] = '';
        $request['orderid'] = $order->getIncrementId();
        $request['orderdescription'] = __('Order #%1', $order->getIncrementId());

        // Billing Information
        $request['firstname'] = $billing->getFirstname();
        $request['lastname'] = $billing->getLastname();
        $request['company'] = $billing->getCompany();
        $request['address1'] =  $billing->getStreetLine(1);
        $request['address2'] = $billing->getStreetLine(2);
        $request['city'] = $billing->getCity();
        $request['state'] = $billing->getRegion();
        $request['zip'] = $billing->getPostcode();
        $request['country'] = $billing->getCountryId();
        $request['phone'] = $billing->getTelephone();
		$request['email'] = $order->getCustomerEmail();
        // Shipping Information
        $request['shipping_firstname'] = $shipping->getFirstname();
        $request['shipping_lastname'] = $shipping->getLastname();
        $request['shipping_company'] = $shipping->getCompany();
        $request['shipping_address1'] = $shipping->getStreetLine(1);
        $request['shipping_address2'] = $shipping->getStreetLine(2);
        $request['shipping_city'] = $shipping->getCity();
        $request['shipping_state'] = $shipping->getRegion();
        $request['shipping_zip'] = $shipping->getPostcode();
        $request['shipping_country'] = $shipping->getCountryId();

        $request['type'] = $paymentType;

        $postRequestData = '';
        $amp = '';
        foreach ($request as $key => $value) {
            if (!empty($value)) {
                $postRequestData .= $amp . urlencode($key) . '=' . urlencode($value);
                $amp = '&';
            }
        }
        $responses = $this->_doPost($postRequestData);
        return $responses;
    }

    protected function _doCapture($transactionId, $amount)
    {
        $request = [];
		
		
	 
		############### ARTSKEEM'S CODE ##################################
		
		// Added by Artskeem 9/6/18
		// added here so it could refund the right merch
		
		//$amt = $amount;
		//$grandTotal = $cart->getQuote()->getGrandTotal();
		
		
		$limit = 6.50;
		$the_limit = 6;		// number_format((float)$limit*100., 0, '.', '');
		$the_amount  = 15; 	// number_format((float)$amt*100., 0, '.', '');
		
		// Default Vantage API Keys 
		$the_usr = $this->getMerchantId();
		$the_api = $this->getApikey();
	
		
		// if over 600 use NMI Gateway API Keys
		if($the_amount > $the_limit){	
		
		 $the_usr  = '107209';
		 $the_api  = 'gq7av9es9c';	
			
		}
		
		$request['password'] = $the_usr; // $this->getMerchantId();
		$request['username'] = $the_api; // $this->getApikey();
		
		########## END ARTSKEEM'S CODE ##################################
		 		

		
		
		########## START DEBUGGY CODE #####################################
		
		// log debug for artskeem code drop text file on the root server
		// called totals.txt to check to make sure the vars are being 
		// compared properly in the if statments above.
		
		$debuggy = fopen("totals.txt", "w") or die("Unable to open file!");
			 
			$txt = "Amount:".$the_amount.">".$the_limit."\n";
			fwrite($debuggy, $txt);
			fclose($debuggy);
		
		########## END DEBUGGY CODE #######################################
		
 
		

		
        $request['transactionid'] =  $transactionId;
        $request['amount'] = $amount;
        $request['type'] = 'capture';
        $postRequestData = '';
        $amp = '';
        foreach ($request as $key => $value) {
            if (!empty($value)) {
                $postRequestData .= $amp . urlencode($key) . '=' . urlencode($value);
                $amp = '&';
            }
        }
        $responses = $this->_doPost($postRequestData);
        return $responses;
    }

    protected function _doRefund($transactionId, $amount)
    {
		 $request = [];
		
		
		
		############### ARTSKEEM'S CODE ##################################
		
		// Added by Artskeem 9/6/18
		// added here so it could refund the right merch
		
		//$amt = $amount;
		//$grandTotal = $cart->getQuote()->getGrandTotal();
		
		
		$limit = 6.50;
		$the_limit = 6;		// number_format((float)$limit*100., 0, '.', '');
		$the_amount  = 15; 	// number_format((float)$amt*100., 0, '.', '');
		
		// Default Vantage API Keys 
		$the_usr = $this->getMerchantId();
		$the_api = $this->getApikey();
	
		
		// if over 600 use NMI Gateway API Keys
		if($the_amount > $the_limit){	
		
		 $the_usr  = '107209';
		 $the_api  = 'gq7av9es9c';	
			
		}
		
		$request['password'] = $the_usr; // $this->getMerchantId();
		$request['username'] = $the_api; // $this->getApikey();
		
		########## END ARTSKEEM'S CODE ##################################
		
		
		
		
       
        $request['transactionid'] =  $transactionId;
        $request['amount'] = $amount;
        $request['type'] = 'refund';
        $postRequestData = '';
        $amp = '';
        foreach ($request as $key => $value) {
            if (!empty($value)) {
                $postRequestData .= $amp . urlencode($key) . '=' . urlencode($value);
                $amp = '&';
            }
        }
        $responses = $this->_doPost($postRequestData);
        return $responses;
    }

    protected function _doPost($postRequestData){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://secure.networkmerchants.com/api/transact.php");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postRequestData);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!($data = curl_exec($ch))) {
            return self::NMI_ERROR;
        }
        curl_close($ch);
        unset($ch);
        $data = explode("&",$data);
        for($i=0;$i<count($data);$i++) {
            $rdata = explode("=",$data[$i]);
            $responses[$rdata[0]] = $rdata[1];
        }

        if ($this->getConfigData('debug')) {
            $this->plLogger->debug(print_r($responses, 1));
        }
        return $responses;
    }
}
