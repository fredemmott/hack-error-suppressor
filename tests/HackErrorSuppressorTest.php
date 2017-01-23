<?hh

namespace FredEmmott\HackErrorSuppressor;

use FredEmmott\HackErrorSuppressor;

final class HackErrorSuppressorTest extends BaseTestCase {
  public function testExceptionRaisedIfNotInstantiated(): void {
    list($output, $exit_code) = $this->runCode(self::CALL_GOOD_CODE);
    $this->assertHasExpectedError($output, $exit_code);
  }

  public function testExceptionRaisedIfNotEnabled(): void {
    list($output, $exit_code) = $this->runCode(
      '$x = new HackErrorSuppressor();'.self::CALL_GOOD_CODE,
    );
    $this->assertHasExpectedError($output, $exit_code);
  }

  public function testGoodOutputIfEnabled(): void {
    list($output, $exit_code) = $this->runCode(
      "\$x = new HackErrorSuppressor();\n".
      "\$x->enable();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasGoodOutput($output, $exit_code);
  }

  public function testSuppressesNoHHConfigErrorIfEnabled(): void {
    unlink($this->getWorkDir().'/.hhconfig');
    list($output, $exit_code) = $this->runCode(
      "\$x = new HackErrorSuppressor();\n".
      "\$x->enable();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasGoodOutput($output, $exit_code);
  }


  public function testExceptionRaisedIfEnabledAndDisabled(): void {
    list($output, $exit_code) = $this->runCode(
      "\$x = new HackErrorSuppressor();\n".
      "\$x->enable();\n".
      "\$x->disable();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasExpectedError($output, $exit_code);
  }

  public function testCantEnableTwice(): void {
    /* HH_IGNORE_ERROR[2049] https://github.com/facebook/hhvm/issues/5917 */
    $this->expectException(\HH\InvariantException::class);

    /* HH_IGNORE_ERROR[2049] PHP class */
    $it = new HackErrorSuppressor();
    $it->enable();
    $it->enable();
  }

  public function testCantDisableIfNotEnabled(): void {
    /* HH_IGNORE_ERROR[2049] https://github.com/facebook/hhvm/issues/5917 */
    $this->expectException(\HH\InvariantException::class);

    /* HH_IGNORE_ERROR[2049] PHP class */
    $it = new HackErrorSuppressor();
    $it->disable();
  }

  public function testDelegatesOtherErrors(): void {
    $x = Vector {};
    \set_error_handler(() ==> $x[] = func_get_args());
    /* HH_IGNORE_ERROR[2049] PHP class */
    $it = new HackErrorSuppressor();
    $it->enable();
    trigger_error('Foo');
    $it->disable();

    $this->assertSame(count($x), 1);
  }

  public function testRestoresErrorHandler(): void {
    $x = Vector {};
    \set_error_handler(() ==> $x[] = func_get_args());
    /* HH_IGNORE_ERROR[2049] PHP class */
    $it = new HackErrorSuppressor();
    $it->enable();
    $it->disable();
    trigger_error('Foo');

    $this->assertSame(count($x), 1);
  }

  <<__Memoize>>
  /* HH_IGNORE_ERROR[2049] PHP class */
  private function getMockWithBadSAPI(): HackErrorSuppressor {
    // ... I feel dirty
    $class = 'TestMock_'.bin2hex(random_bytes(4));
    $temp_file = tempnam($this->getWorkDir(), 'TestMock');
    file_put_contents(
      $temp_file,
      "<?hh\n".
      "<<__MockClass>>\n".
      "class $class extends FredEmmott\HackErrorSuppressor {\n".
      "  protected static function getSAPI(): string {\n".
      "    return 'BOGUS TEST SAPI';\n".
      "  }\n".
      "};\n".
      "return new $class();"
    );
    return require($temp_file);
  }

  public function testFailsIfNotCLI(): void {
    /* HH_IGNORE_ERROR[2049] https://github.com/facebook/hhvm/issues/5917 */
    $this->expectException(\HH\InvariantException::class);
    $this->expectExceptionMessage('BOGUS TEST SAPI');

    $this->getMockWithBadSAPI()->enable();
  }

  public function testSucceedsIfNotCLIAndAllowed(): void {
    /* HH_IGNORE_ERROR[2049] PHP class */
    HackErrorSuppressor::allowRealRequestsAgainstBrokenCode();
    $this->getMockWithBadSAPI()->enable();
  }
}
