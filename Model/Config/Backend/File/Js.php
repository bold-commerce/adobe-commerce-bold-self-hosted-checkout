<?php
declare(strict_types=1);

namespace Bold\CheckoutSelfHosted\Model\Config\Backend\File;

use Magento\Config\Model\Config\Backend\File;

/**
 * "Bold Checkout Template File" system configuration backend class.
 */
class Js extends File
{
    /**
     * @inheritDoc
     */
    protected function _getAllowedExtensions()
    {
        return ['js'];
    }
}
