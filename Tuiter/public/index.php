<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

if (PHP_SAPI == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) return false;
}

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
]);

$mongoconn = new \MongoDB\Client("mongodb://localhost");
$userService = new \Tuiter\Services\UserService($mongoconn->tuiter->users);
$postService = new \Tuiter\Services\PostService($mongoconn->tuiter->posts);
$likeService = new \Tuiter\Services\LikeService($mongoconn->tuiter->likes);
$followService = new \Tuiter\Services\FollowService($mongoconn->tuiter->follows, $userService);
$loginService = new \Tuiter\Services\LoginService($userService);


$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) use ($twig, $followService, $postService) {

    $template = $twig->load('index.html');
    $allUsersFollowed = $followService->getFollowed($_SESSION['user']);
    $allpost = array();
    foreach ($allUsersFollowed as $v) {
        foreach ($postService->getAllPosts($v) as $p) {
            $allpost[] = $p;
        }
    }
    $response->getBody()->write(
        $template->render(['posts' => $allpost])
    );
    return $response;
});

$app->get('/user/me', function (Request $request, Response $response, array $args) use ($twig, $postService, $userService) {

    $template = $twig->load('me.html');
    $tuits = $postService->getAllPosts($userService->getUser($_SESSION['user']));
    $response->getBody()->write(
        $template->render(['tuits' => $tuits])
    );
    return $response;
});
$app->get('/login', function (Request $request, Response $response, array $args) use ($twig) {

    $template = $twig->load('login.html');

    $response->getBody()->write(
        $template->render([])
    );
    return $response;
});

$app->get('/register', function (Request $request, Response $response, array $args) use ($twig) {

    $template = $twig->load('register.html');

    $response->getBody()->write(
        $template->render([])
    );
    return $response;
});

$app->post('/registerUser', function (Request $request, Response $response, array $args) use ($userService) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($userService->register($username, $username, $password)) {
        $response = $response->withStatus(302)->withHeader('Location', '/user/me');
    } else {
        $response = $response->withStatus(302)->withHeader('Location', '/register');
    }
    return $response;
});

$app->post('/loginUser', function (Request $request, Response $response, array $args) use ($loginService) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if (!$loginService->login($username, $password) instanceof \Tuiter\Models\UserNull) {
        $response = $response->withStatus(302)->withHeader('Location', '/user/me');
    } else {
        $response = $response->withStatus(302)->withHeader('Location', '/login');
    }
    return $response;
});

$app->post('/postTuit', function (Request $request, Response $response, array $args) use ($userService, $postService) {
    $content = $_POST['content'];
    if ($postService->create($content, $userService->getUser($_SESSION['user']))) {
        $response = $response->withStatus(302)->withHeader('Location', '/user/me');
    } else {
        $response = $response->withStatus(302)->withHeader('Location', '/login');
    }
    return $response;
});

$app->get('/follow/{username}', function (Request $request, Response $response, array $args) use ($userService, $followService) {
    $userToFollow = $args['username'];
    if ($followService->follow($_SESSION['user'], $userToFollow)) {
        $response = $response->withStatus(302)->withHeader('Location', '/');
    } else {
        $response = $response->withStatus(302)->withHeader('Location', '/user/sme');
    }
    return $response;
});

$app->get('/{username}', function (Request $request, Response $response, array $args) use ($twig, $postService, $userService) {
    $profile = $args['username'];
    $template = $twig->load('index.html');
    if ($profile === $_SESSION['user']) {
        $response = $response->withStatus(302)->withHeader('Location', '/user/me');
    } else {
        $allpost = $postService->getAllPosts($userService->getUser($profile));
        $response->getBody()->write(
            $template->render(['posts' => $allpost])
        );
    }
    return $response;
});

$app->run();
