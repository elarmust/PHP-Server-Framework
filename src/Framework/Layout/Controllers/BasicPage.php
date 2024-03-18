<?php

namespace Framework\Layout\Controllers;

use Framework\Container\ClassContainer;
use Framework\View\ViewRegistry;
use Framework\Localization\Locale;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\Controller;

class BasicPage extends Controller {
    private Locale $locale;

    public function __construct(private ViewRegistry $viewRegistry, private ClassContainer $classContainer) {
        // Set up a locale only for this controller.
        $this->locale = new Locale('testWebsite', 'en_US');
        $this->locale->addTranslations([
            'test-website' => 'Test website.',
            'footer' => '© Copyright {year} by Elar Must. All Rights Reserved.'
        ]);
    }

    public function process(): ResponseInterface {
        $view = $this->viewRegistry->getView('basicPage');
        $response = $this->response->withStatus(200);
        $response->getBody()->write($view->getView(['controller' => $this]));
        return $this->controllerStack->next($this->request, $response);
    }

    public function getLocale(): Locale {
        return $this->locale;
    }
}
