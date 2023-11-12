<?php

namespace Framework\Tests\Tests;

use PHPUnit\Framework\TestCase;
use Framework\Utils\HtmlEditor;

class HtmlEditorTest extends TestCase {
    private HtmlEditor $htmlEditor;

    public function setUp(): void {
        $html = '
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Framework</title>
    </head>
    <body>
    </body>
</html>';
        $this->htmlEditor = new HtmlEditor($html);
    }

    public function testAppend() {
        $this->htmlEditor->append('<div>Test 1</div>', '//body', true);
        $this->assertSame('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->htmlEditor->append('<div>Test 2</div>', '//body', true);
        $this->assertSame('<div>Test 2</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->htmlEditor->append('<div>Test 3</div>', '//body', false);
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getNextSiblings('//body')[0]->getHtmlContent()));
        $this->htmlEditor->append('<div>Test 4</div>', '//body', false);
        $this->assertSame('<div>Test 4</div>', trim($this->htmlEditor->getNextSiblings('//body')[0]->getHtmlContent()));
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getNextSiblings('//body')[1]->getHtmlContent()));
    }

    public function testPrepend() {
        $this->htmlEditor->prepend('<div>Test 1</div>', '//body', true);
        $this->assertSame('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 2</div>', '//body', true);
        $this->assertSame('<div>Test 2</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->assertSame('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 3</div>', '//body', false);
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[0]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 4</div>', '//body', false);
        $this->assertSame('<div>Test 4</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[0]->getHtmlContent()));
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[1]->getHtmlContent()));
    }
}
