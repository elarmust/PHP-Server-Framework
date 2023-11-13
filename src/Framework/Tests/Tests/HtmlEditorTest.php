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

    public function testReplace() {
        $this->htmlEditor->append('<div>Test 1</div>', '//body', true);
        $this->htmlEditor->append('<div>Test 2</div>', '//body', true);
        $this->htmlEditor->getChildren('//body')[0]->replace('<div>Test 3</div>');
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->assertSame('<div>Test 2</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->htmlEditor->getChildren('//body')[0]->replace('<p>Test 3</p>', innerHtml: true);
        $this->assertSame('<div><p>Test 3</p></div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
    }

    public function testRemove() {
        $this->htmlEditor->append('<div>Test 1</div><div>Test 2</div><div>Test 3</div>', '//body', true);
        $this->htmlEditor->getChildren('//body')[1]->remove();
        $this->assertEquals(2, count($this->htmlEditor->getChildren('//body')));
        $this->assertSame('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->assertSame('<div>Test 3</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));

        $this->htmlEditor->getChildren('//body')[1]->remove(innerHtml: true);
        $this->assertSame('<div></div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));

    }

    public function testAttributes() {
        $this->htmlEditor->append('<div><div></div></div>', '//body', true);
        $this->htmlEditor->getChildren('//body')[0]->addAttributes(['test1' => 'test 1']);
        $this->htmlEditor->getChildren('//body')[0]->addAttributes(['test2' => 'test 2']);
        $this->htmlEditor->getChildren('//body')[0]->getChildren()[0]->addAttributes(['test3' => 'test 3']);
        $this->htmlEditor->getChildren('//body')[0]->getChildren()[0]->addAttributes(['test4' => 'test 4']);
        $this->assertSame('<div test1="test 1" test2="test 2"><div test3="test 3" test4="test 4"></div></div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));

        $this->htmlEditor->getChildren('//body')[0]->setAttributes(['test5' => 'test 5']);
        $this->htmlEditor->getChildren('//body')[0]->getChildren()[0]->setAttributes(['test6' => 'test 6']);
        $this->assertSame('<div test5="test 5"><div test6="test 6"></div></div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));

        $this->htmlEditor->getChildren('//body')[0]->getChildren()[0]->removeAttributes(['test6']);
        $this->htmlEditor->getChildren('//body')[0]->removeAttributes(['test5']);
        $this->assertSame('<div><div></div></div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
    }
}
