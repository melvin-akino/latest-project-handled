<?php

use Illuminate\Support\Facades\Cookie;

/**
 * Delete Cookie by Name
 *
 * @param  string   $cookieName     Illuminate\Support\Facades\Cookie;
 */
function deleteCookie(string $cookieName)
{
    Cookie::queue(Cookie::forget($cookieName));
}

if (!function_exists('wsEmit')) {
    function wsEmit($content)
    {
        $server = app('swoole');
        $table = $server->wsTable;
        foreach ($table as $key => $row) {
            if (strpos($key, 'uid:') === 0 && $server->isEstablished($row['value'])) {
//                $content = sprintf('Broadcast: new message "%s" from #%d', $frame->data, $frame->fd);
                $server->push($row['value'], json_encode($content));
            }
        }
    }
}
