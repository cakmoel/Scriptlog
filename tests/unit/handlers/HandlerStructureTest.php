<?php

use PHPUnit\Framework\TestCase;

class HandlerStructureTest extends TestCase
{
    private array $handlerClasses = [
        'PostHandler', 'PageHandler', 'CategoryHandler', 'TagHandler',
        'ArchiveHandler', 'BlogHandler', 'PrivacyHandler', 'DownloadHandler', 'HomeHandler'
    ];

    public function testAllHandlerFilesExist(): void
    {
        foreach ($this->handlerClasses as $class) {
            $file = __DIR__ . '/../../../lib/handler/' . $class . '.php';
            $this->assertFileExists($file, "Handler file $class.php does not exist");
        }
    }

    public function testAllHandlerFilesContainClass(): void
    {
        foreach ($this->handlerClasses as $class) {
            $file = __DIR__ . '/../../../lib/handler/' . $class . '.php';
            $content = file_get_contents($file);
            $this->assertStringContainsString(
                "class $class",
                $content,
                "File $class.php does not contain class $class"
            );
            $this->assertStringContainsString(
                'implements FrontRequestHandler',
                $content,
                "Class $class does not implement FrontRequestHandler"
            );
            $this->assertStringContainsString(
                'defined(\'SCRIPTLOG\') || die',
                $content,
                "File $class.php is missing security guard"
            );
        }
    }

    public function testAllHandlerFilesHaveHandleMethod(): void
    {
        foreach ($this->handlerClasses as $class) {
            $file = __DIR__ . '/../../../lib/handler/' . $class . '.php';
            $content = file_get_contents($file);
            $this->assertStringContainsString(
                'function handle(array $params)',
                $content,
                "Class $class does not declare handle method"
            );
        }
    }
}
