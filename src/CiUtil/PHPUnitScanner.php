<?php
namespace CiUtil;
use Symfony\Component\Finder\Finder;

/**
 * Search for PHPUnit test cases
 */
class PHPUnitScanner {
  /**
   * @return array<string> class names
   */
  static function _findTestClasses($path) {
    $origClasses = get_declared_classes();
    require_once $path;
    $newClasses = get_declared_classes();

    return preg_grep('/Test$/', array_diff(
      $newClasses,
      $origClasses
    ));
  }

  /**
   * @return array (string $file => string $class)
   */
  static function findTestClasses($paths) {
    $testClasses = array();
    $finder = new Finder();

    foreach ($paths as $path) {
      if (is_dir($path)) {
        foreach ($finder->files()->in($paths)->name('*Test.php') as $file) {
          $testClass = self::_findTestClasses((string) $file);
          if (count($testClass) > 1) {
            throw new Exception("Too many classes in $file");
          }
          $testClasses[(string) $file] = array_shift($testClass);
        }
      }
      elseif (is_file($path)) {
        $testClass = self::_findTestClasses($path);
        if (count($testClass) > 1) {
          throw new Exception("Too many classes in $path");
        }
        $testClasses[$path] = array_shift($testClass);
      }
    }

    return $testClasses;
  }

  /**
   * @param array $testClasses
   * @return array each element is an array with keys:
   *   - file: string
   *   - class: string
   *   - method: string
   */
  static function findTestsByPath($paths) {
    $r = array();
    $testClasses = self::findTestClasses($paths);
    foreach ($testClasses as $testFile => $testClass) {
      $clazz = new \ReflectionClass($testClass);
      foreach ($clazz->getMethods() as $method) {
        if (preg_match('/^test/', $method->name)) {
          $r[] = array(
            'file' => $testFile,
            'class' => $testClass,
            'method' => $method->name
          );
        }
      }
    }
    return $r;
  }
}