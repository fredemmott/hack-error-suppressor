<?hh

namespace FredEmmott\HackErrorSuppressor;

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {
  const string EXPECTED_ERROR = 'Hack type error: Invalid return type';
  const string CALL_GOOD_CODE = 'require_once("good_hack_file.php"); good();';

  private ?string $workDir;
  public function setUp(): void {
    $old_umask = umask(0077);
    $root = sprintf(
      '%s/HES_%s',
      sys_get_temp_dir(),
      bin2hex(random_bytes(16)),
    );
    mkdir($root);
    $this->workDir = $root;

    touch($root.'/.hhconfig');
    file_put_contents(
      $root.'/bad_hack_file.php',
      '<?hh function bad(): int { return "not an int"; }',
    );
    file_put_contents(
      $root.'/good_hack_file.php',
      '<?hh function good(): void { print("Good!\n"); }',
    );
    umask($old_umask);
  }

  public function tearDown(): void {
    $work_dir = $this->workDir;
    invariant(
      $work_dir !== null,
      'Trying to tear down without having setUp',
    );

    exec('rm -rf --preserve-root '.escapeshellarg($work_dir));
    $this->workDir = null;
  }

  protected function getWorkDir(): string {
    $work_dir = $this->workDir;
    invariant($work_dir !== null, "trying to get work dir without setup");
    return $work_dir;
  }

  private function getHeader(): string {
    return sprintf(
      "<?php\n".
      "require_once('%s');\n".
      "use %s;\n".
      "use %s;\n\n",
      __DIR__.'/../vendor/autoload.php',
      /* HH_IGNORE_ERROR[2049] PHP class */
      \FredEmmott\HackErrorSuppressor::class,
      /* HH_IGNORE_ERROR[2049] PHP class */
      \FredEmmott\ScopedHackErrorSuppressor::class,
    );
  }

  protected function runCode(string $code): (string, int)  {
    $root = $this->workDir;
    invariant($root !== null, 'Calling runCode without setup');
    $test_file = tempnam($root, 'testfile_');
    file_put_contents($test_file, $this->getHeader().$code);

    $command = sprintf(
      '%s -d hhvm.jit=0 -d hhvm.hack.lang.look_for_typechecker=1 %s @>&1',
      PHP_BINARY,
      escapeshellarg($test_file),
    );
    $output = array();
    $exit_code = -1;
    exec($command, $output, $exit_code);
    unlink($test_file);
    return tuple(trim(implode("\n", $output)), $exit_code);
  }

  protected function assertHasExpectedError(
    string $output,
    int $exit_code,
  ): void {
    $this->assertContains(
      self::EXPECTED_ERROR,
      $output,
    );
    $this->assertNotEquals(0, $exit_code);
  }

  protected function assertHasGoodOutput(
    string $output,
    int $exit_code,
  ): void {
    $this->assertSame('Good!', $output);
    $this->assertSame(0, $exit_code);
  }
}
