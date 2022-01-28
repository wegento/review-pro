<?php

namespace WeGento\ReviewPro\Model\ResourceModel\Review\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\Store;
use Zend_Db_Expr;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Review\Model\Rating\Option\VoteFactory;
use Magento\Review\Model\RatingFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Indexer\Product\Flat\State;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Catalog\Model\ResourceModel\Helper;
use Magento\Eav\Model\EntityFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Review\Model\ResourceModel\Review\Product\Collection
{
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        ResourceConnection $resource,
        EntityFactory $eavEntityFactory,
        Helper $resourceHelper,
        UniversalFactory $universalFactory,
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        State $catalogProductFlatState,
        ScopeConfigInterface $scopeConfig,
        OptionFactory $productOptionFactory,
        Url $catalogUrl,
        TimezoneInterface $localeDate,
        Session $customerSession,
        DateTime $dateTime,
        GroupManagementInterface $groupManagement,
        RatingFactory $ratingFactory,
        VoteFactory $voteFactory,
        AdapterInterface $connection = null,
        ProductLimitationFactory $productLimitationFactory = null,
        MetadataPool $metadataPool = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $ratingFactory,
            $voteFactory,
            $connection,
            $productLimitationFactory,
            $metadataPool
        );
    }

    protected function _joinFields()
    {
        $reviewTable = $this->_resource->getTableName('review');
        $reviewDetailTable = $this->_resource->getTableName('review_detail');

        $this->addAttributeToSelect('name')->addAttributeToSelect('sku');

        $this->getSelect()->join(
            ['rt' => $reviewTable],
            'rt.entity_pk_value = e.entity_id',
            ['rt.review_id', 'review_created_at' => 'rt.created_at', 'rt.entity_pk_value', 'rt.status_id']
        )->join(
            ['rdt' => $reviewDetailTable],
            'rdt.review_id = rt.review_id',
            ['rdt.title', 'rdt.nickname', 'rdt.detail', 'rdt.customer_id', 'rdt.store_id', 'rdt.admin_Replay']
        );

        return $this;
    }

    public function setOrder($attribute, $dir = 'DESC')
    {
        switch ($attribute) {
            case 'rt.review_id':
            case 'rt.created_at':
            case 'rt.status_id':
            case 'rdt.title':
            case 'rdt.nickname':
            case 'rdt.detail':
            case 'rdt.admin_Replay':
                $this->getSelect()->order($attribute . ' ' . $dir);
                break;
            case 'stores':
                // No way to sort
                break;
            case 'type':
                $this->getSelect()->order('rdt.customer_id ' . $dir);
                break;
            default:
                parent::setOrder($attribute, $dir);
                break;
        }
        return $this;
    }

    public function addAttributeToFilter($attribute, $condition = null, $joinType = 'inner')
    {
        switch ($attribute) {
            case 'rt.review_id':
            case 'rt.created_at':
            case 'rt.status_id':
            case 'rdt.title':
            case 'rdt.nickname':
            case 'rdt.detail':
            case 'rdt.admin_Replay':
                $conditionSql = $this->_getConditionSql($attribute, $condition);
                $this->getSelect()->where($conditionSql);
                break;
            case 'stores':
                $this->setStoreFilter($condition);
                break;
            case 'type':
                if ($condition == 1) {
                    $conditionParts = [
                        $this->_getConditionSql('rdt.customer_id', ['is' => new Zend_Db_Expr('NULL')]),
                        $this->_getConditionSql(
                            'rdt.store_id',
                            ['eq' => Store::DEFAULT_STORE_ID]
                        ),
                    ];
                    $conditionSql = implode(' AND ', $conditionParts);
                } elseif ($condition == 2) {
                    $conditionSql = $this->_getConditionSql('rdt.customer_id', ['gt' => 0]);
                } else {
                    $conditionParts = [
                        $this->_getConditionSql('rdt.customer_id', ['is' => new Zend_Db_Expr('NULL')]),
                        $this->_getConditionSql(
                            'rdt.store_id',
                            ['neq' => Store::DEFAULT_STORE_ID]
                        ),
                    ];
                    $conditionSql = implode(' AND ', $conditionParts);
                }
                $this->getSelect()->where($conditionSql);
                break;

            default:
                parent::addAttributeToFilter($attribute, $condition, $joinType);
                break;
        }

        return $this;
    }
}
