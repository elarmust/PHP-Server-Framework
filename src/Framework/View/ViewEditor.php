<?php

/**
 * HTML view manager.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\View;

use DOMXPath;
use DOMDocument;
use Framework\View\View;

class ViewEditor {
    public function __construct() {
    }

    /**
     * Append html content
     * 
     * @param View $view
     * @param string $append Content to append
     * @param string $pathQuery Must be a valid XPath expression
     * @param $contents When true, it appends after the query result element. When false, it appends after last child element.
     * @return View
     */
    public function appendView(View $view, string $append, string $pathQuery = null, $contents = true): View {
        $viewString = $view->getView();
        if (!$pathQuery || $viewString == '') {
            $view->setView($append);
            return $view;
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // for malformed html warning suppression
        $dom->loadHTML($viewString, LIBXML_NOENT);
        libxml_clear_errors(); // for  warning suppression
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query($pathQuery) as $node) {
            $newNode = $dom->createDocumentFragment();
            $newNode->appendXML($append);
            if ($contents) {
                $node->parentNode->appendChild($newNode);
            } else {
                $node->appendChild($newNode);
            }
        }

        $view->setView($dom->saveHTML($dom->documentElement));
        return $view;
    }

    /**
     * Prepends html content
     * 
     * @param View $view
     * @param string $prepend Content to prepend
     * @param string $pathQuery Must be a valid XPath expression
     * @param $contents When true, it prepends after the query result element. When false, it prepends after last child element.
     * @return View
     */
    public function prependView(View $view, string $prepend, string $pathQuery = null, $contents = true): View {
        $viewString = $view->getView();
        if (!$pathQuery || $viewString == '') {
            $view->setView($prepend);
            return $view;
        }

        $dom = new DOMDocument;
        $dom->loadHTML($viewString, LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        $newNode = new DOMDocument;
        $newNode->encoding = 'UTF-8'; // Set the charset explicitly
        $newNode->loadHTML('<meta charset="UTF-8">' . $prepend, LIBXML_HTML_NODEFDTD);
        $newNode = $dom->importNode($newNode->documentElement, true);
        foreach ($xpath->query($pathQuery) as $node) {
            if ($contents) {
                libxml_use_internal_errors(true);
                $node->parentNode->insertBefore($newNode, $node);
                libxml_clear_errors();
            } else {
            
                $node->innerHTML = $prepend . $node->innerHTML;
            }
        }

        $view->setView($dom->saveHTML($dom->documentElement));
        return $view;
    }

    /**
     * Replaces html content
     * 
     * @param View $view
     * @param string $replace replacement content
     * @param string $pathQuery Must be a valid XPath expression
     * @param $contents When true, it replaces the entire query result element including its contents. When false, it only replaces the contents.
     * @return View
     */
    public function replaceView(View $view, string $replace, string $pathQuery = null, $contents = true): View {
        $viewString = $view->getView();
        if (!$pathQuery || $viewString == '') {
            $view->setView($replace);
            return $view;
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // for malformed html warning suppression
        $dom->loadHTML($viewString, LIBXML_NOENT);
        libxml_clear_errors(); // for  warning suppression
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query($pathQuery) as $node) {
            $newNode = $dom->createDocumentFragment();
            $newNode->appendXML($replace);
            if ($contents) {
                $node->parentNode->replaceChild($newNode, $node);
            } else {
                $node->nodeValue = '';
                $node->appendChild($newNode);
            }
        }

        $view->setView($dom->saveHTML($dom->documentElement));
        return $view;
    }
}
