<?php

namespace App\Utils;

use App\Exceptions\IllegalArgumentException;
use DateTime;
use Exception;

class DateTimeUtil
{

    /**
     * Get the datetime of given timestamp
     * 1704112496 -> 20240101 12:34:56
     *
     * @throws Exception
     */
    public static function getDatetimeOfTimestamp(int $timestamp) : DateTime {
        return new DateTime('@' . $timestamp);
    }


    /**
     * Get the datetime of given datetime string
     * '20240101 12:34:56' -> 20240101 12:34:56
     *
     * @param string $datetimeStr
     * @return DateTime
     * @throws Exception
     */
    public static function getDatetimeOfDatetimeStr(string $datetimeStr) : DateTime {
        return new DateTime($datetimeStr);
    }


    /**
     * Get the datetime of given date string
     *
     * @param string $dateStr
     * @return DateTime
     * @throws IllegalArgumentException
     */
    public static function getDatetimeOfDateStr(string $dateStr) : DateTime {
        $datetime =  DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$datetime) {
            throw new IllegalArgumentException('The date should be in the format of \'YYYY-mm-dd\'');
        }
        return $datetime;
    }


    /**
     * Get the date string of given datetime
     * '20240101T123456' -> 2024-01-01 12:34:56
     *
     * @param $tStr
     * @return DateTime
     */
    public static function getDatetimeOfTCompactStr($tStr) : DateTime {
        return DateTime::createFromFormat('Ymd\THis', $tStr);
    }


    /**
     * Get the date string of given datetime
     * '2024-01-01T12:34:56' -> 2024-01-01 12:34:56
     *
     * @param $tStr
     * @return DateTime
     */
    public static function getDatetimeOfTExpandStr($tStr) : DateTime {
        return DateTime::createFromFormat('Y-m-d\TH:i:s', $tStr);
    }


    /**
     * Get the current timestamp
     * 1388516401 -> '2014-01-01 00:00:01'
     *
     * @param int $timestamp
     * @return string
     */
    public static function getDatetimeStrOfTimestamp(int $timestamp) : string {
        return date("Y-m-d H:i:s", $timestamp);
    }


    /**
     * Get the datetime string of given datetime
     * 2024-01-01 12:34:56 -> '2024-01-01 12:34:56'
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getDatetimeStrOfDatetime(DateTime $dateTime) : string {
        return $dateTime->format('Y-m-d H:i:s');
    }


    /**
     * Get datetime string of given date string
     * '2024-01-01' -> '2024-01-01 00:00:00'
     *
     * @param string $dateStr
     * @return string
     * @throws Exception
     */
    public static function getDateTimeStrOfDateStr(string $dateStr) : string {
        $date = DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($date === false) {
            throw new Exception('Invalid date format.');
        }
        $date->setTime(0, 0);
        return $date->format('Y-m-d H:i:s');
    }


    /**
     * Get the date of given datetime
     * '20240101 12:34:56' -> 20240101 00:00:00
     *
     * @param string $datetimeStr
     * @return DateTime
     * @throws Exception
     */
    public static function getDateOfDatetimeStr(string $datetimeStr) : DateTime {
        $date = new DateTime($datetimeStr);
        $date->setTime(0, 0);
        return $date;
    }


    /**
     * Get the date of given datetime
     * 2024-01-01 12:34:56 -> 2024-01-01 00:00:00
     *
     * @param DateTime $dateTime
     * @return DateTime
     */
    public static function getDateOfDatetime(DateTime $dateTime) : DateTime {
        $dateTime->setTime(0, 0);
        return $dateTime;
    }


    /**
     * Get date string of given dateTime
     * 2024-01-01 12:34:56 -> '20240101'
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getDateCompactStrOfDatetime(DateTime $dateTime) : string {
        return $dateTime->format('Ymd');
    }


    /**
     * Get date string of given dateTime
     * 2024-01-01 12:34:56 -> '2024-01-01'
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getDateExpandStrOfDatetime(DateTime $dateTime) : string {
        return $dateTime->format('Y-m-d');
    }


    /**
     * Get date string of given datetime string
     * '2024-01-01 12:34:56' -> '20240101'
     *
     * @param string $dataTimeStr
     * @return string
     */
    public static function getDateCompactStrOfDateTimeStr(string $dataTimeStr) : string {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dataTimeStr);
        return self::getDateCompactStrOfDatetime($date);
    }


    /**
     * Get date string of given date string
     * '2024-01-01' -> '20240101'
     *
     * @param string $dataStr
     * @return string
     */
    public static function getDateCompactStrOfDateExpandStr(string $dataStr) : string {
        $date = DateTime::createFromFormat('Y-m-d', $dataStr);
        return self::getDateCompactStrOfDatetime($date);
    }


    /**
     * Get date string of given date string
     * '2024-01-01T12:34:56' -> '2024-01-01'
     *
     * @param string $tStr
     * @return string
     */
    public static function getDateExpandStrOfTExpandStr(string $tStr) : string {
        $dateTime = self::getDatetimeOfTExpandStr($tStr);
        return self::getDateExpandStrOfDatetime($dateTime);
    }


    /**
     * Get date int of given dateTime
     * 2024-01-01 12:34:56 -> 20240101
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getDateIntOfDatetime(DateTime $dateTime) : string {
        return (int)self::getDateCompactStrOfDatetime($dateTime);
    }


    /**
     * Get the time of given datetime
     * 2024-01-01 12:34:56 -> '20240101T1234'
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getTCompactStrOfDatetime(DateTime $dateTime) : string {
        return $dateTime->format('Ymd\THi');
    }


    /**
     * Get the date string of given datetime
     * 2024-01-01 12:34:56 -> '2024-01-01T12:34:56'
     *
     * @param DateTime $dateTime
     * @return string
     */
    public static function getTExpandStrOfDatetime(DateTime $dateTime) : string {
        return $dateTime->format('Y-m-d\TH:i:s');
    }


    public static function now() : DateTime|bool {
        return new DateTime();
    }


    public static function today() : DateTime|bool {
        return new DateTime('today');
    }


    public static function yesterday() : DateTime|bool {
        return new DateTime('yesterday');
    }


    /**
     * Get the date of days diff from now
     *
     * @throws Exception
     */
    public static function daysDiffNow($days) : DateTime {
        $now = new DateTime();
        return self::daysDiff($now, $days);
    }


    /**
     * Get the date of days diff from given date
     *
     * @param DateTime $originDate
     * @param $days
     * @return DateTime
     * @throws Exception
     */
    public static function daysDiff(DateTime $originDate, $days) : DateTime {
        $date = clone $originDate;
        $sign = $days >= 0 ? '+' : '-';
        $ret  = $date->modify($sign . abs($days) . ' days');
        if (!$ret) {
            throw new Exception('[daysDiff] modify date failed, days=' . $days);
        }
        return $ret;
    }


    /**
     * Get the date of workdays diff from now
     *
     * @param $days
     * @param array $holidays
     * @return DateTime
     */
    public static function workdaysDiffNow($days, array $holidays = []) : DateTime {
        $now = new DateTime();
        return self::workdaysDiff($now, $days, $holidays);
    }


    public static array $PUBLIC_HOLIDAYS = ['01-01', '01-06', '04-25', '05-01', '06-02', '08-15', '11-01', '12-08', '12-25', '12-26'];

    /**
     * Get the date of workdays diff from now
     *
     * @param DateTime $originDate
     * @param int $days
     * @param array $holidays
     * @return DateTime
     */
    public static function workdaysDiff(DateTime $originDate, int $days, array $holidays = []) : DateTime {
        $current = clone $originDate;
        $holidays = array_merge(self::$PUBLIC_HOLIDAYS, $holidays);

        while ($days > 0) {
            $current->modify('-1 day');
            // weekend
            if (!self::checkWeekday($current)) {
                continue;
            }
            // holiday
            $md = $current->format('m-d');
            if (array_key_exists($md, $holidays)) {
                continue;
            }
            $days--;
        }

        return $current;
    }


    /**
     * Check if the given date is a weekday (Monday to Friday)
     *
     * @param DateTime $date
     * @return bool
     */
    public static function checkWeekday(DateTime $date) : bool {
        return $date->format('N') < 6;
    }


    /**
     * Get the natural range of given datetime
     * '20240101 12:34:56' -> ['startOfDay' => 20240101 00:00:00, 'startOfNextDay' => 20240102 00:00:00]
     *
     * @throws Exception
     */
    public static function getNatualRangeOfDatetime(string $datetime) : array {
        $startOfDay     = self::getDateOfDatetimeStr($datetime);
        $startOfNextDay = self::getDateOfDatetimeStr($datetime)->modify('+1 day');

        return [
            'startOfDay'     => $startOfDay->getTimestamp(),
            'startOfNextDay' => $startOfNextDay->getTimestamp(),
        ];
    }


    /**
     * Split a given period of time into segments by interval
     *
     * @param DateTime $start
     * @param DateTime $stop
     * @param int $interval in seconds
     * @return array
     */
    public static function split(DateTime $start, DateTime $stop, int $interval) : array {
        if ($start > $stop) {
            return [];
        }

        $diffInSeconds = $stop->getTimestamp() - $start->getTimestamp();
        if ($diffInSeconds <= $interval) {
            return [
                [
                    'start' => clone $start,
                    'stop'  => clone $stop
                ]
            ];
        }

        $segments = [];
        for ($current = clone $start; $current < $stop;) {
            if ($current->getTimestamp() + $interval > $stop->getTimestamp()) {
                $segments[] = [
                    'start' => clone $current,
                    'stop'  => clone $stop
                ];
                break;
            }

            $segments[] = [
                'start' => clone $current,
                'stop'  => clone $current->modify('+' . $interval . ' seconds')
            ];
        }

        return $segments;
    }


    /**
     * Split a given period of time into segments by interval
     *
     * @param string $startStr
     * @param string $stopStr
     * @param int $interval
     * @return array
     * @throws Exception
     */
    public static function splitDatetimeStr(string $startStr, string $stopStr, int $interval) : array {
        $start = new DateTime($startStr);
        $stop  = new DateTime($stopStr);

        $segments = self::split($start, $stop, $interval);
        if (!$segments) {
            return [];
        }

        return array_map(function ($_segment) {
            return [
                'start' => $_segment['start']->format('Y-m-d H:i:s'),
                'stop'  => $_segment['stop']->format('Y-m-d H:i:s'),
            ];
        }, $segments);
    }

    /**
     * Count the number of working days between two dates.
     *
     * This function calculate the number of working days between two given dates,
     * taking account of the Public festivities, Easter and Easter Morning days,
     * the day of the Patron Saint (if any) and the working Saturday.
     *
     * @param string $date1       Start date ('YYYY-MM-DD' format)
     * @param string $date2       Ending date ('YYYY-MM-DD' format)
     * @param boolean $workSat    TRUE if Saturday is a working day
     * @param string|null $patron Day of the Patron Saint ('MM-DD' format)
     * @return integer            Number of working days ('zero' on error)
     *
     * @author Massimo Simonini <massiws@gmail.com>
     */
    public static function getWorkdays(string $date1, string $date2, bool $workSat = FALSE, string $patron = NULL) : int {
        if (!defined('SATURDAY')) define('SATURDAY', 6);
        if (!defined('SUNDAY')) define('SUNDAY', 0);

        // Array of all public festivities
        $publicHolidays = array('01-01', '01-06', '04-25', '05-01', '06-02', '08-15', '11-01', '12-08', '12-25', '12-26');
        // The Patron day (if any) is added to public festivities
        if ($patron) {
            $publicHolidays[] = $patron;
        }

        /*
         * Array of all Easter Mondays in the given interval
         */
        $yearStart = date('Y', strtotime($date1));
        $yearEnd   = date('Y', strtotime($date2));

        for ($i = $yearStart; $i <= $yearEnd; $i++) {
            $easter = date('Y-m-d', easter_date($i));
            list($y, $m, $g) = explode("-", $easter);
            $monday = mktime(0,0,0, date($m), date($g)+1, date($y));
            $easterMondays[] = $monday;
        }

        $start = strtotime($date1);
        $end   = strtotime($date2);
        $workdays = 0;
        for ($i = $start; $i <= $end; $i = strtotime("+1 day", $i)) {
            $day = date("w", $i);  // 0=sun, 1=mon, ..., 6=sat
            $mmgg = date('m-d', $i);
            if ($day != SUNDAY &&
                !in_array($mmgg, $publicHolidays) &&
                !in_array($i, $easterMondays) &&
                !($day == SATURDAY && $workSat == FALSE)) {
                $workdays++;
            }
        }

        return intval($workdays);
    }
}
