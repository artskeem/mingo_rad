<?php
namespace Greenmoney\Greenmoney\Model;
class Greenmoney extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'greenmoney';
	protected $_formBlockType = 'Greenmoney\Greenmoney\Block\Form\Greenmoney';
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';
	protected $_isGateway                   = true;
    protected $_canAuthorize				= true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
	protected $_canUseInternal				= true;
    protected $_canUseCheckout 				= true;
	protected $_isInitializeNeeded			= false;
	public $_parse;
	public $scopeConfig;
	public $logger;
	protected $catalogSession;
	public function __construct( \Magento\Framework\Model\Context $context,
	\Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Catalog\Model\Session $CatalogSession, 
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Xml\Parser  $parse,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig){
			parent::__construct($context, $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger);
			$this->_parse =  $parse;
			$this->scopeConfig = $scopeConfig;
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/greenmoney.log');
			$this->logger = new \Zend\Log\Logger();
			$this->logger->addWriter($writer);
			 $this->catalogSession = $CatalogSession;
			
		}
		
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
    }
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
		$this->logger->info('return from payment function');
	   if ($amount <= 0) {
		   $this->catalogSession->setGreenMoneyError('Invalid amount for refund.');
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for refund.'));
        }
        if (!$payment->getParentTransactionId()){
			 $this->catalogSession->setGreenMoneyError('Invalid transaction ID.');
				throw new \Magento\Framework\Exception\LocalizedException(__('Invalid transaction ID.'));
        }
		$order = $payment->getOrder();
		$query_data = $this->buildRefundRequest($payment,$amount);
		$this->logger->info($query_data);
		$Response = $this->payment_Refound_gateway($query_data);
		$this->logger->info('return from payment function');
		if(isset($Response['Result'])){
			$this->logger->info('in response');
			if($Response['Result'] == '0' && $Response['ResultDescription']){
				$this->logger->info('in resfsdfsdfsdponse');
				$payment->setIsTransactionClosed(true);
				$order->addStatusHistoryComment($Response['ResultDescription']);
				if($Response['Result'] > 0){
					$order->addStatusHistoryComment('RefundCheckNumber :'.$Response['RefundCheckNumber']);
					$order->addStatusHistoryComment('RefundCheck_ID :'.$Response['RefundCheck_ID']);
				}
				$order->save();
			}
			else{
				$order->addStatusHistoryComment($Response['ResultDescription']);
				$order->save();
			}
		}
		else{
				$this->catalogSession->setGreenMoneyError('Invalid Response.........!.');
				throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Response.........!'));
			}
		
	}
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
		if ($amount <= 0) {
			$this->catalogSession->setGreenMoneyError('Invalid amount for capture.');
				throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for capture.'));
		}
		$payment->setAmount($amount);
		$order = $payment->getOrder();
        $billing = $order->getBillingAddress();
		$query_data = $this->buildRequest($payment);
		$this->logger->info($query_data);
		$Response = $this->payment_gateway($query_data);
		$this->logger->info('return from payment function');
		if(isset($Response['Result'])){
			$this->logger->info('in response');
			if($Response['Result'] == '0' && $Response['ResultDescription']){
				$this->logger->info('in resfsdfsdfsdponse');
				$payment->setTransactionId($Response['CheckNumber'].'-'.$Response['Check_ID']);
				$payment->setIsTransactionClosed(0);
				$payment->setTransactionAdditionalInfo('Check_ID',$Response['Check_ID']);
				$payment->setTransactionAdditionalInfo('CheckNumber',$Response['Check_ID']);
				$order->addStatusHistoryComment('Check ID :'.$Response['Check_ID']);
				$order->addStatusHistoryComment('CheckNumber :'.$Response['Check_ID']);
				$order->save();
			}
			else{
				$this->logger->info('in ressdasdasdasdasdfsdfsdfsdponse');
				$this->catalogSession->setGreenMoneyError($Response['ResultDescription']);
				throw new \Magento\Framework\Exception\LocalizedException(__($Response['ResultDescription']));
			}
		}
		else{
				$this->catalogSession->setGreenMoneyError('Invalid Response.........!');
				throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Response.........!'));
			}
    }
	public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
		$additional_data = $data->getAdditional_data();
        $infoInstance->setAdditionalInformation('accountnumber', $additional_data['accountnumber']);
        $infoInstance->setAdditionalInformation('routingnumber', $additional_data['routingnumber']);
        return $this;
    }
	protected function payment_gateway($query_data){
		$Apiurl =  $this->scopeConfig->getValue('payment/greenmoney/mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$init = $Apiurl.'/CartCheck';
		$this->logger->info($init);
		 try {
			$this->logger->info('Send Data TO api......');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$init);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$query_data);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$result = curl_exec($ch);
			curl_close($ch);
			$xml = @simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$this->logger->info('Return Data TO api......');
			$this->logger->info($result);
			$this->logger->info($json);
			return json_decode($json,TRUE);
			
			} catch (\Exception $e) {
			$this->catalogSession->setGreenMoneyError('Invalid Response.........!');
			throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Response.........!'));
		}
	}
	protected function payment_Refound_gateway($query_data){
		$Apiurl =  $this->scopeConfig->getValue('payment/greenmoney/mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$init = $Apiurl.'/CartCheckRefund ';
		$this->logger->info($init);
		 try {
			$this->logger->info('Send Data TO api......');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$init);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$query_data);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$result = curl_exec($ch);
			curl_close($ch);
			$xml = @simplexml_load_string($result, "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$this->logger->info('Return Data TO api......');
			$this->logger->info($result);
			$this->logger->info($json);
			return json_decode($json,TRUE);
			
			} catch (\Exception $e) {
				$this->catalogSession->setGreenMoneyError('Invalid Response.........!');
			throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Response.........!'));
		}
	}
	protected function buildRequest($payment){
		$order = $payment->getOrder();
        $billing = $order->getBillingAddress();
			$data = '';
			$data.='Client_ID='.$this->scopeConfig->getValue('payment/greenmoney/Client_ID', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'&';
			$data.='ApiPassword='. $this->scopeConfig->getValue('payment/greenmoney/ApiPassword', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'&';
			$data.='Name='.$billing->getName().'&';
			$data.='EmailAddress='. $billing->getEmail().'&';
			$data.='Phone='.  $billing->getTelephone().'&';
			$data.='PhoneExtension=&';
			$data.='Address1='. $billing->getStreetLine(1).'&';
			$data.='Address2=&';
			$data.='City='.$billing->getCity().'&';
			$data.='State='. $billing->getRegion().'&';
			$data.='Zip='. $billing->getPostcode().'&';
			$data.='Country='. $billing->getCountry_id().'&';
			$data.='AccountNumber='.$payment->getAdditionalInformation()['accountnumber'].'&';
			$data.='RoutingNumber='.$payment->getAdditionalInformation()['routingnumber'].'&';
			$data.='CheckMemo=&';
			$data.='CheckAmount='. $payment->getAmount() .'&';
			$data.='CheckDate='.date('m/d/Y');
			return $data;
		}
	protected function buildRefundRequest($payment,$amount){
			$tx = $payment->getParentTransactionId();
			$tx = explode("-",$tx);
			$data = '';
			$data.='Client_ID='.$this->scopeConfig->getValue('payment/greenmoney/Client_ID', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'&';
			$data.='ApiPassword='. $this->scopeConfig->getValue('payment/greenmoney/ApiPassword', \Magento\Store\Model\ScopeInterface::SCOPE_STORE).'&';
			$this->logger->info($tx [1]);
			$data.='Check_ID='.$tx [1].'&';
			$data.='RefundMemo=&';
			$data.='RefundAmount='. $amount.'&';
			return $data;
		}
}
