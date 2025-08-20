<?php

class BillwerkSubscriptionPlan
{
    public function __construct($data)
    {
        $this->setData($data);
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getName()
    {
        return $this->data['name'];
    }

    public function getAmount()
    {
        return $this->data['amount'];
    }

    public function getHandle()
    {
        return $this->data['handle'];
    }

    public function getVersion()
    {
        return $this->data['version'];
    }

    public function getState()
    {
        return $this->data['state'];
    }

    public function getCurrency()
    {
        return $this->data['currency'];
    }

    public function getCreated()
    {
        return $this->data['created'];
    }

    public function getDescription()
    {
        return isset($this->data['description']) ? $this->data['description'] : '';
    }

    public function getVat()
    {
        return isset($this->data['vat']) ? $this->data['vat'] : '';
    }

    public function getQuantity()
    {
        return isset($this->data['quantity']) ? $this->data['quantity'] : '';
    }

    public function getPrepaid()
    {
        return isset($this->data['prepaid']) ? $this->data['prepaid'] : '';
    }

    public function getDeleted()
    {
        return isset($this->data['deleted']) ? $this->data['deleted'] : '';
    }

    public function getDunningPlan()
    {
        return isset($this->data['dunning_plan']) ? $this->data['dunning_plan'] : '';
    }

    public function getTaxPolicy()
    {
        return isset($this->data['tax_policy']) ? $this->data['tax_policy'] : '';
    }

    public function getRenewalReminderEmailDays()
    {
        return isset($this->data['renewal_reminder_email_days']) ? $this->data['renewal_reminder_email_days'] : '';
    }

    public function getTrialReminderEmailDays()
    {
        return isset($this->data['trial_reminder_email_days']) ? $this->data['trial_reminder_email_days'] : '';
    }

    public function getPartialPeriodHandling()
    {
        return isset($this->data['partial_period_handling']) ? $this->data['partial_period_handling'] : '';
    }

    public function getIncludeZeroAmount()
    {
        return isset($this->data['include_zero_amount']) ? $this->data['include_zero_amount'] : '';
    }

    public function getSetupFee()
    {
        return isset($this->data['setup_fee']) ? $this->data['setup_fee'] : '';
    }

    public function getSetupFeeText()
    {
        return isset($this->data['setup_fee_text']) ? $this->data['setup_fee_text'] : '';
    }

    public function getSetupFeeHandling()
    {
        return isset($this->data['setup_fee_handling']) ? $this->data['setup_fee_handling'] : '';
    }

    public function getPartialProrationDays()
    {
        return isset($this->data['partial_period_days']) ? $this->data['partial_period_days'] : '';
    }

    public function getFixedTrialDays()
    {
        return isset($this->data['fixed_trial_days']) ? $this->data['fixed_trial_days'] : '';
    }

    public function getMinimumProratedAmount()
    {
        return $this->data['minimum_prorated_amount']; // ? $this->data['minimum_prorated_amount'] : '';
    }

    public function getAccountFunding()
    {
        return isset($this->data['account_funding']) ? $this->data['account_funding'] : '';
    }

    public function getAmountInclVat()
    {
        return isset($this->data['amount_incl_vat']) ? $this->data['amount_incl_vat'] : '';
    }

    public function getFixedCount()
    {
        return isset($this->data['fixed_count']) ? $this->data['fixed_count'] : '';
    }

    public function getFixedLifeTimeUnit()
    {
        return isset($this->data['fixed_life_time_unit']) ? $this->data['fixed_life_time_unit'] : '';
    }

    public function getFixedLifeTimeLength()
    {
        return isset($this->data['fixed_life_time_length']) ? $this->data['fixed_life_time_length'] : '';
    }

    public function getTrialIntervalUnit()
    {
        return isset($this->data['trial_interval_unit']) ? $this->data['trial_interval_unit'] : '';
    }

    public function getTrialIntervalLength()
    {
        return isset($this->data['trial_interval_length']) ? $this->data['trial_interval_length'] : '';
    }

    public function getIntervalLength()
    {
        return isset($this->data['interval_length']) ? $this->data['interval_length'] : '';
    }

    public function getScheduleType()
    {
        return $this->data['schedule_type'];
    }

    public function getScheduleFixedDay()
    {
        return isset($this->data['schedule_fixed_day']) ? $this->data['schedule_fixed_day'] : '';
    }

    public function getBaseMonth()
    {
        return isset($this->data['base_month']) ? $this->data['base_month'] : '';
    }

    public function getNoticePeriods()
    {
        return isset($this->data['notice_periods']) ? $this->data['notice_periods'] : 0;
    }

    public function getNoticePeriodsAfterCurrent()
    {
        return isset($this->data['notice_periods_after_current']) ? $this->data['notice_periods_after_current'] : '';
    }

    public function getFixationPeriodsFull()
    {
        return isset($this->data['fixation_period_full']) ? $this->data['fixation_period_full'] : '';
    }

    public function getEntitlements()
    {
        return isset($this->data['entitlements']) ? $this->data['entitlements'] : '';
    }

    public function getFixationPeriods()
    {
        return isset($this->data['fixation_periods']) ? $this->data['fixation_periods'] : 0;
    }
}
