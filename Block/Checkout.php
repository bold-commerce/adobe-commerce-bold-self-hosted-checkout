<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Block;

use Bold\Checkout\Model\ConfigInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Theme\Block\Html\Header\Logo;

/**
 * Bold Checkout Self-Hosted block.
 */
class Checkout extends Template
{
    private const CONFIG_PATH_TEMPLATE_URL = 'checkout/bold_checkout_advanced/template_url';
    private const CONFIG_PATH_TEMPLATE_TYPE = 'checkout/bold_checkout_base/template_type';
    private const CONFIG_PATH_TEMPLATE_FILE = 'checkout/bold_checkout_base/template_file';
    private const UPLOAD_DIR = 'bold/checkout/template';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var ConfigInterface
     */
    private $checkoutConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Logo
     */
    private $logo;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Json $serializer
     * @param ConfigInterface $checkoutConfig
     * @param ScopeConfigInterface $config
     * @param Logo $logo
     * @param ManagerInterface $messageManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        Json $serializer,
        ConfigInterface $checkoutConfig,
        ScopeConfigInterface $config,
        Logo $logo,
        ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
        $this->checkoutConfig = $checkoutConfig;
        $this->config = $config;
        $this->logo = $logo;
        $this->messageManager = $messageManager;
    }

    /**
     * Get order data.
     *
     * @return string
     * @throws ValidatorException
     */
    public function getOrderData(): string
    {
        $boldCheckoutData = $this->checkoutSession->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            $this->messageManager->addErrorMessage(
                __('There was an error during checkout. Please contact us or try again later.')
            );
            throw new ValidatorException(__('Bold Checkout data is missing.'));
        }
        return $this->serializer->serialize($boldCheckoutData);
    }

    /**
     * Get shop identifier.
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getShopIdentifier(): string
    {
        $websiteId = (int)$this->checkoutSession->getQuote()->getStore()->getWebsiteId();
        return $this->checkoutConfig->getShopId($websiteId);
    }

    /**
     * Get shop alias.
     *
     * @return string
     */
    public function getShopAlias(): string
    {
        return $this->checkoutSession->getBoldCheckoutData()['data']['initial_data']['shop_name'] ?? '';
    }

    /**
     * Get custom domain.
     *
     * @return string
     */
    public function getCustomDomain(): string
    {
        return $this->checkoutSession->getBoldCheckoutData()['data']['initial_data']['shop_name'] ?? '';
    }

    /**
     * Get shop name.
     *
     * @return string
     */
    public function getShopName()
    {
        $checkoutData = $this->checkoutSession->getBoldCheckoutData();
        $boldShopName = $checkoutData['data']['initial_data']['shop_name'] ?? '';
        return $this->config->getValue('general/store_information/name') ?: $boldShopName;
    }

    /**
     * Get return url.
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getReturnUrl(): string
    {
        return $this->checkoutSession->getQuote()->getStore()->getBaseUrl();
    }

    /**
     * Get login url.
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getLoginUrl(): string
    {
        return $this->checkoutSession->getQuote()->getStore()->getUrl('customer/account/login');
    }

    /**
     * Retrieve template script URL.
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCheckoutTemplateScriptUrl(): string
    {
        $websiteId = (int)$this->checkoutSession->getQuote()->getStore()->getWebsiteId();
        $templateUrl = $this->getCheckoutTemplateUrl($websiteId);
        $templateType = $this->getCheckoutTemplateType($websiteId);

        if (!empty($templateUrl)) {
            return rtrim($templateUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $templateType . '.js';
        }

        $templateFile = $this->getCheckoutTemplateFile($websiteId);
        if (!empty($templateFile)) {
            $mediaUrl = $this->checkoutSession->getQuote()->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $templateFile;
        }

        return $this->getViewFileUrl('Bold_CheckoutSelfHosted::js' . DIRECTORY_SEPARATOR . $templateType . '.js');
    }

    /**
     * Get store logo.
     *
     * @return string
     */
    public function getHeaderLogoUrl(): string
    {
        return $this->logo->getLogoSrc();
    }

    /**
     * Retrieve public order id from bold checkout data.
     *
     * @return string
     */
    public function getPublicOrderId(): string
    {
        return $this->checkoutSession->getBoldCheckoutData()['data']['public_order_id'] ?? '';
    }

    /**
     * Retrieve template URL.
     *
     * @param int $websiteId
     * @return string|null
     */
    private function getCheckoutTemplateUrl(int $websiteId): ?string
    {
        return $this->config->getValue(
            self::CONFIG_PATH_TEMPLATE_URL,
            $websiteId ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }

    /**
     * Retrieve template type.
     *
     * @param int $websiteId
     * @return string
     */
    private function getCheckoutTemplateType(int $websiteId): string
    {
        return $this->config->getValue(
            self::CONFIG_PATH_TEMPLATE_TYPE,
            $websiteId ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }

    /**
     * Retrieve template file.
     *
     * @param int $websiteId
     * @return string|null
     */
    private function getCheckoutTemplateFile(int $websiteId): ?string
    {
        return $this->config->getValue(
            self::CONFIG_PATH_TEMPLATE_FILE,
            $websiteId ? ScopeInterface::SCOPE_WEBSITES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        );
    }
}
