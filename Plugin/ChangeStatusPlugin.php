<?php

namespace WeGento\ReviewPro\Plugin;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Review\Controller\Adminhtml\Product\MassUpdateStatus;
use WeGento\ReviewPro\Helper\Data;

class ChangeStatusPlugin
{
    private  $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param MassUpdateStatus $subject
     * @return array
     */
    public function beforeExecute(MassUpdateStatus $subject)
    {
        if($this->helper->getSendStatusEnable()){
            $reviews=$subject->getRequest()->getParam('reviews');
            $status=$subject->getRequest()->getParam('status');
            $statuses=[1=>__('Approved'),2=>__('Pending'),3=>__('Not Approved')];

            $this->helper->sendCustomerEmail($reviews,$statuses[$status]);
        }
        return [];
    }
}
