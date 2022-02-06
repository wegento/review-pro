<?php

namespace WeGento\ReviewPro\Plugin\Adminhtml\Add;

use WeGento\ReviewPro\Helper\Data;

class Form
{
    protected $helperData;

    public function __construct(Data $helperData)
    {
        $this->helperData = $helperData;
    }

    public function beforeSetForm(\Magento\Review\Block\Adminhtml\Add\Form $object, $form)
    {
        if (!$this->helperData->getEnable()) {
            return [$form];
        }

        $fieldset = $form->addFieldset('review_details_extra', [
            'legend' => '',
            'class' => 'fieldset-wide',
        ]);

        $fieldset->addField('admin_replay', 'textarea', [
            'label' => __('Admin Replay'),
            'required' => false,
            'name' => 'admin_Replay',
            'rows' => 15,
        ]);

        return [$form];
    }
}
