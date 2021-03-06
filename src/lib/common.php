<?php

define('DS', DIRECTORY_SEPARATOR);
define('APP_TITLE', 'Scriptlog');
define('APP_CODENAME', 'Maleo Senkawor');
define('APP_VERSION', '1.0');
define('APP_ROOT', dirname(dirname(__FILE__)) . DS);
define('APP_ADMIN', 'admin');
define('APP_PUBLIC', 'public');
define('APP_LIBRARY', 'lib');
define('APP_CACHE', false);
define('APP_FILE_SIZE', 1048576);
define('APP_IMAGE', APP_PUBLIC . DS . 'files' . DS . 'pictures' . DS);
define('APP_IMAGE_LARGE', APP_IMAGE.'large'.DS);
define('APP_IMAGE_MEDIUM', APP_IMAGE.'medium'.DS);
define('APP_IMAGE_SMALL', APP_IMAGE.'small'.DS);
define('APP_AUDIO', APP_PUBLIC . DS . 'files' . DS . 'audio' . DS);
define('APP_VIDEO', APP_PUBLIC . DS . 'files' . DS . 'video' . DS);
define('APP_DOCUMENT', APP_PUBLIC . DS . 'files' . DS . 'docs' . DS);
define('APP_THEME', APP_PUBLIC . DS . 'themes' . DS);
define('APP_PLUGIN', APP_ADMIN . DS . 'plugins' . DS);
define('APP_DEVELOPMENT', true);
define('SCRIPTLOG', hash_hmac('sha256', APP_TITLE.':'.APP_CODENAME.mt_rand(1, 1000), hash('sha256', uniqid().'M4Le053Nk4WoR!@#{>}>01[:+]-07|=_$%^&*(id)')));
