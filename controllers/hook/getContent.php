<?php

class BillwerkSubscriptionGetContentController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run()
    {
        $output = '';
        if (Tools::isSubmit('submitBillwerkSubscriptionModule')) {
            $output .= $this->module->displayConfirmation($this->module->l('Settings have been saved.'));
            $this->saveData();
            $result = $this->updateWebhooks();

            if (200 != $result['code'] && isset($result['response']['message'])) {
                $message = $result['response']['message'];
                $message = ' :'.$message;
                // omit reporting error for merchant if there was test on
                // localhost and webhooks url is not valid for Frisbii api
                if (!strpos($message, 'unknown host')) {
                    $output .= $this->module->displayError('Error during updating webhooks'.$message);
                }
            }
        }

        return $output.$this->renderForm();
    }

    public function processAjax()
    {
        if ($handle = Tools::getValue('handle')) {
            $planData = BillwerkPlusApi::getSubscriptionPlan($handle, false);
            $subscription_plan = new BillwerkSubscriptionPlan($planData);
            $planHelper = new BillwerkSubscriptionPlanHelper($subscription_plan);
            echo $planHelper->getPlanMerchantDataTable();
        }
    }

    public function saveData()
    {
        return $this->postProcess();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => 'Frisbii Subscription',
                    'icon' => 'icon-envelop',
                ],
                'input' => [
                    [
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'BILLWERK_PRIVATE_API_KEY',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'label' => $this->module->l('Private API Key'),
                        'desc' => $this->module->l('You can find this at your Frisbii Dashboard under "Configurations->Integraion and tools->API credentials"'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enabled'),
                        'name' => 'BILLWERKSUBSCRIPTION_ENABLED',
                        'desc' => $this->module->l('Enable or disable the module'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable on Hold'),
                        'name' => 'BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Compensation method for On Hold'),
                        'name' => 'BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_FOR_ON_HOLD',
                        'required' => false,
                        'desc' => $this->module->l('Compensation method when setting a subscription to On Hold'),
                        'default_value' => (int) $this->context->country->id,
                        'options' => [
                            'query' => [
                                ['key' => 'none', 'name' => $this->module->l('None')],
                                ['key' => 'full_refund', 'name' => $this->module->l('Full refund')],
                                ['key' => 'prorated_refund', 'name' => $this->module->l('Prorated refund')],
                                ['key' => 'full_credit', 'name' => $this->module->l('Full credit')],
                                ['key' => 'prorated_credit', 'name' => $this->module->l('Prorated credit')],
                            ],
                            'name' => 'name',
                            'id' => 'key',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Cancel'),
                        'name' => 'BILLWERKSUBSCRIPTION_ENABLE_CANCEL',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ]],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Compensation method for Cancel'),
                        'name' => 'BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_CANCEL',
                        'desc' => $this->module->l('Compensation method when cancelling a subscription.'),
                        'required' => false,
                        'default_value' => (int) $this->context->country->id,
                        'options' => [
                            'query' => [
                                ['key' => 'none', 'name' => $this->module->l('None')],
                                ['key' => 'full_refund', 'name' => $this->module->l('Full refund')],
                                ['key' => 'prorated_refund', 'name' => $this->module->l('Prorated refund')],
                                ['key' => 'full_credit', 'name' => $this->module->l('Full credit')],
                                ['key' => 'prorated_credit', 'name' => $this->module->l('Prorated credit')],
                            ],
                            'name' => 'name',
                            'id' => 'key',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Subscription order default status after creation '),
                        'name' => 'BILLWERKSUBSCRIPTION_ORDER_DEFAULT_STATUS_AFTER_CREATION',
                        'desc' => $this->module->l('Setting to control witch status the Frisbii Optimize order in Prestashop gets.'),

                        'required' => false,
                        'options' => [
                            'query' => OrderState::getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')),
                            'id' => 'id_order_state',
                            'name' => 'name',
                            'desc' => '',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Renewal order default status after creation'),
                        'name' => 'BILLWERKSUBSCRIPTION_RENEWAL_DEFAULT_STATUS_AFTER_CREATION',
                        'desc' => $this->module->l('Setting to control witch status the Frisbii Optimize renewal order in Prestashop gets.'),
                        'required' => false,
                        'options' => [
                            'query' => [
                                ['key' => 'pending_payment', 'name' => $this->module->l('Pending payment')],
                                ['key' => 'processing', 'name' => $this->module->l('Processing')],
                                ['key' => 'on_hold', 'name' => $this->module->l('On hold')],
                                ['key' => 'completed', 'name' => $this->module->l('Completed')],
                                ['key' => 'cancelled', 'name' => $this->module->l('Cancelled')],
                                ['key' => 'refunded', 'name' => $this->module->l('Refunded')],
                                ['key' => 'failed', 'name' => $this->module->l('Failed')],
                                ['key' => 'draft', 'name' => $this->module->l('Draft')],
                            ],
                            'name' => 'name',
                            'id' => 'key',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable manual subscription start date'),
                        'name' => 'BILLWERKSUBSCRIPTION_ENABLE_MANUAL_START_DATE',
                        'desc' => $this->module->l('This will set a temporary start date for the subscription that is 
                        far in the future. We recommend removing the start date tag from your sign up emails in Frisbii Optimize.'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ]],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Manual start date order status'),
                        'name' => 'BILLWERKSUBSCRIPTION_MANUAL_START_DATE_ORDER_STATUS',
                        'required' => false,
                        'options' => [
                            'query' => [
                                ['key' => 'pending_payment', 'name' => $this->module->l('Pending payment')],
                                ['key' => 'processing', 'name' => $this->module->l('Processing')],
                                ['key' => 'on_hold', 'name' => $this->module->l('On hold')],
                                ['key' => 'completed', 'name' => $this->module->l('Completed')],
                                ['key' => 'cancelled', 'name' => $this->module->l('Cancelled')],
                                ['key' => 'refunded', 'name' => $this->module->l('Refunded')],
                                ['key' => 'failed', 'name' => $this->module->l('Failed')],
                                ['key' => 'draft', 'name' => $this->module->l('Draft')],
                            ],
                            'name' => 'name',
                            'id' => 'key',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable manual subscription start date'),
                        'name' => 'BILLWERKSUBSCRIPTION_ENABLE_MANUAL_SUBSCRIPTION_START_DATE',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ]],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Manual start date order status '),
                        'name' => 'BILLWERKSUBSCRIPTION_MANUAL_SUBSCRIPTION_START_DATE_ORDER_STATUS',
                        'required' => false,
                        'options' => [
                            'query' => [
                                ['key' => 'pending_payment', 'name' => $this->module->l('Pending payment')],
                                ['key' => 'processing', 'name' => $this->module->l('Processing')],
                                ['key' => 'on_hold', 'name' => $this->module->l('On hold')],
                                ['key' => 'completed', 'name' => $this->module->l('Completed')],
                                ['key' => 'cancelled', 'name' => $this->module->l('Cancelled')],
                                ['key' => 'refunded', 'name' => $this->module->l('Refunded')],
                                ['key' => 'failed', 'name' => $this->module->l('Failed')],
                                ['key' => 'draft', 'name' => $this->module->l('Draft')],
                            ],
                            'name' => 'name',
                            'id' => 'key',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Disable Subscription order mails'),
                        'name' => 'BILLWERKSUBSCRIPTION_DISABLE_SUBSCRIPTION_ORDER_MAILS',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ]],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Disable Renewals order mails'),
                        'name' => 'BILLWERKSUBSCRIPTION_DISABLE_RENEWALS_ORDER_MAILS',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ]],

                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Debug mode'),
                        'name' => 'BILLWERKSUBSCRIPTION_DEBUG_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->module->l('When enabled debug data will be logged in Advanced Parameters->Logs'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => 'Enabled',
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => 'Disabled',
                            ],
                        ],
                    ],
                ], 'submit' => [
                    'title' => $this->module->l('Save'),
                ],
            ]];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->submit_action = 'submitBillwerkSubscriptionModule';

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => 2,
            'id_language' => 3,
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function getConfigFormValues()
    {
        return [
            'BILLWERK_PRIVATE_API_KEY' => Configuration::get('BILLWERK_PRIVATE_API_KEY'),
            'BILLWERKSUBSCRIPTION_ENABLED' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLED', 0),
            'BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD', 0),
            'BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_FOR_ON_HOLD' => Configuration::get('BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_FOR_ON_HOLD'),
            'BILLWERKSUBSCRIPTION_ENABLE_CANCEL' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_CANCEL'),
            'BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_CANCEL' => Configuration::get('BILLWERKSUBSCRIPTION_COMPENSATION_METHOD_CANCEL'),
            'BILLWERKSUBSCRIPTION_ORDER_DEFAULT_STATUS_AFTER_CREATION' => Configuration::get('BILLWERKSUBSCRIPTION_ORDER_DEFAULT_STATUS_AFTER_CREATION'),
            'BILLWERKSUBSCRIPTION_RENEWAL_DEFAULT_STATUS_AFTER_CREATION' => Configuration::get('BILLWERKSUBSCRIPTION_RENEWAL_DEFAULT_STATUS_AFTER_CREATION'),
            'BILLWERKSUBSCRIPTION_ENABLE_MANUAL_START_DATE' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_MANUAL_START_DATE'),
            'BILLWERKSUBSCRIPTION_MANUAL_START_DATE_ORDER_STATUS' => Configuration::get('BILLWERKSUBSCRIPTION_MANUAL_START_DATE_ORDER_STATUS'),
            'BILLWERKSUBSCRIPTION_ENABLE_MANUAL_SUBSCRIPTION_START_DATE' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_MANUAL_SUBSCRIPTION_START_DATE'),
            'BILLWERKSUBSCRIPTION_MANUAL_SUBSCRIPTION_START_DATE_ORDER_STATUS' => Configuration::get('BILLWERKSUBSCRIPTION_MANUAL_SUBSCRIPTION_START_DATE_ORDER_STATUS'),
            'BILLWERKSUBSCRIPTION_DISABLE_SUBSCRIPTION_ORDER_MAILS' => Configuration::get('BILLWERKSUBSCRIPTION_DISABLE_SUBSCRIPTION_ORDER_MAILS'),
            'BILLWERKSUBSCRIPTION_DISABLE_RENEWALS_ORDER_MAILS' => Configuration::get('BILLWERKSUBSCRIPTION_DISABLE_RENEWALS_ORDER_MAILS'),
            'BILLWERKSUBSCRIPTION_DEBUG_ENABLED' => Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED'),
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    protected function updateWebhooks()
    {
        $result = BillwerkPlusApi::getWebhookSettings();
        if (200 != $result['code']) {
            return $result;
        }
        $webhooks = ['subscription_renewal'];
        $urls = $result['response']['urls'];
        $urls[] = $this->context->link->getModuleLink('billwerksubscription', 'webhook', [], true);
        $alert_emails = $result['response']['alert_emails'];
        $alert_emails[] = Configuration::get('PS_SHOP_EMAIL');
        $event_types = $result['response']['event_types'];
        $event_types = array_merge($event_types, $webhooks);
        $data = [
            'urls' => array_unique($urls),
            'disabled' => true,
            'alert_emails' => array_unique($alert_emails),
            'event_types' => array_unique($event_types),
        ];

        return BillwerkPlusApi::updateWebhooks($data);
    }
}
