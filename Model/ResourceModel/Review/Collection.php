<?php

namespace WeGento\ReviewPro\Model\ResourceModel\Review;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Helper\Data;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\EntityFactory;

class Collection extends \Magento\Review\Model\ResourceModel\Review\Collection
{
    protected $_reviewDetailTable;

    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Data $reviewData,
        VoteFactory $voteFactory,
        StoreManagerInterface $storeManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $reviewData,
            $voteFactory,
            $storeManager,
            $connection,
            $resource
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->join(
            ['details' => $this->getReviewDetailTable()],
            'main_table.review_id = details.review_id',
            ['detail_id', 'title', 'detail', 'nickname', 'customer_id', 'admin_replay']
        );

        return $this;
    }
}
