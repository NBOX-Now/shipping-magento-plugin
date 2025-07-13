<?php

namespace Nbox\Shipping\Plugin;

use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Nbox\Shipping\Helper\StoreSource;
use Nbox\Shipping\Helper\NboxApi;
use Nbox\Shipping\Utils\Converter;
use Nbox\Shipping\Service\DataFormatter;

/**
 * Class OrderPlugin
 * This plugin handles order-related actions for the Nbox Shipping service.
 */
class OrderPlugin
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var StoreSource
     */
    protected $storeSource;

    /**
     * @var NboxApi
     */
    protected $nboxApi;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var DataFormatter
     */
    protected $dataFormatter;

    /**
     * OrderPlugin constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $storeConfig
     * @param LoggerInterface $logger
     * @param StoreSource $storeSource
     * @param NboxApi $nboxApi
     * @param Converter $converter
     * @param DataFormatter $dataFormatter
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $storeConfig,
        LoggerInterface $logger,
        StoreSource $storeSource,
        NboxApi $nboxApi,
        Converter $converter,
        DataFormatter $dataFormatter
    ) {
        $this->storeManager = $storeManager;
        $this->storeConfig = $storeConfig;
        $this->logger = $logger;
        $this->storeSource = $storeSource;
        $this->nboxApi = $nboxApi;
        $this->converter = $converter;
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * Plugin for Order::place()
     *
     * This method runs AFTER an order is placed.
     *
     * @param Order $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterPlace(Order $subject, $result)
    {
        $this->logger->debug("Plugin Triggered: Order placed!");

        $request = $this->formOrderData($subject);
        
        try {
            $this->nboxApi->checkout($request);
        } catch (\Exception $e) {
            $this->logger->error("Checkout Error: " . $e->getMessage());
        }
        
        return $result; // Ensure original result is returned
    }

    /**
     * Process order cancellation
     *
     * @param Order $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterCancel(Order $subject, $result)
    {
        try {
            $this->logger->info("Order canceled via Plugin: " . $subject->getIncrementId());

            $data = ["id" => (int) $subject->getIncrementId()];

            // Call external API
            $this->logger->debug("TO SEND:", $data);
            $this->nboxApi->cancelled($data);

        } catch (\Exception $e) {
            $this->logger->error("Order cancel plugin error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Process order fulfilled
     *
     * @param Order $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterSave(Order $subject, $result)
    {
        $this->logger->debug("ON STATUS: " . $subject->getStatus());
        $status = $subject->getStatus();

        if ($status == 'shipped' || $status == "ready_for_pickup" || $status == 'processing') {
            try {
                $request = $this->formOrderData($subject);
                $this->logger->debug("TO SEND:", $request);
                $this->nboxApi->fulfilled($request);
            } catch (\Exception $e) {
                $this->logger->error("Checkout Error: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Forms the order data to be sent to the Nbox API
     *
     * @param Order $subject
     * @return array
     */
    private function formOrderData(Order $subject)
    {
        $stores = $this->storeSource->getStoreShippingOrigins();
        
        // Get Shipping Address
        $shippingAddress = $subject->getShippingAddress();
        
        $data = [
            "order" => [
                "id"              => (int) $subject->getIncrementId(),
                "shopDomain"      => $stores[0]['store_domain'],
                "carrier"         => str_replace("nboxshipping_", "", $subject->getShippingMethod()),
                "subTotal"        => (float) $subject->getSubtotal(),
                "orderNumber"     => (int) $subject->getIncrementId(),
                "orderReference"  => $subject->getIncrementId(),
                "total"           => (float) $subject->getGrandTotal(),
                "currency"        => $subject->getOrderCurrencyCode(),
                "shippingFee"     => (float) $subject->getShippingAmount(),
                "paymentStatus"   => $this->getPaymentStatus($subject),
                "paymentMethod"   => $this->getPaymentMethod($subject),
            ],
            "customer" => [
                "firstName"       => $shippingAddress ? $shippingAddress->getFirstname() : '',
                "lastName"        => $shippingAddress ? $shippingAddress->getLastname() : '',
                "email"           => $subject->getCustomerEmail(),
                "phone"           => $shippingAddress ? $shippingAddress->getTelephone() : '',
            ],
            'origin' => $this->dataFormatter->formatOriginAddress($subject->getStoreId()),
            "destination" => $this->dataFormatter->formatDestinationFromOrderAddress($shippingAddress),
            "products" => $this->dataFormatter->formatProductsFromOrderItems(
                $subject->getAllVisibleItems(),
                $subject->getOrderCurrencyCode(),
                $subject->getStoreId()
            ),
        ];
        return $data;
    }

    /**
     * Determine payment status based on actual order payment state
     *
     * @param Order $order
     * @return string
     */
    private function getPaymentStatus(Order $order)
    {
        // Primary check: If nothing is due, order is prepaid
        if ($order->getTotalDue() == 0) {
            return 'prepaid';
        }

        // Secondary check: If payment has been received, it's prepaid
        $payment = $order->getPayment();
        if ($payment && $payment->getAmountPaid() > 0) {
            return 'prepaid';
        }

        // Fallback: Check for known postpaid methods
        if ($payment) {
            $paymentMethod = strtolower($payment->getMethod());
            $postpaidMethods = ['cashondelivery', 'cod', 'checkmo'];
            
            if (in_array($paymentMethod, $postpaidMethods)) {
                return 'postpaid';
            }
        }

        // Default to postpaid for safety (collections may be needed)
        return 'postpaid';
    }

    /**
     * Get payment method name for the order
     *
     * @param Order $order
     * @return string
     */
    private function getPaymentMethod(Order $order)
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return 'unknown';
        }

        return $payment->getMethod();
    }
}
