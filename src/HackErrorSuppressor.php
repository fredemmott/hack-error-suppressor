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
    $errno,
    $errstr,
    $errfile = null,
    $errline = null,
    $errcontext = null
  ) {
    if ((!is_array($errcontext)) || count($errcontext) === 0) {
      return $this->callOldHandler(...func_get_args());
    }

    $blame_func = $errcontext[0]['function'] ?? null;
    if ($blame_func === 'HH\\Client\\typecheck_and_error') {
      return true;
    }

    return $this->callOldHandler(...func_get_args());
  }
}
