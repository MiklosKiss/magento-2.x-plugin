<?php

namespace Seon\Fraud\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper {

    private $_data = '{"ip": "178.149.1.161","license_key": "f5ee07ab-481c-4a72-93f6-051d4decac1e"}';
    private $_api_url = 'https://api.seon.io/SeonRestService/fraud-api/v1.0/';
    private $_license_key = '';

    const XML_API_KEY = 'seon_administrator/settings/keyz';
    const XML_ENABLED = 'seon_administrator/settings/enabled';
    const XML_JS_AGENT = 'seon_administrator/settings/agent';

    protected $scopeConfig;
    protected $curlClient;
    protected $_logger; 

    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Psr\Log\LoggerInterface $customLogger, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface, \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_logger = $customLogger;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->curlClient = $curl;
    }

    /**
     * Is the extension enabled in the admin
     * @return mixed
     */
    public function isJavascriptAgentEnabled() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if ($this->scopeConfig->getValue(self::XML_JS_AGENT, $storeScope)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Is the extension enabled in the admin
     * @return mixed
     */
    public function isEnabled() {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if ($this->scopeConfig->getValue(self::XML_ENABLED, $storeScope)) {
            return true;
        } else {
            return false;
        }
    }

    public function getApiUrl() {
        return $this->_api_url;
    }

    public function request($url, $data = null, $contenttype = "application/json", $auth = null) {

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $apyKey = ($this->scopeConfig->getValue(self::XML_API_KEY, $storeScope));

        $curl = curl_init();
        $response = new \Magento\Framework\DataObject();
        $headers = array();

        curl_setopt($curl, CURLOPT_URL, $url);

        if (stripos($url, 'https://') === 0) {
            curl_setopt($curl, CURLOPT_PORT, 443);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($auth) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $auth);
        }

        if ($data) {
            curl_setopt($curl, CURLOPT_POST, 1);

            $headers[] = "Content-Type: $contenttype";
            $headers[] = "Content-length: " . strlen($data);
            $headers[] = "X-API-KEY: " . $apyKey;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        if (count($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 4);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);

        $response = curl_exec($curl);

        if ($response === false || curl_errno($curl)) {
            $error = curl_error($curl);
            $this->_logger->addDebug($error);
        }

        curl_close($curl);

        return $response;
    }

    public function getPurchase($order) {

        $purchase = array();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $order_data = $order->getData(); // getData returns array not whole object!!! 
        $customer = $this->_customerRepositoryInterface->getById($order->getCustomerId());
        $default_billing = $order->getBillingAddress();
        $api_key = $this->scopeConfig->getValue(self::XML_API_KEY, $storeScope);
        /*
         * System data 
         * ------------------------------------------------------------------- */
        $purchase['ip'] = ($order->getRemoteIp() ? $order->getRemoteIp() : '');
        $purchase['license_key'] = $api_key;
        $purchase['javascript'] = ($this->isJavascriptAgentEnabled()) ? 'true' : 'false';
        //$purchase['transaction_id'] = ''; // what is this ?

        /*
         * User data 
         * ------------------------------------------------------------------- */
        $purchase['user_id'] = $order->getCustomerId();
//        $purchase['affiliate_id'] = '';
        $purchase['user_fullname'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
        $purchase['user_name'] = $order->getCustomerFirstname();
//        $purchase['affiliate_name'] = '';
//        $purchase['user_order_memo'] = '';
        $purchase['email'] = $order->getCustomerEmail();
        $purchase['run_email_api'] = 'true';
//        $purchase['password_hash'] = '';
//        $purchase['user_created'] = '';
        $purchase['user_country'] = $default_billing->getCountryId();
        $purchase['user_city'] = $default_billing->getCity();
        $purchase['user_region'] = $default_billing->getRegionId();
        $purchase['user_zip'] = $default_billing->getPostcode();
        $purchase['user_street'] = $default_billing->getStreet();

        /*
         * Device data 
         * ------------------------------------------------------------------- */
//        $sessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
//        $http_user_agent = Mage::helper('core/http')->getHttpUserAgent();
//
//        $purchase['device_id'] = '';
//        $purchase['session_id'] = $sessionId;

        /*
         * Payment data 
         * ------------------------------------------------------------------- */
//        $asv_result = $this->getAvsResponse($payment);
//        $cvv_result = $this->getCvvResponse($payment);
//        $card = $this->getCard($order, $payment);

        $purchase['payment_mode'] = $payment->getMethodInstance()->getCode();
        $purchase['action_type'] = 'purchase';
//        $purchase['card_fullname'] = $card['card_name'];
//        $purchase['card_bin'] = $card['bin'];
//        $purchase['card_hash'] = $card['hash'];
        $purchase['card_last'] = $payment->getCcLast4();
//        $purchase['avs_result'] = $asv_result;
//        $purchase['cvv_result'] = $cvv_result;
        $purchase['phone_number'] = str_replace(' ', '', $default_billing->getTelephone());
        $purchase['transaction_type'] = $order->getMethod();
        $purchase['transaction_amount'] = $order->getGrandTotal();
        $purchase['transaction_currency'] = $order->getOrderCurrencyCode();

        /*
         * Shipping data 
         * ------------------------------------------------------------------- */
        $shipping_address = $order->getShippingAddress()->getData();
        $shipping_address_street = $order->getShippingAddress()->getStreet();
//
        $purchase['shipping_country'] = $shipping_address['country_id'];
        $purchase['shipping_city'] = $shipping_address['city'];
        $purchase['shipping_region'] = $shipping_address['region_id'];
        $purchase['shipping_zip'] = $shipping_address['postcode'];
        $purchase['shipping_street'] = $shipping_address_street;
        $purchase['shipping_fullname'] = $shipping_address['firstname'] . ' ' . $shipping_address['lastname'];
        $purchase['shipping_phone'] = str_replace(' ', '', $shipping_address['telephone']);
        $purchase['shipping_method'] = $order->getShippingDescription();

        /*
         * Billing data 
         * ------------------------------------------------------------------- */
        $billing_address = $order->getBillingAddress()->getData();
        $billing_address_street = $order->getBillingAddress()->getStreet();
//
        $purchase['billing_country'] = $billing_address['country_id'];
        $purchase['billing_city'] = $billing_address['city'];
        $purchase['billing_region'] = $billing_address['region_id'];
        $purchase['billing_zip'] = $billing_address['postcode'];
        $purchase['billing_street'] = $billing_address_street;
        $purchase['billing_phone'] = str_replace(' ', '', $billing_address['telephone']);
//
//        /*
//         * Misc data 
//         * ------------------------------------------------------------------- */
        //$purchase['discount_code'] = $order_data['coupon_code'];
        $purchase['gift'] = '';
        $purchase['gift_message'] = '';
        $purchase['merchant_id'] = '';
        $purchase['merchant_created_at'] = '';
        $purchase['user_label'] = '';

        /*
         * Product data 
         * ------------------------------------------------------------------- */

        $purchase['items'] = $this->getProducts($order);
        //$purchase['transactionId']    = $this->getTransactionId($payment);

        foreach ($purchase as $key => $value) {
            if (is_null($value)) {
                $purchase[$key] = "";
            }
        }
        
        return json_encode($purchase);       
    }

    public function getProducts($quote) {
        $products = array();

        $store = $this->_storeManager->getStore();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        foreach ($quote->getAllItems() as $item) {
            $product_type = $item->getProductType();

            if (!$product_type || $product_type == 'simple' || $product_type == 'downloadable' || $product_type == 'grouped' || $product_type == 'virtual') {
                $product_object = $item->getProduct();

                if ($product_object) {
                    $product = array();

                    $qty = 1;
                    if ($item->getQty()) {
                        $qty = $item->getQty();
                    } else if ($item->getQtyOrdered()) {
                        $qty = $item->getQtyOrdered();
                    }

                    $price = 0;
                    if ($item->getPrice() > 0) {
                        $price = $item->getPrice();
                    } else if ($item->getBasePrice() > 0) {
                        $price = $item->getBasePrice();
                    } else if ($product_object->getData('price') > 0) {
                        $price = $product_object->getData('price');
                    } else {
                        $parent = $item->getData('parent');

                        if (!$parent) {
                            $parent = $item->getParentItem();
                        }

                        if ($parent) {
                            if ($parent->getBasePrice() > 0) {
                                $price = $parent->getBasePrice();
                            } else if ($parent->getPrice()) {
                                $price = $parent->getPrice();
                            }
                        }
                    }

                    $categories = [];
                    $productModel = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
                    $cats = $productModel->getCategoryIds();

                    if (count($cats)) {
                        foreach ($cats as $cat) {
                            $_categoryModel = $objectManager->create('Magento\Catalog\Model\Category')->load($cat);
                            $categories[] = $_categoryModel->getName();
                        }
                    }

                    $product['item_id'] = $item->getSku();
                    $product['item_quantity'] = intval($qty);
                    $product['item_name'] = $item->getName();
                    $product['item_price'] = floatval($price);
                    $product['item_store'] = $store->getName();
                    $product['item_store_country'] = $store->getCode();
                    $product['item_categories'] = $categories;
                    $product['item_url'] = $item->getProductUrl();
                    $product['item_user_label'] = '';

                    $products[] = $product;
                }
            }
        }

        return $products;
    }

}
