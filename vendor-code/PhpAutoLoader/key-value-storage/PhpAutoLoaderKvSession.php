<?php

class PhpAutoLoaderKvSession extends PhpAutoLoaderKV
{
    public static function bootstrap()
    {
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli') {
            @session_start();
        }
        if (!isset($_SESSION['PhpAutoLoaderClassMap'])) {
            $_SESSION['PhpAutoLoaderClassMap'] = array();
        } elseif(!is_array($_SESSION['PhpAutoLoaderClassMap'])) {
            $_SESSION['PhpAutoLoaderClassMap'] = array();
        }

        return true;
    }

    public static function get($key)
    {
        if (isset($_SESSION['PhpAutoLoaderClassMap'][$key])) {
            return $_SESSION['PhpAutoLoaderClassMap'][$key];
        } else {
            throw new Exception('No value for key: ' . $key);
        }
    }

    public static function set($key, $value)
    {
        $_SESSION['PhpAutoLoaderClassMap'][$key] = $value;
    }

    public static function delete($key)
    {
        unset($_SERVER['PhpAutoLoaderClassMap'][$key]);

        return true;
    }
}

