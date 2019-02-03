<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestSuite;

require_once 'ColorTest.php';

class AllTests {
    public static function main() {
      try {
        TestRunner::run(self::suite());
      } catch (ReflectionException $e) {
      }
    }

    public static function suite() {
        $suite = new TestSuite('Color');
        $suite->addTestSuite('ColorTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}


