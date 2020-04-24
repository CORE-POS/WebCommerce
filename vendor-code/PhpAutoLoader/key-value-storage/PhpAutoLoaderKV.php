<?php

class PhpAutoLoaderKV
{
    /**
      Simple array storage
    */
    private static $storage;

    /**
      Do any necessary initialization.
      @return [boolean] success / failure

      Primary purpose of this method is to check
      for required functionality like a PHP
      extension that may not be installed so
      the system can failover to a different
      key-value store. 
    */
    public static function bootstrap()
    {
        self::$storage = array();

        return true;
    }

    public static function get($key)
    {
        if (isset(self::$storage[$key])) {
            return self::$storage[$key];
        } else {
            throw new Exception('No value for key: ' . $key);
        }
    }

    public static function set($key, $value)
    {
        self::$storage[$key] = $value;
    }

    public static function delete($key)
    {
        unset(self::$storage[$key]);

        return true;
    }
}
