<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Observer;

use Bold\Checkout\Api\Http\ClientInterface;
use Bold\Checkout\Model\IsBoldCheckoutAllowedForRequest;
use Bold\Checkout\Model\Order\InitOrderFromQuote;
use Bold\Checkout\Model\Quote\IsBoldCheckoutAllowedForCart;
use Bold\Checkout\Observer\Checkout\RedirectToBoldCheckoutObserver as RedirectToBoldCheckout;
use Bold\CheckoutSelfHosted\Model\Config;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Redirect to Self-hosted Bold Checkout Observer.
 */
class RedirectToBoldCheckoutObserver implements ObserverInterface
{
    /**
     * @var RedirectToBoldCheckout
     */
    private $redirectToBoldCheckoutObserver;

    /**
     * @var IsBoldCheckoutAllowedForCart
     */
    private $allowedForCart;

    /**
     * @var IsBoldCheckoutAllowedForRequest
     */
    private $allowedForRequest;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var InitOrderFromQuote
     */
    private $initOrderFromQuote;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RedirectToBoldCheckout $redirectToBoldCheckoutObserver
     * @param IsBoldCheckoutAllowedForCart $allowedForCart
     * @param IsBoldCheckoutAllowedForRequest $allowedForRequest
     * @param Session $checkoutSession
     * @param InitOrderFromQuote $initOrderFromQuote
     * @param Config $config
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        RedirectToBoldCheckout $redirectToBoldCheckoutObserver,
        IsBoldCheckoutAllowedForCart $allowedForCart,
        IsBoldCheckoutAllowedForRequest $allowedForRequest,
        Session $checkoutSession,
        InitOrderFromQuote $initOrderFromQuote,
        Config $config,
        ClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->redirectToBoldCheckoutObserver = $redirectToBoldCheckoutObserver;
        $this->allowedForCart = $allowedForCart;
        $this->allowedForRequest = $allowedForRequest;
        $this->checkoutSession = $checkoutSession;
        $this->initOrderFromQuote = $initOrderFromQuote;
        $this->config = $config;
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Redirect to self-hosted Bold Checkout.
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $websiteId = (int)$quote->getStore()->getWebsiteId();
        if (!$this->config->isEnabled($websiteId)) {
            $this->redirectToBoldCheckoutObserver->execute($observer);
            return;
        }
        $request = $observer->getRequest();
        if (!$this->allowedForCart->isAllowed($quote)) {
            return;
        }
        if (!$this->allowedForRequest->isAllowed($quote, $request)) {
            return;
        }
        try {
            $checkoutData = $this->initOrderFromQuote->init($quote);
            $this->checkoutSession->setBoldCheckoutData($checkoutData);
            $this->client->get($websiteId, 'refresh');
            $checkoutUrl = $quote->getStore()->getUrl('experience/index/index');
            $observer->getControllerAction()->getResponse()->setRedirect($checkoutUrl);
        } catch (Exception $exception) {
            $this->logger->critical($exception);
        }
    }
}
