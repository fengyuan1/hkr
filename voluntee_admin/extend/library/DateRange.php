<?php

namespace library;


class DateRange
{
    public static function parse($string)
    {
        list($start, $end) = array_map(
            function ($i) {
                return strtotime('00:00:00', strtotime(trim($i))) ;
            },
            explode('-', $string)
        );
        $end = strtotime('23:59:59', $end);

        return [$start, $end];
    }


    public static function format($start, $end)
    {
        $format = 'Y/m/d';
        return date($format, $start) . ' - ' . date($format, $end);
    }


    public static function thisMonth()
    {
        $start = strtotime(date('Y-m'));
        $end = strtotime('-1 second', strtotime('+1 month', $start));
        return [$start, $end];
    }
}