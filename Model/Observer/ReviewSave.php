<?php

namespace WeGento\ReviewPro\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Review\Model\Review\StatusFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use WeGento\ReviewPro\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\Store;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;

class ReviewSave implements ObserverInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_resource;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ScopeConfigInterface 0
     */
    protected $scopeConfig;
    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var StatusFactory
     */
    protected $_statusFactory;

    /** @var StateInterface */
    protected $inlineTranslation;

    /** @var Escaper */
    protected $escaper;

    /**
     * ReviewSave constructor.
     *
     * @param ResourceConnection    $resource
     * @param TransportBuilder      $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface  $scopeConfig
     * @param Data                  $helperData
     * @param CustomerFactory       $customerFactory
     * @param ManagerInterface      $messageManager
     * @param StatusFactory         $statusFactory
     */
    public function __construct(
        ResourceConnection $resource,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Data $helperData,
        CustomerFactory $customerFactory,
        ManagerInterface $messageManager,
        StatusFactory $statusFactory
    ) {
        $this->_resource = $resource;
        $this->transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->_helperData = $helperData;
        $this->_customerFactory = $customerFactory;
        $this->_messageManager = $messageManager;
        $this->_statusFactory = $statusFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
    }

    /**
     * @param Observer $observer
     *
     * @return Observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->_helperData->isModuleOutputEnabled()) {
            return $observer;
        }

        $review = $observer->getEvent()->getDataObject();

        try {
            $currentAreacode = ObjectManager::getInstance()->get('Magento\Framework\App\State')->getAreaCode();

            if ($currentAreacode == 'adminhtml') {
                $connection = $this->_resource;
                $tableName = $connection->getTableName('review_detail');
                $detail = [
                    'admin_replay' => $review->getAdminReplay(),
                ];
                $select = $connection->getConnection()->select()->from($tableName)->where('review_id = :review_id');
                $detailId = $connection->getConnection()->fetchOne($select, [':review_id' => $review->getId()]);

                if ($detailId) {
                    $condition = ['detail_id = ?' => $detailId];
                    $connection->getConnection()->update($tableName, $detail, $condition);
                } else {
                    $detail['store_id'] = $review->getStoreId();
                    $detail['customer_id'] = $review->getCustomerId();
                    $detail['review_id'] = $review->getId();
                    $connection->getConnection()->insert($tableName, $detail);
                }
            } else {
                $this->sendEmail($review);
            }

            $statusFact = $this->_statusFactory->create()->load($review->getStatusId());
        } catch (Exception $e) {
            $this->_messageManager->addErrorMessage($e->getMessage());
        }

        return $observer;
    }

    protected function sendEmail($review)
    {
        $emailAdmin = $this->_helperData->getEmailAdmin();

        if (empty($emailAdmin)){
            return;
        }

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('review_send')
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID,
                ])
                ->setTemplateVars([
                    'review' => $review,
                ])
                ->addTo($emailAdmin)
                ->setFromByScope('support')
                ->getTransport();

            $transport->sendMessage();
        } catch (Exception $e) {
        }
    }
}
