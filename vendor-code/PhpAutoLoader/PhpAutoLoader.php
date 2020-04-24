<?php

if (!class_exists('PhpAutoLoaderConfig')) {
    require(dirname(__FILE__) . '/PhpAutoLoaderConfig.php');
}
if (!class_exists('PhpAutoLoaderKV')) {
    require(dirname(__FILE__) . '/key-value-storage/PhpAutoLoaderKV.php');
}

class PhpAutoLoader 
{

    private static $storage = false;

    /**
      Initialize session to retain class
      definition info.
    */
    static public function init()
    {
        // already initialized
        if (self::$storage) {
            return;
        }

        $config = PhpAutoLoaderConfig::getConfig();
        $cache_class = $config['cache'];
        if (!class_exists($cache_class, false) && file_exists(dirname(__FILE__) . '/key-value-storage/' . $cache_class . '.php')) {
            include(dirname(__FILE__) . '/key-value-storage/' . $cache_class . '.php');
        }

        if (!class_exists($cache_class, false)) {
            // no definition file or missing class definition
            // failover to simple array
            $cache_class = 'PhpAutoLoaderKV';
        }

        $ready = $cache_class::bootstrap();
        if (!$ready) {
            // some required component is missing
            // failover to simple array
            $cache_class = 'PhpAutoLoaderKV';
            $cache_class::bootstrap(); 
        }

        self::$storage = $cache_class;
    }

    /**
      Load definition for given class
      @param $name the class name
    */
    static public function loadClass($name)
    {
        // class already defined. someone may have
        // called this manually & mistakenly
        if (class_exists($name, false)) {
            return false;
        }

        try {
            // temp; PHP chokes on two :: operators in one line
            $s = self::$storage;
            $mapped_file = $s::get($name);
            // if class is known in the map, include its file
            // otherwise search for an appropriate file
            if (file_exists($mapped_file)) {
                include_once($mapped_file);
                if (!class_exists($name, false)) {
                    // mapped file does not contain class definition
                    $s::delete($name);
                } else {
                    // class found; no need to search for it
                    return true;
                }
            } else {
                // mapped file disappeared
                $s::delete($name);
            }
        } catch (Exception $ex) {
            // no entry for this class name. not
            // a critical error. continue to search
        }

        $config = PhpAutoLoaderConfig::getConfig();
        /** 
          Search plugin directories first if properly
          configured
        */
        if ($config['plugin_class'] != '') {
            $plugin_mode = true;
            $skip_plugins = false;
            if ($name == $config['plugin_class']) {
                $plugin_mode = false;
            } else if (!class_exists($config['plugin_class'])) {
                $skip_plugins = true;
            } else if (!is_callable(array($config['plugin_class'], 'isEnabled'))) {
                $skip_plugins = true;
            } else if (!is_callable(array($config['plugin_class'], 'memberOf'))) {
                $skip_plugins = true;
            }

            if (!$skip_plugins) {
                foreach($config['directories']['plugin'] as $path) {
                    if ($path[0] != '/') {
                        $path = dirname(__FILE__) . '/' . $path;
                    }
                    $file = self::findClass($name, $path, $plugin_mode); 
                    if ($file !== false) {
                        include_once($file);

                        return true;
                    }
                }
            }
        }

        /**
          Now search non-plugin directories:
          1. Project directories
          2. Vendor directories
        */
        $all_directories = array_merge($config['directories']['project'], $config['directories']['vendor']);
        foreach($all_directories as $path) {
            if ($path[0] != '/') {
                $path = dirname(__FILE__) . '/' . $path;
            }
            $file = self::findClass($name, $path, false);
            if ($file !== false) {
                include_once($file);

                return true;
            }
        }

        // class wasn't found
        return false;
    }

