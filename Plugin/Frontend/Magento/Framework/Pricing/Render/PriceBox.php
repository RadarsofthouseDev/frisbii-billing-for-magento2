<?php

declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Plugin\Frontend\Magento\Framework\Pricing\Render;

use Magento\Catalog\Model\Product;

class PriceBox
{
    /**
     * @var \Radarsofthouse\BillwerkPlusSubscription\Helper\Data
     */
    private $helper;

    /**
     * Constructor.
     *
     * @param \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper
     */
    public function __construct(
        \Radarsofthouse\BillwerkPlusSubscription\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * After render amount plugin to append subscription plan label.
     *
     * @param \Magento\Framework\Pricing\Render\PriceBox $subject
     * @param string $result
     * @return string
     */
    public function afterRenderAmount(
        \Magento\Framework\Pricing\Render\PriceBox $subject,
        $result
    ) {

        // Get the current product using getSaleableItem()
        $product = $subject->getSaleableItem();

        // Handle configurable product
        if ($product instanceof Product && $product->getTypeId() === 'configurable') {
            try {
                // Get all associated simple products (variations)
                $children = $product->getTypeInstance()->getUsedProducts($product);
                $lowestPriceWithLabel = null;
                $lowestPricePlanLabel = '';
                $absoluteLowestPrice = null;
                foreach ($children as $child) {
                    $childPrice = (float) $child->getPrice();
                    $planLabel = $this->helper->getLabel($child);
                    $isEnabled = $child->getData('billwerk_sub_enabled');

                    // Track the absolute lowest price (whether or not it has a label)
                    if ($absoluteLowestPrice === null || $childPrice < $absoluteLowestPrice) {
                        $absoluteLowestPrice = $childPrice;
                    }

                    // Track the lowest price with a valid label
                    if ($isEnabled && $planLabel) {
                        if ($lowestPriceWithLabel === null || $childPrice < $lowestPriceWithLabel) {
                            $lowestPriceWithLabel = $childPrice;
                            $lowestPricePlanLabel = $planLabel;
                        }
                    }
                }

                // If we found a valid subscription plan, append it to the price box
                if ($lowestPricePlanLabel && $lowestPriceWithLabel === $absoluteLowestPrice) {
                    return $result . '<span class="subscription-frequency-label-lower-price">'
                        . $lowestPricePlanLabel . '</span>';
                }

                // If no valid label, just return the result (price only)
            } catch (\Exception $e) {
                return $result;
            }
        }

        // Handle simple/virtual product directly
        if ($product instanceof Product) {
            $planLabel = $this->helper->getLabel($product);
            if ($planLabel) {
                return $result . '<span class="subscription-frequency-label">' . $planLabel . '</span>';
            }
        }

        return $result;
    }
}
