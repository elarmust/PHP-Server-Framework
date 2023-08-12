<?php

/**
 * HTML view manager.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\ViewManager;

use DOMXPath;
use DOMDocument;
use ReflectionException;
use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\Core\ClassManager;
use Swoole\Coroutine\System;
use Framework\EventManager\EventManager;

class ViewManager {
    private EventManager $eventManager;
    private ClassManager $classManager;
    private Logger $logger;
    private array $views = [];

    /**
     * @param EventManager $eventManager
     * @param ClassManager $classManager
     * @param Logger $logger
     */
    public function __construct(EventManager $eventManager, ClassManager $classManager, Logger $logger) {
        $this->eventManager = $eventManager;
        $this->classManager = $classManager;
        $this->logger = $logger;
    }

    /**
     * Register a new view
     * 
     * @param string $viewName
     * @param null|string $controllerClass
     * @param string $viewSourceFile
     * @return void
     */
    public function registerView(string $viewName, ?string $controllerClass = null, string $viewString = ''): void {
        if ($controllerClass && !is_subclass_of($controllerClass, AbstractViewController::class)) {
            throw new InvalidArgumentException('View controller \'' . $controllerClass . '\' must extend ' . AbstractViewController::class . '!');
        }

        $this->views[$viewName] = new View($viewName, $viewString, $controllerClass);
    }

    /**
     * Unregister a view
     *
     * @param string $viewName
     * @return void
     */
    public function unregisterView(string $viewName): void {
        if (!isset($this->views[$viewName])) {
            $this->logger->log(Logger::LOG_NOTICE, 'Unregistering nonexistent view: \'' . $viewName . '\'', 'framework');
            return;
        }

        unset($this->views[$viewName]);
    }

    /**
     * List existing views.
     *
     * @return array
     */
    public function listViews(): array {
        return array_keys($this->views);
    }

    /**
     * Get base view by name
     * @param string $viewName
     * @throws InvalidArgumentException
     * @return View
     */
    public function getView(string $viewName): View {
        if (!isset($this->views[$viewName])) {
            throw new InvalidArgumentException('View \'' . $viewName . '\' does not exist!');
        }

        return $this->views[$viewName];
    }

    /**
     * Returns a parsed view.
     *
     * @param View $view View to use
     * @param array $data Parameters to pass to the view controller.
     * @return View Returns the parsed View.
     * @throws ReflectionException
     */
    public function parseView(View $view, array $data = []): View {
        $view = clone $view;
        $controller = $this->classManager->getTransientClass($view->getController(), [$data]);

        if ($view->getController()) {
            $eventResult = $this->eventManager->dispatchEvent('beforeViewController', ['view' => &$view, 'data' => &$data]);
            if ($eventResult->isCanceled() === false) {
                // Replace controller, if needed.
                if ($controller::class != $view->getController()) {
                    $controller = $this->classManager->getTransientClass($view->getController(), [$data]);
                }
            }
        }

        // Run View controller.
        $controller->run($data);
        $eventResult = $this->eventManager->dispatchEvent('beforeView', ['view' => &$view, 'data' => &$data]);
        // Load view contents if event has not been canceled
        if ($eventResult->isCanceled() === false) {
            $view->setView($controller->evalView($view));
        }

        $eventResult = $this->eventManager->dispatchEvent('afterView', ['view' => &$view, 'data' => &$data]);
        if ($eventResult->isCanceled() === false) {
            return $eventResult->getData()['view'];
        }

        return $view;
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
