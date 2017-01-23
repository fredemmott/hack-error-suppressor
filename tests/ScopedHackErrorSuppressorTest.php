<?hh

namespace FredEmmott\HackErrorSuppressor;

use FredEmmott\HackErrorSuppressor;

final class ScopedHackErrorSuppressorTest extends BaseTestCase {
  public function testGoodOutputIfInScope(): void {
    list($output, $exit_code) = $this->runCode(
      "\$x = new ScopedHackErrorSuppressor();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasGoodOutput($output, $exit_code);
  }

  public function testExceptionRaisedIfNotRetained(): void {
    list($output, $exit_code) = $this->runCode(
      "new ScopedHackErrorSuppressor();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasExpectedError($output, $exit_code);
  }

  public function testExceptionRaisedIfNotOutOfScope(): void {
    list($output, $exit_code) = $this->runCode(
      "function main() { \$x = new ScopedHackErrorSuppressor(); }\n".
      "main();\n".
      self::CALL_GOOD_CODE,
    );
    $this->assertHasExpectedError($output, $exit_code);
  }
}
