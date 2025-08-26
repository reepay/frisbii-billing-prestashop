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
                        'label' => $this->module->l('Enable Self-service "Pause"'),
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
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Self-service "Cancel"'),
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
            'BILLWERKSUBSCRIPTION_ENABLE_CANCEL' => Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_CANCEL'),
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
