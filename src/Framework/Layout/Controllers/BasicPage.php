<?php

namespace Framework\Layout\Controllers;

use Framework\Container\ClassContainer;
use Framework\View\ViewRegistry;
use Framework\Localization\Locale;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\AbstractRouteController;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicPage extends AbstractRouteController {
    private Locale $locale;

    public function __construct(private ViewRegistry $viewRegistry, private ClassContainer $classContainer) {
        // Set up a locale only for this controller.
        $this->locale = new Locale('testWebsite', 'en_US');
        $this->locale->addTranslations([
            'test-website' => 'Test website.',
            'footer' => '© Copyright {year} by Elar Must. All Rights Reserved.'
        ]);
    }

    public function execute(ServerRequestInterface $request, ResponseInterface $response, ControllerStackInterface $controllerStack): ResponseInterface {
        $view = $this->viewRegistry->getView('basicPage');
        $response = $response->withStatus(200);
        $response->getBody()->write($view->getView(['controller' => $this]));
        return $controllerStack->execute($request, $response);
    }

    public function getLocale(): Locale {
        return $this->locale;
    }
}
