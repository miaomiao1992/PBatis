<?php
/**
 * Created by Yang333
 * 2021-06-15更新，添加部分函数
 */

namespace pbatis;

class Date
{

    /**
     * 获取当前时间戳毫秒数
     */
    public static function getMs()
    {
        list ($usec, $sec) = \explode(" ", \microtime());
        return \sprintf('%.0f', ((float)$usec + (float)$sec) * 1000);
    }

    public static function getDateTime()
    {
        return date("Y-m-d H:i:s");
    }

    public static function getDate()
    {
        return date("Y-m-d");
    }

    /**
     * 格式化时间戳，精确到毫秒，x代表毫秒
     */
    public static function uDate($tag, $time)
    {
        $dateArr = \explode(".", $time / 1000);
        $usec = $dateArr[0];
        $sec = isset($dateArr[1]) ? $dateArr[1] : 0;
        $date = \date($tag, $usec);
        return \str_replace('x', $sec, $date);
    }

    /**
     * 计算两个日期相差的月数
     */
    public static function getDiffMonthNum($date1, $date2)
    {
        $date1_stamp = \strtotime($date1);
        $date2_stamp = \strtotime($date2);
        list ($date_1['y'], $date_1['m']) = \explode("-", date('Y-m', $date1_stamp));
        list ($date_2['y'], $date_2['m']) = \explode("-", date('Y-m', $date2_stamp));
        return \abs(($date_2['y'] - $date_1['y']) * 12 + $date_2['m'] - $date_1['m']);
    }

    /**
     * 计算两个日期相差的天数
     */
    public static function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = \strtotime($day1);
        $second2 = \strtotime($day2);
        return ($second1 - $second2) / 86400;
    }

    /**根据某个毫秒形式的日期获取星期几
     * @param integer $ms 毫秒数
     * @param string $prefix 日期形式前缀，默认周
     * @return string
     */
    public static function getWeek($ms, $prefix = '周')
    {
        return self::getWeekByInt((self::diffBetweenTwoDays(self::uDate('Y-m-d', $ms), '1970-01-01') + 4) % 7, $prefix);
    }

    /**根据数字获取星期几
     * @param integer $week
     * @param string $prefix
     * @return bool|string
     */
    private static function getWeekByInt($week = 99, $prefix = '周')
    {
        if ($week == "1") {
            return $prefix . "一";
        } else if ($week == "2") {
            return $prefix . "二";
        } else if ($week == "3") {
            return $prefix . "三";
        } else if ($week == "4") {
            return $prefix . "四";
        } else if ($week == "5") {
            return $prefix . "五";
        } else if ($week == "6") {
            return $prefix . "六";
        } else if ($week == "0") {
            return $prefix . "日";
        } else {
            return false;
        }
    }

    /**获取某个毫秒形式的时间获取对应周一的日期Y-m-d
     * @param integer $ms 毫秒数
     * @return string 日期格式
     */
    public static function getWeekMonday($ms)
    {
        $iDay = (self::diffBetweenTwoDays(Date::uDate('Y-m-d', $ms), '1970-01-01') + 4) % 7;
        return $iDay === 0 ? self::uDate('Y-m-d', $ms - 24 * 3600000 * 6) : self::uDate('Y-m-d', $ms - 24 * 3600000 * ($iDay - 1));
    }

    /**获取某个月最大天数
     *
     */
    public static function getMonthLastDay($year, $month)
    {
        if ($year == '') {
            $year = \date("Y", \time());
        }
        switch ($month) {
            case 4:
            case 6:
            case 9:
            case 11:
                $days = 30;
                break;
            case 2:
                if ($year % 4 == 0) {
                    if ($year % 100 == 0) {
                        $days = $year % 400 == 0 ? 29 : 28;
                    } else {
                        $days = 29;
                    }
                } else {
                    $days = 28;
                }
                break;
            default:
                $days = 31;
                break;
        }
        return $days;
    }

    /**
     * @param int $iDay 从今天数往前多少天（包含今天）
     * @param string $tag 日期格式，默认："Y-m-d"
     * @return array 日期数组
     */
    public static function getDateBeforeNow($iDay = 0, $tag = "Y-m-d")
    {
        $array = [];
        $ms = self::getMs();
        for ($i = 0; $i < $iDay; $i++) {
            $date = self::uDate($tag, $ms);
            array_push($array, $date);
            $ms = $ms - 24 * 60 * 60 * 1000;
        }
        return $array;
    }
}

