<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Controller\Index;

use Bold\CheckoutSelfHosted\Model\Config;
use Magento\Csp\Api\CspAwareActionInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\View\Result\PageFactory;
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
     * @var Config
     */
    private $config;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config,
        PageFactory $resultPageFactory
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
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
        $checkoutTemplateUrl = $this->config->getCheckoutTemplateUrl($websiteId);

        if (!$checkoutTemplateUrl) {
            return $appliedPolicies;
        }

        $appliedPolicies[] = new FetchPolicy(
            'script-src',
            false,
            [$checkoutTemplateUrl],
            ['https']
        );

        return $appliedPolicies;
    }
}
