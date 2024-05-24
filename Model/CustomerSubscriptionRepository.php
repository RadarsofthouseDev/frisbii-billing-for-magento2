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
use Radarsofthouse\BillwerkPlusSubscription\Api\CustomerSubscriptionRepositoryInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterface;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Api\Data\CustomerSubscriptionSearchResultsInterfaceFactory;
use Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscription as ResourceCustomerSubscription;
use Radarsofthouse\BillwerkPlusSubscription\Model\ResourceModel\CustomerSubscription\CollectionFactory
    as CustomerSubscriptionCollectionFactory;

class CustomerSubscriptionRepository implements CustomerSubscriptionRepositoryInterface
{

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var CustomerSubscription
     */
    protected $searchResultsFactory;

    /**
     * @var CustomerSubscriptionCollectionFactory
     */
    protected $customerSubscriptionCollectionFactory;

    /**
     * @var ResourceCustomerSubscription
     */
    protected $resource;

    /**
     * @var CustomerSubscriptionInterfaceFactory
     */
    protected $customerSubscriptionFactory;

    /**
     * @param ResourceCustomerSubscription $resource
     * @param CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory
     * @param CustomerSubscriptionCollectionFactory $customerSubscriptionCollectionFactory
     * @param CustomerSubscriptionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceCustomerSubscription $resource,
        CustomerSubscriptionInterfaceFactory $customerSubscriptionFactory,
        CustomerSubscriptionCollectionFactory $customerSubscriptionCollectionFactory,
        CustomerSubscriptionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->customerSubscriptionFactory = $customerSubscriptionFactory;
        $this->customerSubscriptionCollectionFactory = $customerSubscriptionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        CustomerSubscriptionInterface $customerSubscription
    ) {
        try {
            $this->resource->save($customerSubscription);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the customerSubscription: %1',
                $exception->getMessage()
            ));
        }
        return $customerSubscription;
    }

    /**
     * @inheritDoc
     */
    public function get($entityId)
    {
        $customerSubscription = $this->customerSubscriptionFactory->create();
        $this->resource->load($customerSubscription, $entityId);
        if (!$customerSubscription->getId()) {
            throw new NoSuchEntityException(__('CustomerSubscription with id "%1" does not exist.', $entityId));
        }
        return $customerSubscription;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->customerSubscriptionCollectionFactory->create();

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
        CustomerSubscriptionInterface $customerSubscription
    ) {
        try {
            $customerSubscriptionModel = $this->customerSubscriptionFactory->create();
            $this->resource->load($customerSubscriptionModel, $customerSubscription->getEntityId());
            $this->resource->delete($customerSubscriptionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CustomerSubscription: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->get($entityId));
    }
}
