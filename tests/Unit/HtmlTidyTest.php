<?php

namespace Tests\Unit;

use App\Support\HtmlTidy;
use PHPUnit\Framework\TestCase;

class HtmlTidyTest extends TestCase
{
    public function test_removes_extension_popup_block_and_its_text(): void
    {
        $out = HtmlTidy::stripJunkBlocks(
            '<p>Vero</p><div class="ssvd_mengceng"><div id="ssvd_dialog">Salva pagina</div></div>'
        );

        $this->assertStringContainsString('Vero', $out);
        $this->assertStringNotContainsString('ssvd', $out);
        $this->assertStringNotContainsString('Salva pagina', $out);
    }

    public function test_keeps_clean_markup_and_null_passes_through(): void
    {
        $this->assertSame('<p>Ciao <strong>mondo</strong></p>', HtmlTidy::stripJunkBlocks('<p>Ciao <strong>mondo</strong></p>'));
        $this->assertNull(HtmlTidy::stripJunkBlocks(null));
    }
}
