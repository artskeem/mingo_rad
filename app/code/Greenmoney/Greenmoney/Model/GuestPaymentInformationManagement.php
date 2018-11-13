<?php
namespace Greenmoney\Greenmoney\Model;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement
{
	protected $catalogSession;
      public function __construct(
        \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\GuestCartManagementInterface $cartManagement,
        \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
		\Magento\Catalog\Model\Session $catalogSession, 
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct( $billingAddressManagement,$paymentMethodManagement, $cartManagement,$paymentInformationManagement,$quoteIdMaskFactory,$cartRepository);
		 $this->catalogSession = $catalogSession;
    }
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
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