    /**
      Search for class in given path
      @param $name the class name
      @param $path path to search
      @param $plugin_mode [boolean] check if files are part of an enabled plugin
      @return A filename or false
    */
    static private function findClass($name, $path, $plugin_mode=false)
    {
        $config = PhpAutoLoaderConfig::getConfig();
        $plugin_class = $config['plugin_class'];
        if (!is_dir($path)) {
            return false;
        } else if ($name == 'index') {
            return false;
        }
        // temp; PHP chokes on two :: operators in one line
        $s = self::$storage;

        $dh = opendir($path);
        while($dh && ($file=readdir($dh)) !== false) {
            if ($file[0] == ".") continue;
            if ($file == 'noauto') continue;
            if ($file == 'index.php') continue;
            $fullname = realpath($path.'/'.$file);
            if (is_dir($fullname)) {
                // check again exclude patterns
                foreach($config['exclude']['directories'] as $pattern) {
                    if (preg_match($pattern, $file)) {
                        continue 2;
                    }
                }
                // recurse looking for file
                $file = self::findClass($name, $fullname);
                if ($file !== false) { 
                    return $file;
                }
            } else if (substr($file,-4) == '.php') {
                // check again exclude patterns
                foreach($config['exclude']['files'] as $pattern) {
                    if (preg_match($pattern, $file)) {
                        continue 2;
                    }
                }

                // enforce plugin rules if applicable
                if ($plugin_mode) {
                    $belongs_to = $plugin_class::memberOf($fullname);
                    if ($belongs_to === false || !$plugin_class::isEnabled($belongs_to)) {
                        continue;
                    }
                }

                // map all PHP files as long as we're searching
                // but only return if the correct file is found
                $class = substr($file,0,strlen($file)-4);
                $s::set($class, $fullname);
                if ($class == $name) {
                    return $fullname;
                }
            }
        }

        return false;
    }

    /**
      Get a list of all available classes implementing a given
      base class
      @param $base_class [string] name of base class
      @param $include_base [boolean] include base class name in the result set
        [optional, default false]
      @return [array] of [string] class names
    */
    static public function listModules($base_class, $include_base=false)
    {
        $config = PhpAutoLoaderConfig::getConfig();
        $directories = $config['directories']['project'];

        // recursive search
        $search = function($path) use (&$search, &$config) {
            if (is_file($path) && substr($path,-4)=='.php') {
                return array($path);
            } elseif (is_dir($path)) {
                $dh = opendir($path);
                $ret = array();
                while( ($file=readdir($dh)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    if ($file == 'noauto') continue;
                    if ($file == 'index.php') continue;
                    foreach($config['exclude']['directories'] as $pattern) {
                        if (preg_match($pattern, $file)) {
                            continue 2;
                        }
                    }
                    $ret = array_merge($ret, $search($path.'/'.$file));
                }
                return $ret;
            }
            return array();
        };

        $files = array();
        foreach($directories as $dir) {
            $files = array_merge($files, $search($dir));
        }

        $ret = array();
        foreach($files as $file) {
            foreach($config['exclude']['files'] as $pattern) {
                if (preg_match($pattern, $file)) {
                    continue 2;
                }
            }
            $class = substr(basename($file),0,strlen(basename($file))-4);
            // matched base class
            if ($class === $base_class) {
                if ($include_base) {
                    $ret[] = $class;
                }
                continue;
            }
            
            // almost certainly not a class definition
            if ($class == 'index') {
                continue;
            }

            // verify class exists
            ob_start();
            include_once($file);
            ob_end_clean();

            if (!class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, $base_class)) {
                $ret[] = $class;
            }
        }

        return $ret;
    }
}

PhpAutoLoader::init();
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('PhpAutoLoader','loadClass'));
} else {
    function __autoload($name)
    {
        PhpAutoLoader::loadClass($name);
    }
}
if (file_exists(dirname(__FILE__) . '/../../vendor/autoload.php')) {
    include(dirname(__FILE__) . '/../../vendor/autoload.php');
}

$tmp_config = PhpAutoLoaderConfig::getConfig();
foreach($tmp_config['globals'] as $file) {
    if ($file[0] != '/') {
        $file = dirname(__FILE__) . '/' . $file;
    }
    include($file);
}

