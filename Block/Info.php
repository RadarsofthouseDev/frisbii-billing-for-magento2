<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Block;

use Magento\Payment\Block\Info as MagentoPaymentInfo;

class Info extends MagentoPaymentInfo
{
    /**
     *  Override Magento\Payment\Block\Info
     *
     * @param null|\Magento\Framework\DataObject|array $transport
     * @return array|\Magento\Framework\DataObject|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        if ($additionalInformation = $this->getInfo()->getAdditionalInformation()) {
            if (isset($additionalInformation['raw_details_info']['source_type']) &&
                !empty($additionalInformation['raw_details_info']['source_type'])
            ) {
                $data['Type'] = $additionalInformation['raw_details_info']['source_type'];
            }
            if (isset($additionalInformation['raw_details_info']['source_card_type']) &&
                !empty($additionalInformation['raw_details_info']['source_card_type'])
            ) {
                $data['Card type'] = $additionalInformation['raw_details_info']['source_card_type'];
            }
            if (isset($additionalInformation['raw_details_info']['source_masked_card']) &&
                !empty($additionalInformation['raw_details_info']['source_masked_card'])
            ) {
                $data['Card'] = $additionalInformation['raw_details_info']['source_masked_card'];
            }
            if (isset($additionalInformation['raw_details_info']['source_exp_date']) &&
                !empty($additionalInformation['raw_details_info']['source_exp_date'])
            ) {
                $data['Exp date'] = $additionalInformation['raw_details_info']['source_exp_date'];
            }
            if (isset($additionalInformation['raw_details_info']['state']) &&
                !empty($additionalInformation['raw_details_info']['state'])
            ) {
                $data['State'] = $additionalInformation['raw_details_info']['state'];
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
