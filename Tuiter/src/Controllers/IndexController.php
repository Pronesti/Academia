<?php

namespace Tuiter\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController implements \Tuiter\Interfaces\Controller {

    public function config($app) {

        $app->get('/', function (Request $request, Response $response, array $args) {
    
            $template = $request->getAttribute("twig")->load('index.html');

            $response->getBody()->write(
                $template->render(['name' => 'Dario'])
            );
            return $response;
        });

    }


}