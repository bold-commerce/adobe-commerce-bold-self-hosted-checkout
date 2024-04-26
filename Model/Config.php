<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Model;

use Bold\Checkout\Api\ConfigManagementInterface;

class Config
{
    private const PATH_IS_ENABLED = 'checkout/bold_checkout_self_hosted/is_enabled';
    private const PATH_TEMPLATE_TYPE = 'checkout/bold_checkout_self_hosted/template_type';
    private const PATH_TEMPLATE_FILE = 'checkout/bold_checkout_self_hosted/template_file';
    private const PATH_TEMPLATE_URL = 'checkout/bold_checkout_self_hosted/template_url';

    /**
     * @var ConfigManagementInterface
     */
    private $configManagement;

    /**
     * @param ConfigManagementInterface $configManagement
     */
    public function __construct(
        ConfigManagementInterface $configManagement
    ) {
        $this->configManagement = $configManagement;
    }


    /**
     * Is self-hosted functionality enabled.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isEnabled(int $websiteId): bool
    {
        return $this->configManagement->isSetFlag(
            self::PATH_IS_ENABLED,
            $websiteId
        );
    }

    /**
     * Retrieve template URL.
     *
     * @param int $websiteId
     * @return string|null
     */
    public function getCheckoutTemplateUrl(int $websiteId): ?string
    {
        return $this->configManagement->getValue(
            self::PATH_TEMPLATE_URL,
            $websiteId
        );
    }

    /**
     * Retrieve template type.
     *
     * @param int $websiteId
     * @return string
     */
    public function getCheckoutTemplateType(int $websiteId): string
    {
        return $this->configManagement->getValue(
            self::PATH_TEMPLATE_TYPE,
            $websiteId
        );
    }

    /**
     * Retrieve template file.
     *
     * @param int $websiteId
     * @return string|null
     */
    public function getCheckoutTemplateFile(int $websiteId): ?string
    {
        return $this->configManagement->getValue(
            self::PATH_TEMPLATE_FILE,
            $websiteId
        );
    }
}
