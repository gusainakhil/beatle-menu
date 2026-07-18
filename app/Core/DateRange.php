<?php

namespace App\Core;

use DateTime;
use Exception;

class DateRange {
    private string $type;
    private DateTime $startDate;
    private DateTime $endDate;

    public function __construct(string $type = 'month', ?string $customStart = null, ?string $customEnd = null) {
        $this->type = in_array($type, ['today', 'week', 'month', 'year', 'custom']) ? $type : 'month';
        $this->endDate = new DateTime();
        $this->endDate->setTime(23, 59, 59);

        $this->startDate = new DateTime();
        
        switch ($this->type) {
            case 'today':
                $this->startDate->setTime(0, 0, 0);
                break;
            case 'week':
                $this->startDate->modify('monday this week');
                $this->startDate->setTime(0, 0, 0);
                break;
            case 'month':
                $this->startDate->modify('first day of this month');
                $this->startDate->setTime(0, 0, 0);
                break;
            case 'year':
                $this->startDate->modify('first day of January this year');
                $this->startDate->setTime(0, 0, 0);
                break;
            case 'custom':
                try {
                    if ($customStart) {
                        $this->startDate = new DateTime($customStart);
                        $this->startDate->setTime(0, 0, 0);
                    } else {
                        $this->startDate->modify('-30 days')->setTime(0, 0, 0);
                    }
                    if ($customEnd) {
                        $this->endDate = new DateTime($customEnd);
                        $this->endDate->setTime(23, 59, 59);
                    }
                } catch (Exception $e) {
                    $this->startDate->modify('-30 days')->setTime(0, 0, 0);
                }
                break;
        }
    }

    public function getType(): string {
        return $this->type;
    }

    public function getStartDate(): DateTime {
        return $this->startDate;
    }

    public function getEndDate(): DateTime {
        return $this->endDate;
    }

    public function getStartString(): string {
        return $this->startDate->format('Y-m-d H:i:s');
    }

    public function getEndString(): string {
        return $this->endDate->format('Y-m-d H:i:s');
    }

    public function getLabel(): string {
        switch ($this->type) {
            case 'today':
                return 'Today (' . $this->startDate->format('M d') . ')';
            case 'week':
                return 'This Week (' . $this->startDate->format('M d') . ' - ' . $this->endDate->format('M d') . ')';
            case 'month':
                return 'This Month (' . $this->startDate->format('M Y') . ')';
            case 'year':
                return 'This Year (' . $this->startDate->format('Y') . ')';
            case 'custom':
                return $this->startDate->format('M d, Y') . ' - ' . $this->endDate->format('M d, Y');
        }
        return '';
    }

    public static function fromRequest(Request $request): self {
        $type = $request->input('range', 'month');
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        return new self($type, $start, $end);
    }
}
