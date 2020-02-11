<?php

namespace Tuiter\Controllers;
use Tuiter\Interfaces\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

Class LikeController implements Controller{
    public function config($app){
        $app->get('/like/{postId}', function (Request $request, Response $response, array $args) {
            $postToLike = $args['postId'];
            if ($request->getAttribute("likeService")->like($request->getAttribute("user"), $request->getAttribute("postService")->getPost($postToLike))) {
                $response = $response->withStatus(302)->withHeader('Location', '/');
            } else {
                $response = $response->withStatus(302)->withHeader('Location', '/user/sme');
            }
            return $response;
        });

    }
}