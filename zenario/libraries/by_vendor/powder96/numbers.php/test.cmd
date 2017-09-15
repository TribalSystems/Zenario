@echo off

REM Path to PHP executable.
set PATH_PHP_EXEC=Z:\usr\local\php5\php.exe

REM Path to Composer PHAR. http://getcomposer.org/composer.phar
set PATH_COMPOSER_PHAR=Z:\tools\composer.phar

REM Path to PHPUnit PHAR. http://pear.phpunit.de/get/phpunit.phar
set PATH_PHPUNIT_PHAR=Z:\tools\phpunit.phar

REM Install the dependencies, if we have to.
if not exist vendor\autoload.php %PATH_PHP_EXEC% %PATH_COMPOSER_PHAR% install

REM Run tests.
%PATH_PHP_EXEC% %PATH_PHPUNIT_PHAR% --configuration phpunit.xml.dist
echo.
pause