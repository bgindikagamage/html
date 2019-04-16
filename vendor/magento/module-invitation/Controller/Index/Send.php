<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Index;

use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\ObjectManager;

/**
 * Controller for sending customer invitations.
 */
class Send extends \Magento\Invitation\Controller\Index
{
    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * Send invitations from frontend
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $validFormKey = $this->getFormKeyValidator()->validate($this->getRequest());
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            if (!$validFormKey) {
                $this->messageManager->addError(__('Invalid Form Key. Please refresh the page.'));
            } else {
                $customer = $this->_session->getCustomer();
                $message = isset($data['message']) ? $data['message'] : '';
                if (!$this->_config->isInvitationMessageAllowed()) {
                    $message = '';
                }
                $invPerSend = $this->_config->getMaxInvitationsPerSend();
                $attempts = 0;
                $sent = 0;
                $customerExists = 0;
                foreach ($data['email'] as $email) {
                    $attempts++;
                    if (!\Zend_Validate::is($email, 'EmailAddress')) {
                        continue;
                    }
                    if ($attempts > $invPerSend) {
                        continue;
                    }
                    try {
                        $invitation = $this->invitationFactory->create()->setData(
                            ['email' => $email, 'customer' => $customer, 'message' => $message]
                        )->save();
                        if ($invitation->sendInvitationEmail()) {
                            $this->messageManager->addSuccess(__('You sent the invitation for %1.', $email));
                            $sent++;
                        } else {
                            // not \Magento\Framework\Exception\LocalizedException intentionally
                            throw new \Exception('');
                        }
                    } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                        $customerExists++;
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $this->messageManager->addError($e->getMessage());
                    } catch (\Exception $e) {
                        $this->messageManager->addError(
                            __('Something went wrong while sending an email to %1.', $email)
                        );
                    }
                }
                if ($customerExists) {
                    $this->messageManager->addNotice(
                        __('We did not send %1 invitation(s) addressed to current customers.', $customerExists)
                    );
                }
            }
            $this->_redirect('*/*/');
            return;
        }

        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Send Invitations'));
        $this->_view->renderLayout();
    }

    /**
     * Get form key validator
     *
     * @deprecated 100.1.1
     *
     * @return FormKeyValidator
     */
    private function getFormKeyValidator()
    {
        if (!$this->formKeyValidator) {
            $this->formKeyValidator = ObjectManager::getInstance()->get(FormKeyValidator::class);
        }
        return $this->formKeyValidator;
    }
}
