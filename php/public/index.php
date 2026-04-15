<?php


session_start();

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AlbumController.php';
require_once __DIR__ . '/../controllers/PhotoController.php';
require_once __DIR__ . '/../controllers/FeedController.php';
require_once __DIR__ . '/../controllers/ExportController.php';

$route  = $_GET['route'] ?? 'feed';
$method = $_SERVER['REQUEST_METHOD'];

$authController   = new AuthController();
$albumController  = new AlbumController();
$photoController  = new PhotoController();
$feedController   = new FeedController();
$exportController = new ExportController();

try {
    switch ($route) {
        
        case 'login':
            $method === 'POST' ? $authController->login() : $authController->showLogin();
            break;
        case 'register':
            $method === 'POST' ? $authController->register() : $authController->showRegister();
            break;
        case 'logout':
            $authController->logout();
            break;

        
        case 'feed':
            $feedController->index();
            break;
        case 'api_feed':
            $feedController->loadMore();
            break;

        
        case 'albums':
            $albumController->index();
            break;
        case 'album':
            $albumController->show();
            break;
        case 'album_create':
            $albumController->create();
            break;
        case 'album_delete':
            $albumController->delete();
            break;
        case 'album_share':
            $albumController->share();
            break;
        case 'api_search_users':
            $albumController->searchUsers();
            break;

        
        case 'photo_upload':
            $photoController->upload();
            break;
        case 'photo_edit':
            $photoController->edit();
            break;
        case 'photo_serve':
            $photoController->serve();
            break;
        case 'photo_delete':
            $photoController->delete();
            break;

        
        case 'api_save_recipe':
            $photoController->saveRecipe();
            break;
        case 'api_get_recipe':
            $photoController->getRecipe();
            break;

        
        case 'api_like':
            $photoController->like();
            break;
        case 'api_comment':
            $photoController->comment();
            break;
        case 'api_comments':
            $photoController->getComments();
            break;

        
        case 'api_export_recipe':
            $exportController->exportRecipe();
            break;
        case 'api_import_recipe':
            $exportController->importRecipe();
            break;

        default:
            redirect('/index.php?route=feed');
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo '<h1>Erreur serveur</h1><p>Une erreur est survenue.</p>';
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    http_response_code(500);
    echo '<h1>Erreur</h1><p>Une erreur inattendue est survenue.</p>';
}
