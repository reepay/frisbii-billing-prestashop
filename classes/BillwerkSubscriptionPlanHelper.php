<?php

class BillwerkSubscriptionPlanHelper
{
    // Schedule type
    public const TYPE_DAILY = 'daily';
    public const TYPE_MONTH_START_DATE = 'month_startdate';
    public const TYPE_MONTH_FIXED_DAY = 'month_fixedday';
    public const TYPE_MONTH_LAST_DAY = 'month_lastday';
    public const TYPE_WEEKLY_FIXED_DAY = 'weekly_fixedday';
    public const TYPE_MANUAL = 'manual';

    private $plan;

    public function __construct(BillwerkSubscriptionPlan $plan)
    {
        $this->plan = $plan;
    }

    public function getScheduleTypeText()
    {
        return $this->sheduleTypeTexts[$this->plan->getScheduleType()];
    }

    private $data;

    private $sheduleTypeTexts =
        [
            self::TYPE_DAILY => 'Day(s)',
            self::TYPE_MONTH_START_DATE => 'Month(s)',
            self::TYPE_MONTH_FIXED_DAY => 'Fixed day of month',
            self::TYPE_MONTH_LAST_DAY => 'Last day of month',
            self::TYPE_WEEKLY_FIXED_DAY => 'Fixed day of week',
            self::TYPE_MANUAL => 'Manual'];

    private $billTypes = [
        'bill_prorated' => 'Bill prorated (Default)',
        'bill_full' => 'Bill for full period)',
        'bill_zero_amount' => 'Bill a zero amount',
        'no_bill' => 'Do not consider the partial period a billing period',
    ];

    private $prorationTypes = [
        'full_day' => 'Full day proration',
        'by_minute' => 'By the minute proration'];

    public function getPlanMerchantData()
    {
        $data = [];
        $data['Price'] = $this->preparePrice($this->plan->getAmount(), $this->plan->getCurrency());
        $data['Schedule type'] = $this->getScheduleTypeText();

        if ($this->plan->getIntervalLength() && self::TYPE_MANUAL !== $this->plan->getScheduleType()) {
            $suffix = $this->getScheduleTypeLabel();
            $data['Charge every'] = $this->plan->getIntervalLength().' '.$suffix;
        }

        if ($this->plan->getScheduleFixedDay()) {
            $fixedDayText = 'On this day of the week';
            if (false !== strpos($this->plan->getScheduleType(), 'month')) {
                $fixedDayText = 'On this day of the month';
            }
            $data[$fixedDayText] = $this->plan->getScheduleFixedDay();

            $data['Partial period handling'] = $this->billTypes[$this->plan->getPartialPeriodHandling()];

            $data['Minimum prorated amount'] = $this->plan->getMinimumProratedAmount() ? $this->plan->getMinimumProratedAmount() : 0;
        }

        if ($this->plan->getQuantity()) {
            $data['Quantity'] = $this->plan->getQuantity();
        }

        if ($this->plan->getRenewalReminderEmailDays()) {
            $data['Renewal Reminder'] = $this->plan->getRenewalReminderEmailDays();
        }

        $data['Minimum contract period'] = $this->plan->getFixationPeriods();
        $data['Notice period'] = $this->plan->getNoticePeriods();

        if ($this->plan->getNoticePeriodsAfterCurrent()) {
            $data['Notice period start'] = 'When the current cancelled period ends';
        } else {
            $data['Notice period start'] = 'Immediately after cancellation';
        }

        if ($this->plan->getSetupFee()) {
            $data = array_merge($data, $this->getFeeData());
        }

        if ($this->plan->getTrialIntervalLength()) {
            $data = array_merge($data, $this->getTrialData());
        }

        return $data;
    }

    public function getPlanMerchantDataTable()
    {
        $out = '';
        $result = $this->getPlanMerchantData();
        foreach ($result as $key => $value) {
            $out .= "<tr> <td> {$key} </td> <td> {$value} </td> </tr>";
        }

        return "<table class=\"table\" style=\"width: 50%\"> {$out} </table>";
    }

