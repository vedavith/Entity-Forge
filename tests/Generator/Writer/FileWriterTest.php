<?php

namespace Tests\Generator\Writer;

use EntityForge\Generator\Writer\FileWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileWriterTest extends TestCase
{
    private FileWriter $writer;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->writer = new FileWriter();
        $this->tmpDir = sys_get_temp_dir() . '/ef_writer_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    public function test_writes_file_content(): void
    {
        $path = $this->tmpDir . '/out.php';

        $this->writer->write($path, '<?php echo "hello";');

        $this->assertSame('<?php echo "hello";', file_get_contents($path));
    }

    public function test_creates_nested_directories(): void
    {
        $path = $this->tmpDir . '/deep/nested/dir/file.php';

        $this->writer->write($path, 'content');

        $this->assertFileExists($path);
    }

    public function test_overwrites_existing_file_by_default(): void
    {
        $path = $this->tmpDir . '/file.php';
        file_put_contents($path, 'old');

        $this->writer->write($path, 'new');

        $this->assertSame('new', file_get_contents($path));
    }

    public function test_throws_when_overwrite_false_and_file_exists(): void
    {
        $path = $this->tmpDir . '/file.php';
        file_put_contents($path, 'existing');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        $this->writer->write($path, 'new', false);
    }

    public function test_does_not_throw_when_overwrite_false_and_file_not_exists(): void
    {
        $path = $this->tmpDir . '/new_file.php';

        $this->writer->write($path, 'content', false);

        $this->assertFileExists($path);
    }
}
