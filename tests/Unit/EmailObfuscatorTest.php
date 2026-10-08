<?php

namespace Tests\Unit;

use App\Support\EmailObfuscator;
use PHPUnit\Framework\TestCase;

class EmailObfuscatorTest extends TestCase
{
    public function test_obfuscates_plain_text_email(): void
    {
        $out = EmailObfuscator::protect('<p>Scrivi a segreteria@comitato.test per informazioni.</p>');

        $this->assertStringNotContainsString('segreteria@comitato.test', $out);
        $this->assertStringContainsString('&#115;&#101;&#103;&#114;&#101;&#116;&#101;&#114;&#105;&#97;&#64;&#99;&#111;&#109;&#105;&#116;&#97;&#116;&#111;&#46;&#116;&#101;&#115;&#116;', $out);
    }

    public function test_obfuscates_email_inside_mailto_href_and_stays_a_valid_link(): void
    {
        $out = EmailObfuscator::protect('<a href="mailto:info@example.com">scrivici</a>');

        $this->assertStringNotContainsString('info@example.com', $out);
        $this->assertStringStartsWith('<a href="mailto:&#105;', $out);
    }

    public function test_obfuscates_multiple_addresses(): void
    {
        $out = EmailObfuscator::protect('a@b.it e anche c@d.com');

        $this->assertStringNotContainsString('a@b.it', $out);
        $this->assertStringNotContainsString('c@d.com', $out);
    }

    public function test_leaves_text_without_email_untouched(): void
    {
        $html = '<p>Nessun indirizzo qui.</p>';

        $this->assertSame($html, EmailObfuscator::protect($html));
    }

    public function test_null_and_empty_pass_through(): void
    {
        $this->assertNull(EmailObfuscator::protect(null));
        $this->assertSame('', EmailObfuscator::protect(''));
    }
}
