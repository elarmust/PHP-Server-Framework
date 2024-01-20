<?php

namespace Framework\Tests\Tests;

use PHPUnit\Framework\TestCase;
use Framework\Utils\HtmlEditor;

class HtmlEditorTest extends TestCase {
    private HtmlEditor $htmlEditor;
    private string $htmlContent = '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Framework</title>
    </head>
    <body>
        <div>
            <div>Test 1</div>
            <div>Test 2</div>
            <div>Test 3</div>
            Test 4
        </div>
    </body>
</html>';

    public function setUp(): void {
        $this->htmlEditor = new HtmlEditor($this->htmlContent);
    }

    public function getGetHtmlContent() {
        $this->assertEquals($this->htmlContent, trim($this->htmlEditor->getHtmlContent()));
    }

    public function testSearch() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0] ?? null;
        $this->assertInstanceOf(HtmlEditor::class, $search);
        $this->assertEquals('<div>Test 1</div>', trim($search->getHtmlContent()));
        $search = $this->htmlEditor->search('//body/div/div[2]')[0] ?? null;
        $this->assertInstanceOf(HtmlEditor::class, $search);
        $this->assertEquals('<div>Test 2</div>', trim($search->getHtmlContent()));
        $search = $this->htmlEditor->search('//body/div/text()[4]')[0] ?? null;
        $this->assertInstanceOf(HtmlEditor::class, $search);
        $this->assertEquals('Test 4', trim($search->getHtmlContent()));
    }

    public function testSearchNoResults() {
        $search = $this->htmlEditor->search('//body/p');
        $this->assertEmpty($search);
    }

    public function testGetChildren() {
        $search = $this->htmlEditor->search('//body/div')[0]->getChildren();
        $this->assertCount(4, $search);
    }

    public function testGetNthChildren() {
        $search = $this->htmlEditor->search('//body/div')[0];
        $this->assertEquals('<div>Test 1</div>', trim($search->getChildren(nth: 1)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 2</div>', trim($search->getChildren(nth: 2)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 3</div>', trim($search->getChildren(nth: 3)[0]->getHtmlContent()));
        $this->assertEquals('Test 4', trim($search->getChildren(nth: 4)[0]->getHtmlContent()));
    }

    public function testGetParent() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0];
        $search = $search->getParent()->getChildren();
        $this->assertCount(4, $search);
    }

    public function testSearchCloneResults() {
        $search = $this->htmlEditor->search('//body/div/div[1]', true)[0];
        $this->assertNull($search->getParent());
    }

    public function testGetSiblings() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0]->getSiblings();
        $this->assertCount(4, $search);
    }

    public function testGetNthSibling() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0];
        $this->assertEquals('<div>Test 1</div>', trim($search->getSiblings(nth: 1)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 2</div>', trim($search->getSiblings(nth: 2)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 3</div>', trim($search->getSiblings(nth: 3)[0]->getHtmlContent()));
        $this->assertEquals('Test 4', trim($search->getSiblings(nth: 4)[0]->getHtmlContent()));
    }

    public function testNextSibling() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0]->getNextSiblings();
        $this->assertEquals('<div>Test 2</div>', trim($search[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 3</div>', trim($search[1]->getHtmlContent()));
        $this->assertEquals('Test 4', trim($search[2]->getHtmlContent()));
    }

    public function testNextNthSibling() {
        $search = $this->htmlEditor->search('//body/div/div[1]')[0];
        $this->assertEquals('<div>Test 2</div>', trim($search->getNextSiblings(nth: 1)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 3</div>', trim($search->getNextSiblings(nth: 2)[0]->getHtmlContent()));
        $this->assertEquals('Test 4', trim($search->getNextSiblings(nth: 3)[0]->getHtmlContent()));
    }

    public function testPreviousSibling() {
        $search = $this->htmlEditor->search('//body/div/text()[4]')[0]->getPreviousSiblings();
        $this->assertEquals('<div>Test 3</div>', trim($search[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 2</div>', trim($search[1]->getHtmlContent()));
        $this->assertEquals('<div>Test 1</div>', trim($search[2]->getHtmlContent()));
    }

    public function testPreviousNthSibling() {
        $search = $this->htmlEditor->search('//body/div/text()[4]')[0];
        $this->assertEquals('<div>Test 3</div>', trim($search->getPreviousSiblings(nth: 1)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 2</div>', trim($search->getPreviousSiblings(nth: 2)[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 1</div>', trim($search->getPreviousSiblings(nth: 3)[0]->getHtmlContent()));
    }

    public function testGetRoot() {
        $search = $this->htmlEditor->search('//body/div/text()[4]')[0];
        $this->assertStringContainsString(trim($search->getRoot()->getHtmlContent()), $this->htmlContent);
    }

    public function testAppend() {
        $this->htmlEditor->append('<div>Test 5</div>', '//body/div[1]');
        $this->assertEquals('<div>Test 5</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->htmlEditor->append('<div>Test 6</div>', '//body/div[1]');
        $this->assertEquals('<div>Test 6</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->assertEquals('<div>Test 5</div>', trim($this->htmlEditor->getChildren('//body')[2]->getHtmlContent()));
    }

    public function testInnerHtml() {
        $this->htmlEditor->append('<div>Test 5</div>', '//body/div', true);
        $this->assertEquals('<div>Test 5</div>', trim($this->htmlEditor->getChildren('//body/div')[4]->getHtmlContent()));
        $this->htmlEditor->append('<div>Test 6</div>', '//body/div', true);
        $this->assertEquals('<div>Test 6</div>', trim($this->htmlEditor->getChildren('//body/div')[5]->getHtmlContent()));
        $this->assertEquals('<div>Test 5</div>', trim($this->htmlEditor->getChildren('//body/div')[4]->getHtmlContent()));
    }

    public function testPrepend() {
        $this->htmlEditor->prepend('<div>Test 1</div>', '//body', true);
        $this->assertEquals('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 2</div>', '//body', true);
        $this->assertEquals('<div>Test 2</div>', trim($this->htmlEditor->getChildren('//body')[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 1</div>', trim($this->htmlEditor->getChildren('//body')[1]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 3</div>', '//body', false);
        $this->assertEquals('<div>Test 3</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[0]->getHtmlContent()));
        $this->htmlEditor->prepend('<div>Test 4</div>', '//body', false);
        $this->assertEquals('<div>Test 4</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[0]->getHtmlContent()));
        $this->assertEquals('<div>Test 3</div>', trim($this->htmlEditor->getPreviousSiblings('//body')[1]->getHtmlContent()));
    }

    public function testReplace() {
        $this->htmlEditor->getChildren('//body/div')[0]->replace('<p>Test 1</p>');
        $this->assertEquals('<p>Test 1</p>', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
        $this->htmlEditor->getChildren('//body/div')[1]->replace('<p>Test 2</p>');
        $this->assertEquals('<p>Test 2</p>', trim($this->htmlEditor->getChildren('//body/div')[1]->getHtmlContent()));
    }

    public function testReplaceInnerHtml() {
        $this->htmlEditor->getChildren('//body/div')[0]->replace('<p>Test 1</p>', innerHtml: true);
        $this->assertEquals('<div><p>Test 1</p></div>', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
        $this->htmlEditor->getChildren('//body/div')[1]->replace('<p>Test 2</p>', innerHtml: true);
        $this->assertEquals('<div><p>Test 2</p></div>', trim($this->htmlEditor->getChildren('//body/div')[1]->getHtmlContent()));
    }

    public function testRemove() {
        $this->htmlEditor->getChildren('//body/div')[0]->remove();
        $this->assertEquals('<div>Test 2</div>', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
        $this->htmlEditor->getChildren('//body/div')[0]->remove();
        $this->assertEquals('<div>Test 3</div>', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
        $this->htmlEditor->getChildren('//body/div')[0]->remove();
        $this->assertEquals('Test 4', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
    }

    public function testAttributes() {
        $this->htmlEditor->getChildren('//body/div')[0]->addAttributes(['test1' => 'test 1']);
        $this->htmlEditor->getChildren('//body/div')[1]->addAttributes(['test2' => 'test 2']);
        $this->htmlEditor->getChildren('//body/div')[2]->addAttributes(['test3' => 'test 3']);

        $this->assertEquals('<div test1="test 1">Test 1</div>', trim($this->htmlEditor->getChildren('//body/div')[0]->getHtmlContent()));
        $this->assertEquals('<div test2="test 2">Test 2</div>', trim($this->htmlEditor->getChildren('//body/div')[1]->getHtmlContent()));
        $this->assertEquals('<div test3="test 3">Test 3</div>', trim($this->htmlEditor->getChildren('//body/div')[2]->getHtmlContent()));
    }
}
