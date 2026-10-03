<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_domain_does_not_depend_on_illuminate(): void
    {
        $domainPath = realpath(__DIR__ . '/../../app/Domain');
        $this->assertNotFalse($domainPath);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($domainPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString(
                    'Illuminate\\',
                    $content,
                    "R-01 Violation: {$file->getFilename()} contains Illuminate references."
                );
            }
        }
    }

    public function test_application_does_not_depend_on_infrastructure_or_illuminate(): void
    {
        $appPath = realpath(__DIR__ . '/../../app/Application');
        $this->assertNotFalse($appPath);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString(
                    'App\\Infrastructure\\',
                    $content,
                    "R-02 Violation: {$file->getFilename()} imports Infrastructure."
                );
                $this->assertStringNotContainsString(
                    'Illuminate\\',
                    $content,
                    "R-02 Violation: {$file->getFilename()} imports Illuminate."
                );
            }
        }
    }

    public function test_presentation_does_not_import_infrastructure(): void
    {
        $presentationPath = realpath(__DIR__ . '/../../app/Presentation');
        $this->assertNotFalse($presentationPath);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($presentationPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString(
                    'App\\Infrastructure\\',
                    $content,
                    "R-03 Violation: {$file->getFilename()} imports Infrastructure directly."
                );
            }
        }
    }
}
