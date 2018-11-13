<?php
namespace Greenmoney\Greenmoney\Model;
use Magento\Framework\Exception\CouldNotSaveException;
class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement
{
	protected $catalogSession;
      public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
		\Magento\Catalog\Model\Session $catalogSession, 
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
    ) {
        parent::__construct($billingAddressManagement,$paymentMethodManagement,$cartManagement,$paymentDetailsFactory,$cartTotalsRepository);
		 $this->catalogSession = $catalogSession;
    }
	 public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
        try {
            $orderId = $this->cartManagement->placeOrder($cartId);
        } catch (\Exception $e) {
			if($this->catalogSession->getGreenMoneyError()){
				 throw new CouldNotSaveException(__($this->catalogSession->getGreenMoneyError()));
			}else{
					 throw new CouldNotSaveException(__('An error occurred on the server. Please try todfsfsdf place the order again11111.'),$e);
			}
        }
        return $orderId;
    }
}