<?php
/**
 * PL Development.
 *
 * @category    PL
 * @author      Linh Pham <plinh5@gmail.com>
 * @copyright   Copyright (c) 2016 PL Development. (http://www.polacin.com)
 */
namespace PL\Nmi\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \PL\Nmi\Logger\Logger
     */
    protected $plLogger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \PL\Nmi\Logger\Logger $plLogger
    ) {
        parent::__construct($context);
        $this->plLogger = $plLogger;
    }

    /**
     * @param $text
     * @return \Magento\Framework\Phrase
     */
    public function wrapGatewayError($text)
    {
        return __('Gateway error: %1', $text);
    }
}
