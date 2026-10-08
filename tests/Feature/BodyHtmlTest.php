<?php

namespace Tests\Feature;

use App\Support\BodyHtml;
use Tests\TestCase;

class BodyHtmlTest extends TestCase
{
    public function test_keeps_plain_editorial_markup(): void
    {
        $html = '<p>Ciao <strong>mondo</strong></p><ul><li>uno</li></ul>';

        $this->assertSame($html, BodyHtml::clean($html));
    }

    public function test_strips_script_and_event_handlers(): void
    {
        $clean = BodyHtml::clean('<p onclick="steal()">x</p><script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', (string) $clean);
        $this->assertStringNotContainsString('onclick', (string) $clean);
    }

    public function test_strips_forms(): void
    {
        $clean = BodyHtml::clean('<form action="https://evil.test"><input name="pw"></form>');

        $this->assertStringNotContainsString('<form', (string) $clean);
        $this->assertStringNotContainsString('<input', (string) $clean);
    }

    public function test_allows_whitelisted_iframe(): void
    {
        $clean = BodyHtml::clean('<iframe src="https://www.youtube.com/embed/abc123" allowfullscreen></iframe>');

        $this->assertStringContainsString('youtube.com/embed/abc123', (string) $clean);
    }

    public function test_removes_iframe_from_unlisted_host(): void
    {
        $clean = BodyHtml::clean('<iframe src="https://evil.test/embed/x"></iframe>');

        $this->assertStringNotContainsString('evil.test', (string) $clean);
    }

    public function test_strips_pasted_extension_popup_block_with_its_text(): void
    {
        $clean = BodyHtml::clean(
            '<p>Contenuto vero</p>'
            .'<div id="ssvd_dialog" class="ssvd_dialog"><button id="ssvd_dialog_get">Get</button>'
            .'<p>Subscription will automatically renew</p></div>'
        );

        $this->assertStringContainsString('Contenuto vero', (string) $clean);
        $this->assertStringNotContainsString('Subscription will automatically renew', (string) $clean);
        $this->assertStringNotContainsString('ssvd', (string) $clean);
    }

    public function test_drops_img_width_and_height(): void
    {
        $clean = BodyHtml::clean('<p><img src="/storage/post-body/x.jpg" alt="x" width="1280" height="853"></p>');

        $this->assertStringContainsString('src="/storage/post-body/x.jpg"', (string) $clean);
        $this->assertStringNotContainsString('width', (string) $clean);
        $this->assertStringNotContainsString('height', (string) $clean);
    }

    public function test_null_and_blank_normalise_to_null(): void
    {
        $this->assertNull(BodyHtml::clean(null));
        $this->assertNull(BodyHtml::clean('   '));
        $this->assertNull(BodyHtml::clean('<p></p>'));
    }
}
