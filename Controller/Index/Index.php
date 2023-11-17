<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Controller\Index;

use Bold\Checkout\Api\ConfigManagementInterface;
use Bold\CheckoutSelfHosted\Block\Checkout;
use Magento\Csp\Api\CspAwareActionInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Render Bold Checkout Self-Hosted page.
 */
class Index implements ActionInterface, CspAwareActionInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigManagementInterface
     */
    private $configManagement;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ConfigManagementInterface $configManagement
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigManagementInterface $configManagement,
        PageFactory $resultPageFactory
    ) {
        $this->storeManager = $storeManager;
        $this->configManagement = $configManagement;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function modifyCsp(array $appliedPolicies): array
    {
        $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
        $reactAppUrl = $this->configManagement->getValue(
            Checkout::CONFIG_PATH_TEMPLATE_URL,
            $websiteId
        );
        $appliedPolicies[] = new FetchPolicy(
            'script-src',
            false,
            [$reactAppUrl],
            ['https']
        );

        return $appliedPolicies;
    }
}
