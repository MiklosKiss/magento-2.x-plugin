<?php

namespace Seon\Fraud\Observer;

use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

class OrderObserver implements ObserverInterface {

    protected $order;
    protected $_logger;
    protected $_helper;

    public function __construct(
    \Magento\Sales\Model\Order $order, \Psr\Log\LoggerInterface $customLogger, \Seon\Fraud\Helper\Data $dataHelper
    ) {
        $this->order = $order;
        $this->_logger = $customLogger;
        $this->_helper = $dataHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        if (!$this->_helper->isEnabled()) {
            $this->_logger->addDebug('Seon API Modul is not enabled');
            return $this;
        } else {
            $this->_logger->addDebug('Seon API Modul is enabled');
        }

        $order = $observer->getEvent()->getOrder();

        $firstname = $order->getCustomerFirstname();
        $lastname = $order->getCustomerMiddlename();

        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();

        $paymentMethod = $payment->getMethodInstance()->getCode();

        try {
            $data = $this->_helper->getPurchase($order);
            $url = $this->_helper->getApiUrl();
            $this->_logger->addDebug($url);
            $response = $this->_helper->request($url, $data, 'application/json');

            $result = json_decode($response);

            if ($result->success) {

                $order->setData('seon_transaction_id', $result->data->id);
                $order->setData('proxy_score', $result->data->proxy_score);
                $order->setData('is_fraud', $result->data->state);
                $order->setData('fraud_score', $result->data->fraud_score);

                if ($result->data->state != 'APPROVE') {
                    $order->hold();
                    $order->addStatusHistoryComment('Seon: Suspected fraud, order is on hold. <br> Login to your <a href="https://admin.seon.io/">SEON Admin panel</a> for more details.');
                }

                $order->save();
            }
        } catch (Exception $e) {
            $this->_logger->addDebug("PutOrderOnHold Error: $e");
        }
        return $this;
    }

}