    private function preparePrice($amount, $currency)
    {
        return ($amount / 100).' '.$currency;
    }

    protected function getFeeData()
    {
        $data = [];
        if ($this->plan->getSetupFee()) {
            $data['Include setup fee'] = 'Active';
            $data['Setup fee'] = (intval($this->plan->getSetupFee()) / 100).' '.$this->plan->getCurrency();
            if ($this->plan->getSetupFeeText()) {
                $data['Text'] = $this->plan->getSetupFeeText();
            }

            $handlingText = '';
            switch ($this->plan->getSetupFeeHandling()) {
                case 'first':
                    $handlingText = 'Include setup fee as order line on the first scheduled invoice';
                    break;
                case 'separate':
                    $handlingText = 'Create a separate invoice for the setup fee';
                    break;
                case 'separate_conditional':
                    $handlingText = 'Create a separate invoice for the setup fee, if the first invoice is not created in conjunction with the creation';
                    break;
            }

            $data['Handling'] = $handlingText;

            return $data;
        }
    }

    protected function getTrialData()
    {
        $data = [];
        $data['Trial period'] = $this->plan->getTrialIntervalLength().' '.$this->plan->getTrialIntervalUnit();

        return $data;
    }

    public function getSubscriptionDetails()
    {
        $return = [];
        $schedule_type = $this->plan->getScheduleType();
        $return['schedule_type'] = $schedule_type;
        $interval = $this->plan->getIntervalLength();
        $return['interval'] = $interval;
        $return['amount'] = $this->plan->getAmount();
        $return['currency'] = $this->plan->getCurrency();
        $return['schedule_type_text'] = $this->getScheduleTypeLabel();
        if ($this->plan->getIntervalLength() > 1) {
            $return['schedule_type_text'] = $this->plan->getIntervalLength().
                                    ' '.$this->getScheduleTypeLabel().'s';
        }
        if (self::TYPE_DAILY == $this->plan->getScheduleType()) {
            $text = 'Billed every %s day%s';
            if ($this->plan->getIntervalLength() > 1) {
                $text = sprintf($text, $this->plan->getIntervalLength(), 's');
            }
            $text = str_replace('%s', '', $text);
            $return['billing_description'] = $text;
        }

        if (self::TYPE_MANUAL == $this->plan->getScheduleType()) {
            $return['billing_description'] = 'Manual';
        }

        if (self::TYPE_MONTH_START_DATE == $this->plan->getScheduleType()) {
            $text = 'Billed every %s month%s on the first day on the month';
            if ($this->plan->getIntervalLength() > 1) {
                $text = sprintf($text, $this->plan->getIntervalLength(), 's');
            }
            $text = str_replace('%s', '', $text);
            $return['billing_description'] = $text;
        }

        if (self::TYPE_MONTH_LAST_DAY == $this->plan->getScheduleType()) {
            $text = 'Billed every %s month%s on the last day on the month';
            if ($this->plan->getIntervalLength() > 1) {
                $text = sprintf($text, $this->plan->getIntervalLength(), 's');
            }
            $text = str_replace('%s', '', $text);
            $return['billing_description'] = $text;
        }

        if (self::TYPE_MONTH_FIXED_DAY == $this->plan->getScheduleType()) {
            $text = 'Billed every %s month%s';
            if ($this->plan->getIntervalLength() > 1) {
                $text = sprintf($text, $this->plan->getIntervalLength(), 's');
            }
            $text = str_replace('%s', '', $text);
            $text .= sprintf(' on day %s', $this->plan->getScheduleFixedDay());
            $return['billing_description'] = $text;
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getScheduleTypeLabel()
    {
        $suffix = '';
        switch ($this->plan->getScheduleType()) {
            case self::TYPE_DAILY:
            case self::TYPE_WEEKLY_FIXED_DAY:
                $suffix = 'Day';
                break;

            case self::TYPE_MONTH_START_DATE:
            case self::TYPE_MONTH_FIXED_DAY:
            case self::TYPE_MONTH_LAST_DAY:
                $suffix = 'Month';
                break;
        }

        return $suffix;
    }
}
