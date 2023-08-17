<?php

namespace Framework\Utils;

use DateTime;

class TimeUtils {
    public static function getMillisecondsToDateTime(DateTime $dateTime): int {
        $currentTime = new DateTime();
        $timeDifference = $dateTime->diff($currentTime);
        
        $millisecondsDifference = ($timeDifference->s + $timeDifference->i * 60 + $timeDifference->h * 3600) * 1000;
        $millisecondsDifference += $timeDifference->f * 1000;
    
        return round($millisecondsDifference);
    }
}