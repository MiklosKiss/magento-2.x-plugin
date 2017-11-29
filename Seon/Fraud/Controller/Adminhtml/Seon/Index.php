<?php

namespace Magefox\Example\Controller\Adminhtml\Seon;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Check if user has enough privileges
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Seon_Fraud::seon');
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_setActiveMenu('Seon_Fraud::seon');
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Foxy Admin Page'));

        $this->_addBreadcrumb(__('Stores'), __('Stores'));
        $this->_addBreadcrumb(__('Foxy Admin Page'), __('Foxy Admin Page'));

        $this->_view->renderLayout();
    }
}
?>
  