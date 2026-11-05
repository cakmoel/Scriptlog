<?php

use PHPUnit\Framework\TestCase;

/**
 * LicenseFileTest - guards the repository's license metadata.
 *
 * GitHub detects every license-like file at the project root. Keeping a
 * single canonical license file prevents the repository from being reported
 * as dual-licensed when the files actually contain the same MIT text.
 */
class LicenseFileTest extends TestCase
{
    /**
     * Project root directory.
     */
    private const ROOT = __DIR__ . '/../..';

    /**
     * Filenames GitHub/licensee recognises as a root license document.
     */
    private const LICENSE_FILENAMES = [
        'LICENSE',
        'LICENSE.md',
        'LICENSE.txt',
        'license',
        'license.md',
        'license.txt',
        'LICENCE',
        'LICENCE.md',
        'LICENCE.txt',
        'licence',
        'licence.md',
        'licence.txt',
        'COPYING',
        'COPYING.md',
        'COPYING.txt',
    ];

    /**
     * The repository must expose exactly one root license document.
     */
    public function testRepositoryHasExactlyOneRootLicenseFile(): void
    {
        $found = [];

        foreach (self::LICENSE_FILENAMES as $filename) {
            if (is_file(self::ROOT . '/' . $filename)) {
                $found[] = $filename;
            }
        }

        $this->assertSame(
            ['LICENSE.md'],
            $found,
            'Expected exactly one canonical root license file (LICENSE.md).'
        );
    }

    /**
     * The canonical license document must still contain the MIT text.
     */
    public function testCanonicalLicenseFileContainsMitText(): void
    {
        $license = file_get_contents(self::ROOT . '/LICENSE.md');

        $this->assertIsString($license);
        $this->assertStringStartsWith('MIT License', $license);
        $this->assertStringContainsString(
            'Permission is hereby granted, free of charge, to any person obtaining a copy',
            $license
        );
        $this->assertStringContainsString(
            'THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND',
            $license
        );
    }
}
