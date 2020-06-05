<?php

declare(strict_types=1);

namespace PHPGithook\ModuleTester;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PHPGithook\ModuleInterface\PHPGithookCommitMsgInterface;
use PHPGithook\ModuleInterface\PHPGithookPostCommitInterface;
use PHPGithook\ModuleInterface\PHPGithookPreCommitInterface;
use PHPGithook\ModuleInterface\PHPGithookPrepareCommitMsgInterface;
use PHPGithook\ModuleInterface\PHPGithookPrepushInterface;
use PHPGithook\ModuleInterface\PHPGithookSetupInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

abstract class ModuleTester extends TestCase
{
    private Filesystem $fs;

    /**
     * @test
     */
    public function have_setup_file(): void
    {
        if (!$this->getSetupClass()) {
            throw new RuntimeException('Your module MUST have a "Setup.php" file');
        }

        self::assertTrue(true);
    }

    /**
     * @test
     * @depends have_setup_file
     */
    public function have_commit_msg_file(): void
    {
        $this->moduleTest(PHPGithookCommitMsgInterface::class, 'commit messages');
    }

    /**
     * @test
     * @depends have_setup_file
     */
    public function have_post_commit_file(): void
    {
        $this->moduleTest(PHPGithookPostCommitInterface::class, 'post commits');
    }

    /**
     * @test
     * @depends have_setup_file
     */
    public function have_pre_commit_file(): void
    {
        $this->moduleTest(PHPGithookPreCommitInterface::class, 'pre commits');
    }

    /**
     * @test
     * @depends have_setup_file
     */
    public function have_prepare_commit_file(): void
    {
        $this->moduleTest(PHPGithookPrepareCommitMsgInterface::class, 'prepare commits');
    }

    /**
     * @test
     * @depends have_setup_file
     */
    public function have_pre_push_file(): void
    {
        $this->moduleTest(PHPGithookPrepushInterface::class, 'pre push');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $adapter = new Local($this->directoryToModule());
        $this->fs = new Filesystem($adapter);
    }

    /**
     * Return the directory to your module.
     */
    abstract protected function directoryToModule(): string;

    private function getSetupClass(): ?PHPGithookSetupInterface
    {
        $files = $this->fs->listContents('', true);
        foreach ($files as $file) {
            if ('dir' === $file['type']) {
                continue;
            }

            if ('Setup.php' === $file['basename']) {
                self::assertTrue(true);
                $adapter = $this->fs->getAdapter();
                if (method_exists($adapter, 'getPathPrefix')) {
                    $fullpath = $adapter->getPathPrefix().'/'.$file['path'];
                    $className = ClassNamespaceResolver::getClassFullNameFromFile($fullpath);
                    if (class_exists($className)) {
                        return new $className();
                    }
                }
            }
        }

        return null;
    }

    private function moduleTest(string $interface, string $type): void
    {
        if (!$setupClass = $this->getSetupClass()) {
            throw new RuntimeException('Your module MUST have a "Setup.php" file');
        }

        $haveClass = false;
        if ($setupClass instanceof $interface) {
            self::assertTrue(true);
            $haveClass = true;
        }

        foreach ($setupClass->classes() as $class) {
            $classObj = new $class();
            if ($classObj instanceof $interface) {
                self::assertTrue(true);
                $haveClass = true;
            }
        }

        if (!$haveClass) {
            $this->addWarning(
                sprintf(
                    "Your module does not have a method for %s\nimplement '%s' if you want to have it",
                    $type,
                    $interface
                )
            );
        }
    }
}
