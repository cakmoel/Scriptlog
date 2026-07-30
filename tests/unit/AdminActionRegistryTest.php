<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class AdminActionRegistryTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new \Scriptlog\Handler\AdminActionRegistry();
    }

    public function testHasReturnsFalseForUnregisteredAction(): void
    {
        $this->assertFalse($this->registry->has('unknown_action'));
    }

    public function testRegisterAndHas(): void
    {
        $command = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);
        $this->registry->register('test_action', $command);
        $this->assertTrue($this->registry->has('test_action'));
    }

    public function testExecuteDelegatesToCommand(): void
    {
        $command = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(['key' => 'value']);

        $this->registry->register('test_action', $command);
        $this->registry->execute('test_action', ['key' => 'value']);
    }

    public function testExecuteThrowsExceptionForUnregisteredAction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->registry->execute('unknown_action', []);
    }

    public function testMultipleRegistrations(): void
    {
        $cmd1 = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);
        $cmd2 = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);

        $this->registry->register('action_a', $cmd1);
        $this->registry->register('action_b', $cmd2);

        $this->assertTrue($this->registry->has('action_a'));
        $this->assertTrue($this->registry->has('action_b'));
    }

    public function testExecuteWithCorrectCommand(): void
    {
        $cmd1 = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);
        $cmd2 = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);

        $cmd2->expects($this->once())->method('execute');
        $cmd1->expects($this->never())->method('execute');

        $this->registry->register('action_a', $cmd1);
        $this->registry->register('action_b', $cmd2);

        $this->registry->execute('action_b', []);
    }

    public function testHasAfterExecute(): void
    {
        $command = $this->createMock(\Scriptlog\Handler\AdminActionCommand::class);
        $this->registry->register('test', $command);
        $this->registry->execute('test', []);
        $this->assertTrue($this->registry->has('test'));
    }
}
