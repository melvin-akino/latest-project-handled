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