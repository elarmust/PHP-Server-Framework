<?php

/**
 * HTML editor.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use DOMXPath;
use DOMDocument;

/**
 * The HtmlEditor trait provides a set of methods for easy manipulation of HTML content.
 * It offers functionalities for appending, prepending, replacing, and modifying HTML elements based on
 * XPath query.
 * This trait also includes methods for searching, adding,
 * removing, and setting attributes within HTML elements.
 */
trait HtmlEditor {
    /**
     * Append html content
     * 
     * @param string $htmlString The HTML content.
     * @param string $append Content to append
     * @param string $query XPath query
     * @param $afterElement When set to true, it inserts the content after the element. When set to false, it inserts the content after the last child element inside the matched element.
     * @return string
     */
    public function append(string $htmlString, string $append, string $pathQuery = null, $afterElement = true): string {
        if (!$pathQuery || $htmlString == '') {
            return $append;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];
            $newNode = $this->createDom($append);

            foreach ($xpath->query($pathQuery) as $node) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendChild($dom->importNode($newNode->documentElement, true));

                if ($afterElement) {
                    $node->parentNode->appendChild($fragment);
                } else {
                    $node->appendChild($fragment);
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString;
    }

    /**
     * Prepends html content
     * 
     * @param string $htmlString The HTML content.
     * @param string $prepend Content to prepend
     * @param string $query XPath query
     * @param $beforeElement When set to true, it inserts the content before the element. When set to false, it inserts the content before the last child element inside the matched element.
     * @return string
     */
    public function prepend(string $htmlString, string $prepend, string $pathQuery = null, $beforeElement = true): string {
        if (!$pathQuery || $htmlString == '') {
            return $prepend;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];
            $newNode = $this->createDom($prepend);

            foreach ($xpath->query($pathQuery) as $node) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendChild($dom->importNode($newNode->documentElement, true));

                if ($beforeElement) {
                    libxml_use_internal_errors(true);
                    $node->parentNode->insertBefore($fragment, $node);
                    libxml_clear_errors();
                } else {
                    $node->innerHTML = $prepend . $node->innerHTML;
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString;
    }

    /**
     * Replaces html content
     * 
     * @param string $htmlString The HTML content.
     * @param string $replace replacement content
     * @param string $query XPath query
     * @param $replaceElement When set to true, it replaces the entire matched the element. When set to false, it replaces the content inside the matched element.
     * @return string
     */
    public function replace(string $htmlString, string $replace, string $pathQuery = null, $replaceElement = true): string {
        if (!$pathQuery || $htmlString == '') {
            return $replace;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];
            $newNode = $this->createDom($replace);

            foreach ($xpath->query($pathQuery) as $node) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendChild($dom->importNode($newNode->documentElement, true));

                if ($replaceElement) {
                    $node->parentNode->replaceChild($fragment, $node);
                } else {
                    $node->nodeValue = '';
                    $node->appendChild($fragment);
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString;
    }

    /**
     * Remove HTML elements based on XPath query.
     * A wrapper for replace().
     *
     * @param string $htmlString The HTML content.
     * @param string $query XPath query
     * @param $removeElement When set to true, it removes the entire matched the element. When set to false, it removes the content inside the matched element.
     * @return string
     */
    public function remove(string $htmlString, string $pathQuery = null, $removeElement = true) {
        return $this->replace($htmlString, '', $pathQuery, $removeElement);
    }

    /**
     * Search for HTML elements based on an XPath query and return an array of matching elements as strings.
     * 
     * @param string $htmlString The HTML content.
     * @param string $query XPath query
     * @return array An array of matching elements as strings.
     */
    public function search(string $htmlString, string $pathQuery): array {
        $results = [];

        if (!$pathQuery || $htmlString == '') {
            return $results;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $xpath = $domXPath['xpath'];
            $dom = $domXPath['dom'];
            foreach ($xpath->query($pathQuery) as $node) {
                // Convert DOMNode to HTML string and add to results
                $element = $dom->saveHTML($node);
                $results[] = $element;
            }
        }

        return $results;
    }

    /**
     * Add attributes to HTML elements matching the given XPath query.
     * 
     * @param string $htmlString The HTML content.
     * @param string $query XPath query
     * @param array $attributesToAdd An associative array of attributes to add (e.g., ['class' => 'new-class', 'data-id' => '123']).
     * @return string The modified HTML content.
     */
    public function addAttributes(string $htmlString, string $pathQuery, array $attributesToAdd): string {
        if (!$pathQuery || $htmlString == '') {
            return $htmlString;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];

            foreach ($xpath->query($pathQuery) as $node) {
                foreach ($attributesToAdd as $attribute => $value) {
                    $node->setAttribute($attribute, $value);
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString; // Return the original HTML if there was an error
    }

    /**
     * Remove attributes from HTML elements matching the given XPath query.
     * 
     * @param string $htmlString The HTML content.
     * @param string $query XPath query
     * @param array $attributesToRemove An array of attribute names to remove (e.g., ['class', 'data-id']).
     * @return string The modified HTML content.
     */
    public function removeAttributes(string $htmlString, string $pathQuery, array $attributesToRemove): string {
        if (!$pathQuery || $htmlString == '') {
            return $htmlString;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];

            foreach ($xpath->query($pathQuery) as $node) {
                // If no specific attributes are provided, remove all attributes
                if (empty($attributesToRemove)) {
                    while ($node->hasAttributes()) {
                        $node->removeAttributeNode($node->attributes->item(0));
                    }
                } else {
                    foreach ($attributesToRemove as $attribute) {
                        $node->removeAttribute($attribute);
                    }
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString; // Return the original HTML if there was an error
    }

    /**
     * Replace attributes in HTML elements matching the given XPath query.
     * 
     * @param string $htmlString The HTML content.
     * @param string $query XPath query
     * @param array $attributesToAdd An associative array of attributes to add (e.g., ['class' => 'new-class', 'data-id' => '123']).
     * @return string The modified HTML content.
     */
    public function setAttributes(string $htmlString, string $pathQuery, array $attributes) {
        if (!$pathQuery || $htmlString == '') {
            return $htmlString;
        }

        $domXPath = $this->createDomXPath($htmlString);

        if ($domXPath) {
            $dom = $domXPath['dom'];
            $xpath = $domXPath['xpath'];

            // Loop through the matching elements
            foreach ($xpath->query($pathQuery) as $element) {
                // Remove existing attributes
                while ($element->hasAttributes()) {
                    $element->removeAttributeNode($element->attributes->item(0));
                }
        
                // Set new attributes
                foreach ($attributes as $name => $value) {
                    $element->setAttribute($name, $value);
                }
            }

            return $dom->saveHTML($dom->documentElement);
        }

        return $htmlString; // Return the original HTML if there was an error
    }

    /**
     * Create and configure DOMDocument and DOMXPath instances for HTML processing.
     *
     * @param string $htmlString The HTML content.
     * @return array|null An array containing DOMDocument and DOMXPath objects or null if there was an error.
     */
    private function createDomXPath(string $htmlString): ?array {
        $dom = new DOMDocument;
        $xpath = null;

        // Suppress malformed HTML warnings
        libxml_use_internal_errors(true);

        if ($dom->loadHTML($htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_NONET)) {
            // Create a new DOMXPath object
            $xpath = new DOMXPath($dom);
        }

        // Clear any XML error messages
        libxml_clear_errors();

        return ($xpath) ? ['dom' => $dom, 'xpath' => $xpath] : null;
    }

    private function createDom(string $html): DOMDocument {
        // Create a new DOMDocument for the appended content
        $dom = new DOMDocument;
        $dom->encoding = 'UTF-8'; // Set the charset explicitly for UTF-8
        // Load the appended HTML content
        libxml_use_internal_errors(true); // For malformed HTML warning suppression
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_NONET);
        libxml_clear_errors(); // Clear warnings
        return $dom;
    }
}
