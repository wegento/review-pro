<?php

namespace WeGento\ReviewPro\Plugin\Adminhtml\Edit;

use Magento\Framework\Registry;
use WeGento\ReviewPro\Helper\Data;

class Form
{
    protected $helperData;

    protected $_coreRegistry;

    public function __construct(
        Data $helperData,
        Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        $this->helperData = $helperData;
    }

    public function beforeSetForm(\Magento\Review\Block\Adminhtml\Edit\Form $object, $form)
    {
        if (!$this->helperData->getEnable()) {
            return [$form];
        }

        $review = $this->_coreRegistry->registry('review_data');

        $fieldset = $form->addFieldset('review_details_extra', [
            'legend' => '',
            'class' => 'fieldset-wide',
        ]);

        $fieldset->addField('admin_replay', 'textarea', [
            'label' => 'Replay',
            'required' => false,
            'name' => 'admin_replay',
            'rows' => 15,
        ]);

        $form->setValues($review->getData());

        return [$form];
    }
}
