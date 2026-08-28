<?php

declare(strict_types=1);

namespace Grav\Plugin\Admonitions\Tests\Unit;

use Grav\Plugin\Admonitions\AdmonitionsExtension;
use PHPUnit\Framework\TestCase;

/** Tests the public Grav block-handler contract without a full site fixture. */
final class AdmonitionsExtensionTest extends TestCase
{
    public function testRecognizesRegularAndCollapsibleSyntax(): void
    {
        $extension = new AdmonitionsExtension();
        $regular = $extension->block(['text' => '!!! note']);
        $closed = $extension->block(['text' => '??? warning']);
        $open = $extension->block(['text' => '???+ tip']);

        self::assertTrue((bool) ($regular['admonition'] ?? false));
        self::assertStringContainsString('note', json_encode($regular, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('<svg', json_encode($regular, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('open', json_encode($closed, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('open', json_encode($open, JSON_THROW_ON_ERROR));
    }

    public function testCustomAndEmptyTitlesAreRepresentedSafely(): void
    {
        $extension = new AdmonitionsExtension();
        $custom = $extension->block(['text' => '!!! info "**Read** &lt;safe&gt;"']);
        $empty = $extension->block(['text' => '!!! note ""']);
        $details = $extension->block(['text' => '??? note ""']);

        self::assertStringContainsString('**Read**', json_encode($custom, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('admonition--title', json_encode($empty, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('Note', json_encode($details, JSON_THROW_ON_ERROR));
    }

    public function testRejectsMalformedSyntax(): void
    {
        $extension = new AdmonitionsExtension();
        self::assertNull($extension->block(['text' => '!!!']));
        self::assertNull($extension->block(['text' => '!!! note unquoted title']));
        self::assertNull($extension->block(['text' => '???+']));
    }

    public function testUnknownTypeUsesNeutralClassAndConfiguredColorsAreSafe(): void
    {
        $extension = new AdmonitionsExtension(['types' => [
            'release' => ['label' => 'Release', 'class' => 'release', 'color' => '#123abc'],
            'unsafe' => ['label' => 'Unsafe', 'class' => 'unsafe', 'color' => 'red; background: black'],
        ]]);
        $unknown = json_encode($extension->block(['text' => '!!! made-up']), JSON_THROW_ON_ERROR);
        $release = json_encode($extension->block(['text' => '!!! release']), JSON_THROW_ON_ERROR);
        $unsafe = json_encode($extension->block(['text' => '!!! unsafe']), JSON_THROW_ON_ERROR);

        self::assertStringContainsString('admonition--unknown', $unknown);
        self::assertStringContainsString('--admonition-accent: #123abc', $release);
        self::assertStringNotContainsString('background: black', $unsafe);
    }

    public function testFourSpaceBodyAndDedentControlContinuation(): void
    {
        $extension = new AdmonitionsExtension();
        $block = $extension->block(['text' => '!!! note']);
        self::assertIsArray($block);
        $continued = $extension->blockContinue(['indent' => 4, 'text' => 'Inside', 'body' => '    Inside'], $block);

        self::assertIsArray($continued);
        self::assertStringContainsString('Inside', json_encode($continued, JSON_THROW_ON_ERROR));
        self::assertNull($extension->blockContinue(['indent' => 0, 'text' => 'Outside'], $continued));
    }
}
