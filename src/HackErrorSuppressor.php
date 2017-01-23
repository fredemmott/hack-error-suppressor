<?php

namespace FredEmmott;

final class HackErrorSuppressor {
  private $oldHandler;
  private $enabled = false;

  private static bool $cliOnly = true;

  private static function checkSAPI() {
    if (!self::$cliOnly) {
      return;
    }
    \HH\invariant(
      static::getSAPI() === 'cli',
      'Got called from non-CLI SAPI "%s"; fix your errors!',
      static::getSAPI()
    );
  }

  protected static function getSAPI(): \HH\string {
    return php_sapi_name();
  }

  public static function allowRealRequestsAgainstBrokenCode() {
    self::$cliOnly = false;
  }

  public function enable() {
    self::checkSAPI();
    \HH\invariant(
      $this->enabled === false,
      'Already enabled'
    );
    $this->oldHandler = \set_error_handler(
      function(...$args) { $this->handleError(...$args); }
    );
    $this->enabled = true;
  }

  public function disable() {
    \HH\invariant(
      $this->enabled === true,
      'Not enabled'
    );

    // https://github.com/facebook/hhvm/issues/7613
    if ($this->oldHandler === null) {
      \restore_error_handler();
    } else {
      \set_error_handler($this->oldHandler);
    }
    $this->enabled = false;
  }

  private function callOldHandler(...$args) {
    $handler = $this->oldHandler;
    if ($handler === null) {
      return false;
    }
    return $handler(...$args);
  }

  private function handleError(
    ...$args
  ) {
    // The position of the backtrace varies for these :/
    $bt_args = array_values(array_filter(
      $args,
      function($arg) {
        return is_array($arg) && array_key_exists('function', $arg[0] ?? []);
      }
    ));
    if (count($bt_args) !== 1) {
      return $this->callOldHandler(...$args);
    }

    $backtrace = $bt_args[0];
    foreach ($backtrace as $frame) {
      $func = $frame['function'] ?? null;
      if ($func === 'HH\\Client\\typecheck_and_error') {
        return true;
      }
    }

    return $this->callOldHandler(...$args);
  }
}
