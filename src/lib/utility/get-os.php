<?php

use Sinergi\BrowserDetector\Os;

/**
 * get_os
 *
 * @see https://stackoverflow.com/questions/18070154/get-operating-system-info
 * @return string
 * 
 */

function get_os()
{

    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

    $os = new Os();

    $unknown_os = $os->getName();

    $os_array     = array(

        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/debian/i'             =>  'Debian',
        '/gentoo/i'             =>  'Gentoo',
        '/arch/i'               =>  'Arch',
        '/slackware/i'          =>  'Slackware',
        '/redhat/i'             =>  'RedHat',
        '/fedora/i'             =>  'Fedora',
        '/centos/i'             =>  'CentOS',
        '/mageia/i'             =>  'Mageia',
        '/vercel/i'             =>  'Vercel',
        '/netlify/i'            =>  'Netlify',
        '/alpine/i'             =>  'Alpine',
        '/opensuse/i'           =>  'OpenSUSE',
        '/macos/i'              =>  'MacOS',
        '/freebsd/i'            =>  'FreeBSD',
        '/openbsd/i'            =>  'OpenBSD',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile',

    );

    if ($user_agent) {

        foreach ($os_array as $regex => $value) {

            if (preg_match($regex, $user_agent)) {

                $unknown_os = $value;
            }
        }
    } else {
        $unknown_os = php_uname('s');
    }


    return $unknown_os;
}

/**
 * get_operating_system
 *
 * @author Soumitra roytuts2014@gmail.com
 * @see https://roytuts.com/detect-operating-system-using-php/
 * @return string
 * 
 */
function get_operating_system()
{
    $u_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $operating_system = 'Unknown Operating System';

    //Get the operating_system name
    if ($u_agent) {
        if (preg_match('/linux/i', $u_agent)) {
            $operating_system = 'Linux';
        } elseif (preg_match('/macintosh|mac os x|mac_powerpc/i', $u_agent)) {
            $operating_system = 'Mac';
        } elseif (preg_match('/windows|win32|win98|win95|win16/i', $u_agent)) {
            $operating_system = 'Windows';
        } elseif (preg_match('/ubuntu/i', $u_agent)) {
            $operating_system = 'Ubuntu';
        } elseif (preg_match('/iphone/i', $u_agent)) {
            $operating_system = 'IPhone';
        } elseif (preg_match('/ipod/i', $u_agent)) {
            $operating_system = 'IPod';
        } elseif (preg_match('/ipad/i', $u_agent)) {
            $operating_system = 'IPad';
        } elseif (preg_match('/android/i', $u_agent)) {
            $operating_system = 'Android';
        } elseif (preg_match('/blackberry/i', $u_agent)) {
            $operating_system = 'Blackberry';
        } elseif (preg_match('/webos/i', $u_agent)) {
            $operating_system = 'Mobile';
        }
    } else {
        $operating_system = php_uname('s');
    }

    return $operating_system;
}

/**
 * get_linux_distro
 *
 * @category function
 * @see https://stackoverflow.com/questions/26862978/get-the-linux-distribution-name-in-php
 * @return mixed
 * 
 */
function get_linux_distro()
{

    if (strtolower(substr(PHP_OS, 0, 5)) == 'linux') {

        $vars = array();
        $files = function_exists('glob') ? glob('/etc/*-release') : "";

        foreach ($files as $file) {

            $lines = array_filter(array_map(function ($line) {

                // split value from key
                $parts = explode('=', $line);

                // makes sure that "useless" lines are ignored (together with array_filter)
                if (count($parts) !== 2) {
                    return false;
                }

                // remove quotes, if the value is quoted
                $parts[1] = str_replace(array('"', "'"), '', $parts[1]);
                return $parts;
            }, file($file)));

            foreach ($lines as $line) {
                $vars[$line[0]] = $line[1];
            }
        }

        return $vars;
    }
}
