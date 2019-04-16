<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Controller\HtmlRedirect;

use Magento\Framework\App\Request;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Worldpay\Gateway\Command\ResponseCommand;
use Psr\Log\LoggerInterface;

/**
 * Displays message and redirect to the ResultController with appropriate parameter
 *
 * Class Response
 */
class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ResponseCommand
     */
    private $command;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private static $transStatusSuccess = 'Y';

    /**
     * @var string
     */
    private static $transStatusCancel = 'C';

    /**
     * @param Context $context
     * @param ResponseCommand $command
     * @param LoggerInterface $logger
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        ResponseCommand $command,
        LoggerInterface $logger,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);

        $this->command = $command;
        $this->layoutFactory = $layoutFactory;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $resultLayout = $this->layoutFactory->create();
        $resultLayout->addDefaultHandle();
        $processor = $resultLayout->getLayout()->getUpdate();
        try {
            $this->command->execute(['response' => $params]);
        } catch (\Exception $e) {
            $this->logger->critical($e);

            $processor->load(['response_failure']);
            return $resultLayout;
        }

        switch ($params['transStatus']) {
            case self::$transStatusSuccess:
                $processor->load(['response_success']);
                break;
            case self::$transStatusCancel:
                $processor->load(['response_cancel']);
                break;
            default:
                $processor->load(['response_failure']);
                break;
        }

        return $resultLayout;
    }
}
