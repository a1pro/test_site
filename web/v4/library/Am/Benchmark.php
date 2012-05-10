<?php

class Am_Benchmark
{
    static $points = array();
    static function start()
    {
        self::mark('start');
    }
    static function mark($point)
    {
        self::$points[$point] = array(
            'tm' => microtime($point),
            'mem' => memory_get_usage($point),
        );
    }
    static function finish()
    {
        self::mark('finish');
        $tm_start = self::$points['start']['tm'];
        echo "<table border=1 cellspacing=3 cellpadding=2>";
        foreach (self::$points as $k => $p)
        {
            $tm = $p['tm']; $mem = $p['mem'];
            if (!empty($pp)) {
                $ttm = sprintf('%.3fms', ($p['tm'] - $tm_start)*1000);
                $tm = sprintf('%.3fms', ($tm - $pp['tm'])*1000);
                $mem -= $pp['mem'];
            } else {
                $tm = $ttm = 0;
            }
            $mem = sprintf('%.2f MB', $mem/(1024*1024));
            echo "<tr><th>$k</th><td align=right>$tm</td><td align=right>$mem</td><td align=right>$p[mem]</td><td align=right>$ttm</td></tr>\n";
            $pp = $p;
        }
        echo "</table>\n";
    }
}