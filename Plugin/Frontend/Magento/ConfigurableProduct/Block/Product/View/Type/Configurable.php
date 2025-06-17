<?php

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Frontend\Magento\ConfigurableProduct\Block\Product\View\Type;

class Configurable
{

    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Data
     */
    protected $helper;

    /**
     * @param \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper
     */
    public function __construct(
        \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Modify JSON configuration to include subscription frequency.
     *
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject
     * @param string $result
     * @return false|string
     */
    public function afterGetJsonConfig(
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject,
        $result
    ) {
        // Decode the JSON to add custom data
        $config = json_decode($result, true);

        // Add subscription frequency for each product variation
        foreach ($subject->getAllowProducts() as $product) {
            $productId = $product->getId();
            $subscriptionFrequency = $this->helper->getLabel($product);
            $config['subscriptionFrequency'][$productId] = $subscriptionFrequency;
        }

        // Encode back to JSON
        return json_encode($config);
    }
}
