<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Handler\AdminActionCommand;
use Scriptlog\Handler\AdminActionRegistry;

class AdminActionRegistryTest extends TestCase
{
    private AdminActionRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new AdminActionRegistry();
    }

    public function testRegisterAndExecute(): void
    {
        $command = $this->createMock(AdminActionCommand::class);
        $command->expects($this->once())
            ->method('execute')
            ->with(['key' => 'value']);

        $this->registry->register('TEST_ACTION', $command);
        $this->assertTrue($this->registry->has('TEST_ACTION'));
        $this->registry->execute('TEST_ACTION', ['key' => 'value']);
    }

    public function testHasReturnsFalseForUnknownAction(): void
    {
        $this->assertFalse($this->registry->has('NONEXISTENT'));
    }

    public function testExecuteThrowsExceptionForUnknownAction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No command registered for action: NONEXISTENT');
        $this->registry->execute('NONEXISTENT', []);
    }

    public function testRegisterOverwritesExisting(): void
    {
        $command1 = $this->createMock(AdminActionCommand::class);
        $command2 = $this->createMock(AdminActionCommand::class);
        $command2->expects($this->once())->method('execute');

        $this->registry->register('ACTION', $command1);
        $this->registry->register('ACTION', $command2);
        $this->registry->execute('ACTION', []);
    }

    public function testMultipleCommands(): void
    {
        $cmdA = $this->createMock(AdminActionCommand::class);
        $cmdB = $this->createMock(AdminActionCommand::class);
        $cmdA->expects($this->once())->method('execute');
        $cmdB->expects($this->once())->method('execute');

        $this->registry->register('ACTION_A', $cmdA);
        $this->registry->register('ACTION_B', $cmdB);

        $this->assertTrue($this->registry->has('ACTION_A'));
        $this->assertTrue($this->registry->has('ACTION_B'));
        $this->registry->execute('ACTION_A', []);
        $this->registry->execute('ACTION_B', []);
    }
}
