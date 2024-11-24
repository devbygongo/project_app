<?php

use Carbon\Carbon;

if (!function_exists('formatLastViewed')) {
    function formatLastViewed($lastViewed)
    {
        if (!$lastViewed) {
            return '';
        }

        $currentTimestamp = now(); // Current time
        $lastViewedTimestamp = Carbon::parse($lastViewed);

        $differenceInSeconds = $currentTimestamp->diffInSeconds($lastViewedTimestamp);

        if ($differenceInSeconds < 60) {
            return $differenceInSeconds . ' seconds ago';
        } elseif ($differenceInSeconds < 3600) {
            $minutes = floor($differenceInSeconds / 60);
            return $minutes . ' minutes ago';
        } elseif ($differenceInSeconds < 86400) {
            $hours = floor($differenceInSeconds / 3600);
            return $hours . ' hours ago';
        } else {
            $days = floor($differenceInSeconds / 86400);
            return $days . ' days ago';
        }
    }
}

?>