<?php

class PageDetails
{
    public static function putting( $html = NULL)
    {
        $smallimage = new TImage("app/images/logo_small.png");
        $largeimage = new TImage("app/images/logo_large.png");

        $html = str_replace('{smallLogo}', $smallimage, $html);
        $html = str_replace('{largeLogo}', $largeimage, $html);
        $html = str_replace('{systemname}', "Rádio FM 87.9", $html);
        $html = str_replace('{systemversion}', "Beta", $html);
        $html = str_replace('{systemowner}', "Handshake Solution", $html);
        $html = str_replace('{pagetitle}', "FM 87.9", $html);
        $html = str_replace('{pagefavicon}', "app/images/favicon.png", $html);
        $html = str_replace('{homepage}', "#", $html);

        return $html;
    }
}
