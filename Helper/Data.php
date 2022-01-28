<?php

namespace WeGento\ReviewPro\Helper;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class Data extends AbstractHelper
{
    const XML_REPLAY_REVIEW_GROUP = 'replay_review_section/replay_review_config/';

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;


    public   $customerRepository;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        TransportBuilder $transportBuilder,
        CollectionFactory $collectionFactory,
        CustomerRepository $customerRepository
    ) {
        $this->_customerSession = $customerSession;
        $this->transportBuilder=$transportBuilder;
        $this->collectionFactory=$collectionFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }
    /**
     * @return Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if ($this->getEnable()) {
            return 'WeGento_ReviewPro::product/view/ajax.phtml';
        }

        return 'Magento_Review::product/view/list.phtml';
    }

    public function getCustomerReviewTemplate()
    {
        if ($this->getEnable()) {
            return 'WeGento_ReviewPro::customer/view.phtml';
        }

        return 'Magento_Review::customer/view.phtml';
    }

    private function getConfigValue($code, $storeId = null)
    {
        return $this->scopeConfig->getValue(
             static::XML_REPLAY_REVIEW_GROUP. $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @return string
     */
    public function getEmailAdmin()
    {
        return $this->getConfigValue('admin_email');
    }
    /**
     * @return bool
     */
    public function getSendStatusEnable()
    {
        return (bool)$this->getConfigValue('customer_status');
    }
    /**
     * @return bool
     */
    public function getSendReplayEnable()
    {
        return (bool)$this->getConfigValue('customer_notify_replay');
    }
    /**
     * @return bool
     */
    public function getEnable()
    {
        return (bool)$this->getConfigValue('enable');
    }

    public function sendCustomerEmail($review_id,$status=null)
    {
        if(!is_array($review_id)){
            $review_id=[$review_id];
        }
        $reviews= $this->getReviewCollection($review_id);
        /**
         * @var $review \Magento\Review\Model\Review;
         */

        foreach ($reviews as $review) {
            $customer_id=$review->getCustomerId();
            if($customer_id){
              $customer=$this->customerRepository->getById($customer_id);
              $review->setData('status',$status);
              $this->sendEmail(['review'=>$review],$customer->getEmail(),'customer_review_status');
            }
        }

    }


    private function sendEmail(array $data,$to,$templateId)
    {

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DEFAULT_STORE_ID,
                ])
                ->setTemplateVars(
                    $data
                )
                ->addTo($to)
                ->setFromByScope('support')
                ->getTransport();

          $transport->sendMessage();
         } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
    /**
     * Returns requested collection.
     *
     * @return Collection
     */
    private function getReviewCollection(array $reviews)
    {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter(
                'main_table.' . $collection->getResource()
                    ->getIdFieldName(),
                $reviews
             );

            $this->collection = $collection;


        return $this->collection;
    }
}
