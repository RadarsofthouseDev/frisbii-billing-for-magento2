<?php
/**
 * Copyright © radarsofthouse.dk All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Radarsofthouse\BillwerkPlusSubscription\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriberRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriberSearchResultsInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscriber as ResourceCustomerSubscriber;
use Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscriber\CollectionFactory
    as CustomerSubscriberCollectionFactory;

class CustomerSubscriberRepository implements CustomerSubscriberRepositoryInterface
{

    /**
     * @var CustomerSubscriberInterfaceFactory
     */
    protected $customerSubscriberFactory;

    /**
     * @var CustomerSubscriber
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var CustomerSubscriberCollectionFactory
     */
    protected $customerSubscriberCollectionFactory;

    /**
     * @var ResourceCustomerSubscriber
     */
    protected $resource;

    /**
     * @param ResourceCustomerSubscriber $resource
     * @param CustomerSubscriberInterfaceFactory $customerSubscriberFactory
     * @param CustomerSubscriberCollectionFactory $customerSubscriberCollectionFactory
     * @param CustomerSubscriberSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceCustomerSubscriber $resource,
        CustomerSubscriberInterfaceFactory $customerSubscriberFactory,
        CustomerSubscriberCollectionFactory $customerSubscriberCollectionFactory,
        CustomerSubscriberSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->customerSubscriberFactory = $customerSubscriberFactory;
        $this->customerSubscriberCollectionFactory = $customerSubscriberCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        CustomerSubscriberInterface $customerSubscriber
    ) {
        try {
            $this->resource->save($customerSubscriber);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the customerSubscriber: %1',
                $exception->getMessage()
            ));
        }
        return $customerSubscriber;
    }

    /**
     * @inheritDoc
     */
    public function get($customerId)
    {
        $customerSubscriber = $this->customerSubscriberFactory->create();
        $this->resource->load($customerSubscriber, $customerId);
        if (!$customerSubscriber->getId()) {
            throw new NoSuchEntityException(__('CustomerSubscriber with id "%1" does not exist.', $customerId));
        }
        return $customerSubscriber;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->customerSubscriberCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        CustomerSubscriberInterface $customerSubscriber
    ) {
        try {
            $customerSubscriberModel = $this->customerSubscriberFactory->create();
            $this->resource->load($customerSubscriberModel, $customerSubscriber->getCustomerId());
            $this->resource->delete($customerSubscriberModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CustomerSubscriber: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($customereId)
    {
        return $this->delete($this->get($customereId));
    }
}
