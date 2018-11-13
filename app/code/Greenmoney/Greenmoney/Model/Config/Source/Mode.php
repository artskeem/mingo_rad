<?php

namespace Greenmoney\Greenmoney\Model\Config\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
 
        return [
            ['value' => 'https://cpSandbox.com/ecart.asmx', 'label' => __('Test')],
            ['value' => 'https://greenbyphone.com/ecart.asmx', 'label' => __('Live')],
        ];
    }
}
