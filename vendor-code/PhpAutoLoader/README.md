PhpAutoLoader
=============
Generic module for automatically loading classes by path and filename.

Setup
=============
Copy PhpAutoLoadingConfig.php.dist to PhpAutoLoaderConfig.php. Edit
that file to set path(s) where your class definition files can be
found.

Usage
=============
PhpAutoLoader expects class names to match file names - i.e., a 
file name Foo.php should contain the class definition for Foo.

Todo
=============
Add more efficient key-value storage options (APC, memcache, NoSQL, etc)
Work on listModules() method
