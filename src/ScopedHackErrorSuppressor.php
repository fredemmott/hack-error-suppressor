<?php

namespace FredEmmott;

final class ScopedHackErrorSuppressor {
  private $impl;

  public function __construct() {
    $this->impl = new HackErrorSuppressor();
    $this->impl->enable();
  }

  public function __destruct() {
    $this->impl->disable();
  }
}
