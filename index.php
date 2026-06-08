<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

$routes = [
    ''                  => '/frontend/index.php',
    '/login'            => '/frontend/pages/login.php',
    '/cadastro'         => '/frontend/pages/cadastro.php',
    '/dashboard'        => '/frontend/pages/dashboard.php',
    '/perfil'           => '/frontend/pages/perfil.php',
    '/gerenciar'        => '/frontend/pages/gerenciar.php',
    '/portfolio'        => '/frontend/pages/portfolio.php',
    '/novo-servico'     => '/frontend/pages/novo-servico.php',
    '/chat'             => '/frontend/pages/chat.php',
    '/meus-pedidos'     => '/frontend/pages/meus-pedidos.php',
    '/detalhes'         => '/frontend/pages/detalhes.php',
    '/esqueci-senha'    => '/frontend/pages/esqueci-senha.php',
    '/avaliar-prestador'=> '/frontend/pages/avaliar-prestador.php',
    '/ver-perfil'       => '/frontend/pages/ver-perfil.php',
    '/logout'           => '/backend/controllers/AuthController.php',
];

if (isset($routes[$uri])) {
    $file = __DIR__ . $routes[$uri];
    if ($uri === '/logout') {
        $_GET['action'] = 'logout';
    }
    chdir(dirname($file));
    require $file;
    exit;
}

http_response_code(404);
echo 'Not Found';
