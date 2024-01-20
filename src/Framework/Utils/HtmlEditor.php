<?php

/**
 * HTML editor.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Utils;

use DOMText;
use DOMXPath;
use DOMNode;
use DOMElement;
use DOMDocument;

/**
 * The HtmlEditor class provides a set of methods for easy manipulation of HTML content.
 * It offers functionalities for appending, prepending, replacing, and modifying HTML elements based on
 * XPath queries.
 */
class HtmlEditor {
    protected null|DOMDocument|DomNode $dom = null;
    protected null|DOMXPath $xPath = null;

    public function __construct(DOMDocument|DomNode|string $content) {
        libxml_use_internal_errors(true);

        if ($content instanceof DOMNode || $content instanceof DOMDocument) {
            $this->dom = $content;
        } else {
            $this->dom = $this->createDom($content);
        }

        if ($this->dom instanceof DOMDocument) {
            $this->xPath = new DOMXPath($this->dom);
        } else {
            $this->xPath = new DOMXPath($this->dom->ownerDocument);
        }

        libxml_clear_errors();
    }

    /**
     * Append html content
     *
     * @param string|HtmlEditor $append Content to append. Either a string or an HtmlEditor object.
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @param $innerHtml = false When false, appends to the selected element; when true, appends to its contents.
     *
     * @return HtmlEditor
     */
    public function append(string|HtmlEditor $append, string $pathQuery = '', $innerHtml = false): HtmlEditor {
        if ($this->xPath) {
            if (is_string($append)) {
                $tempDom = $this->createDom('<wrapper>' . $append . '</wrapper>');
                $childNodes = $tempDom->getElementsByTagName('wrapper')[0]->childNodes;
            } else {
                $childNodes = $append->getDom()->childNodes;
            }

            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                foreach ($childNodes as $childNode) {
                    if ($this->dom->ownerDocument) {
                        $element = $this->dom->ownerDocument->importNode($childNode, true);
                    } else {
                        $element = $this->dom->importNode($childNode, true);
                    }

                    if ($innerHtml) {
                        $node->appendChild($element);
                    } else {
                        if ($node->nextSibling) {
                            $node->parentNode->insertBefore($element, $node->nextSibling);
                        } else {
                            $node->appendChild($element);
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Prepends html content
     *
     * @param string|HtmlEditor $prepend Content to prepend. Either a string or an HtmlEditor object.
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @param $innerHtml = false When false, prepends to the selected element; when true, prepends to its contents.
     * @return HtmlEditor
     */
    public function prepend(string|HtmlEditor $prepend, string $pathQuery = '', $innerHtml = false): HtmlEditor {
        if ($this->xPath) {
            if (is_string($prepend)) {
                $tempDom = $this->createDom('<wrapper>' . $prepend . '</wrapper>');
                $childNodes = $tempDom->getElementsByTagName('wrapper')[0]->childNodes;
            } else {
                $childNodes = $prepend->getDom()->childNodes;
            }

            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                foreach ($childNodes as $childNode) {
                    if ($this->dom->ownerDocument) {
                        $element = $this->dom->ownerDocument->importNode($childNode, true);
                    } else {
                        $element = $this->dom->importNode($childNode, true);
                    }

                    if ($innerHtml) {
                        $node->insertBefore($element, $node->firstChild);
                    } else {
                        $node->parentNode->insertBefore($element, $node);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Replaces html content
     *
     * @param string|HtmlEditor $replace Content to replace. Either a string or an HtmlEditor object.
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @param $innerHtml = false When false, replaces the entire element; when true, replaces only its content.
     * @return HtmlEditor
     */
    public function replace(string|HtmlEditor $replace, string $pathQuery = '', $innerHtml = false): HtmlEditor {
        if ($this->xPath) {
            if (is_string($replace)) {
                $tempDom = $this->createDom('<wrapper>' . $replace . '</wrapper>');
                $childNodes = $tempDom->getElementsByTagName('wrapper')[0]->childNodes;
            } else {
                $childNodes = $replace->getDom()->childNodes;
            }

            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                if ($innerHtml) {
                    foreach ($node->childNodes as $child) {
                        $node->removeChild($child);
                    }
                }

                $removeNode = false;
                foreach ($childNodes as $childNode) {
                    $element = $this->dom->ownerDocument->importNode($childNode, true);
                    if ($innerHtml) {
                        $node->appendChild($element);
                    } else {
                        $removeNode = true;
                        if ($node->nextSibling) {
                            $node->parentNode->insertBefore($element, $node->nextSibling);
                        } else {
                            $node->parentNode->appendChild($element);
                        }
                    }
                }

                if ($removeNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        return $this;
    }

    /**
     * Remove HTML elements.
     *
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @param $innerHtml = false When false, removes the entire element; when true, removes only its content.
     *
     * @return HtmlEditor
     */
    public function remove(string $pathQuery = '', $innerHtml = false): HtmlEditor {
        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                if ($innerHtml) {
                    foreach ($node->childNodes as $child) {
                        // Remove the indentation that would be left after node removal.
                        if ($child->previousSibling instanceof DOMText && preg_match('/^\s*$/', $child->previousSibling->nodeValue)) {
                            $node->removeChild($child->previousSibling);
                        }

                        $node->removeChild($child);
                    }
                } else {
                    // Remove the indentation that would be left after node removal.
                    if ($node->previousSibling instanceof DOMText && preg_match('/^\s*$/', $node->previousSibling->nodeValue)) {
                        $node->parentNode->removeChild($node->previousSibling);
                    }

                    $node->parentNode->removeChild($node);
                }
            }
        }

        return $this;
    }

    /**
     * Add attributes to HTML elements matching the given XPath query.
     *
     * @param array $attributesToAdd An associative array of attributes to add (e.g., ['class' => 'new-class', 'data-id' => '123']).
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @return HtmlEditor
     */
    public function addAttributes(array $attributes, string $pathQuery = ''): HtmlEditor {
        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                foreach ($attributes as $attribute => $value) {
                    $node->setAttribute($attribute, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Replace attributes in HTML elements.
     *
     * @param array $attributesToAdd An associative array of attributes to add (e.g., ['class' => 'new-class', 'data-id' => '123']).
     * @param string $query Optional XPath query. This is relative to the current selected element.
     *
     * @return HtmlEditor
     */
    public function setAttributes(array $attributes, string $pathQuery = ''): HtmlEditor {
        if ($this->xPath) {
            // Loop through the matching elements
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                // Remove existing attributes
                while ($node->hasAttributes()) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }

                // Set new attributes
                foreach ($attributes as $name => $value) {
                    $node->setAttribute($name, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Remove attributes from HTML elements.
     *
     * @param array $attributesToRemove An array of attribute names to remove (e.g., ['class', 'data-id']).
     * @param string $query Optional XPath query. This is relative to the current selected element.
     *
     * @return HtmlEditor
     */
    public function removeAttributes(array $attributes = [], string $pathQuery = ''): HtmlEditor {
        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $node) {
                // If no specific attributes are provided, remove all attributes
                if (empty($attributes)) {
                    while ($node->hasAttributes()) {
                        $node->removeAttributeNode($node->attributes->item(0));
                    }
                } else {
                    foreach ($attributes as $attribute) {
                        $node->removeAttribute($attribute);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Search for HTML elements based on an XPath query and return an array of matching elements.
     *
     * @param string $query Optional XPath query. This is relative to the current selected element.
     * @param bool $clone = false When set to true, it returns the result as a separate and unrelated HTML object.
     *
     * @return array An array of HtmlEditor objects.
     */
    public function search(string $pathQuery, bool $clone = false): array {
        $results = [];

        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) ?? [] as $node) {
                if ($clone) {
                    $results[] = new HtmlEditor($this->getDom()->saveHTML($node));
                } else {
                    $results[] = new HtmlEditor($node);
                }
            }
        }

        return $results;
    }

    /**
     * Returns the parent of the current selected element.
     *
     * @return ?HtmlEditor Returns the parent of the current selected element or null, if it could not be determined.
     */
    public function getParent(): ?HtmlEditor {
        $parent = $this->dom->parentNode;
        if ($parent) {
            return new HtmlEditor($parent);
        }

        return null;
    }

    /**
     * Retrieve all child elements.
     *
     * @param string $pathQuery Optional XPath query. This is relative to the current selected element.
     *
     * @return array An array of child elements.
     */
    public function getChildren(string $pathQuery = '', ?int $nth = null): array {
        $results = [];

        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $match) {
                $count = 0;
                foreach ($match->childNodes as $child) {
                    // Ignore indentations.
                    if ($child instanceof DOMText && preg_match('/^\s*$/', $child->nodeValue)) {
                        continue;
                    }

                    $count++;
                    if (!$nth || $count == $nth) {
                        $results[] = new HtmlEditor($child);

                        if ($nth && $count == $nth) {
                            break;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Retrieve sibling elements of the current selected element.
     *
     * @param string $pathQuery Optional XPath query. This is relative to the current selected element.
     *
     * @return array An array of sibling elements.
     */
    public function getSiblings(string $pathQuery = '', ?int $nth = null): array {
        $results = [];
        $parent = $this->getParent();
        if ($parent) {
            $results = $parent->getChildren($pathQuery, $nth);
        }

        return $results;
    }

    /**
     * Retrieve the next sibling elements.
     *
     * @param string $pathQuery Optional XPath query. This is relative to the current selected element.
     * @param ?int nth Optional nth next sibling.
     *
     * @return array An array of next sibling elements.
     */
    public function getNextSiblings(string $pathQuery = '', ?int $nth = null): array {
        $results = [];

        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $match) {
                $nextSiblingCounter = 0;
                $currentNode = $match->nextSibling;
                while ($currentNode !== null) {
                    if (!($currentNode instanceof DOMText && preg_match('/^\s*$/', $currentNode->nodeValue))) {
                        $nextSiblingCounter++;
                        if (!$nth || $nextSiblingCounter == $nth) {
                            $results[] = new HtmlEditor($currentNode);

                            if ($nth && $nextSiblingCounter == $nth) {
                                break;
                            }
                        }
                    }

                    $currentNode = $currentNode->nextSibling;
                }
            }
        }

        return $results;
    }

    /**
     * Retrieve previous sibling elements.
     *
     * @param string $pathQuery Optional XPath query. This is relative to the current selected element.
     * @param ?int nth Optional nth previous sibling.
     *
     * @return array An array of previous sibling elements.
     */
    public function getPreviousSiblings(string $pathQuery = '', ?int $nth = null): array {
        $results = [];

        if ($this->xPath) {
            foreach ($this->xPath->query($this->generateAbsoluteXpath() . $pathQuery) as $match) {
                $previousSiblingCounter = 0;
                $currentNode = $match->previousSibling;
                while ($currentNode !== null) {
                    if (!($currentNode instanceof DOMText && preg_match('/^\s*$/', $currentNode->nodeValue))) {
                        $previousSiblingCounter++;
                        if (!$nth || $previousSiblingCounter == $nth) {
                            $results[] = new HtmlEditor($currentNode);

                            if ($nth && $previousSiblingCounter == $nth) {
                                break;
                            }
                        }
                    }

                    $currentNode = $currentNode->previousSibling;
                }
            }
        }

        return $results;
    }

    /**
     * Get the HTML content.
     *
     * @return string
     */
    public function getHtmlContent(): string {
        if ($this->dom instanceof DomDocument) {
            return $this->dom->saveHTML($this->dom->documentElement);
        } else {
            $tempDom = new DOMDocument();
            $tempDom->appendChild($tempDom->importNode($this->dom, true));

            return preg_replace('/^\s*$/m', '', $tempDom->saveHTML());
            return $tempDom->saveHTML();
        }
    }

    /**
     * Get the HTML content as a string.
     *
     * @return string
     */
    public function __toString(): string {
        return $this->getHtmlContent();
    }

    public function getRoot(): HtmlEditor {
        if (!$this->dom instanceof DOMDocument) {
            $this->dom = $this->dom->ownerDocument;
        }

        return $this;
    }

    public function getDom(): DOMElement|DOMDocument {
        return $this->dom;
    }

    /**
     * Create a new DOMDocument from html string.
     *
     * @param string $htmlString The HTML content.
     * @return DOMDocument
     */
    private function createDom(string $htmlString): DOMDocument {
        // Create a new DOMDocument for the appended content
        $dom = new DOMDocument();
        $dom->encoding = 'UTF-8'; // Set the charset explicitly for UTF-8
        // Load the appended HTML content
        libxml_use_internal_errors(true); // For malformed HTML warning suppression
        $dom->loadHTML($htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors(); // Clear warnings
        return $dom;
    }

    private function generateAbsoluteXpath() {
        $elem = $this->dom;
    
        // Check if the input is a DOMDocument
        if ($elem instanceof DOMDocument) {
            return '/';
        }
    
        $xpath = '';
    
        // Start from the given node and traverse up the tree
        while ($elem !== null) {
            $elementXPath = '';
    
            // Check if the node is an element and has an ID
            if ($elem instanceof DOMElement && $elem->hasAttribute('id')) {
                $elementXPath = $elem->localName . '[@id="' . $elem->getAttribute('id') . '"]';
            } elseif ($elem instanceof DOMText) {
                // If it's a text node, include the position among siblings
                $siblings = 1;
                $previous = $elem->previousSibling;
    
                while ($previous !== null) {
                    if ($previous instanceof DOMText) {
                        $siblings++;
                    }
    
                    $previous = $previous->previousSibling;
                }
    
                $elementXPath = 'text()[' . $siblings . ']';
            } elseif ($elem instanceof DOMElement) {
                // For regular elements, include the position among siblings
                $siblings = 1;
                $previous = $elem->previousSibling;
    
                while ($previous !== null) {
                    if ($previous instanceof DOMElement && $previous->localName === $elem->localName) {
                        $siblings++;
                    }
    
                    $previous = $previous->previousSibling;
                }
    
                $elementXPath = $elem->localName;
                if ($siblings > 1 || ($siblings == 1 && $elem->nextSibling !== null)) {
                    $elementXPath .= '[' . $siblings . ']';
                }
            }
    
            // Prepend the node's XPath to the current XPath
            $xpath = '/' . $elementXPath . $xpath;
    
            // Move up to the parent node
            $elem = $elem->parentNode;
        }
    
        return $xpath;
    }
}
