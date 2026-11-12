<?php

declare(strict_types=1);

use DataForge\Http\Controller\ActivityController;
use DataForge\Http\Controller\AuthController;
use DataForge\Http\Controller\DashboardController;
use DataForge\Http\Controller\DatabaseController;
use DataForge\Http\Controller\HomeController;
use DataForge\Http\Controller\MediaController;
use DataForge\Http\Controller\ProfileController;
use DataForge\Http\Controller\QueryController;
use DataForge\Http\Controller\RecordController;
use DataForge\Http\Controller\TableController;
use DataForge\Http\Middleware\RequireAuthMiddleware;
use DataForge\Http\Middleware\VerifyCsrfMiddleware;

/**
 * Mapa de rutas de la aplicacion.
 *
 * Las rutas que modifican datos declaran el middleware de CSRF y las que
 * requieren sesion iniciada declaran el middleware de autenticacion.
 */
return [
    ['GET',  '/',                                [HomeController::class, 'index']],
    ['GET',  '/docs',                            [HomeController::class, 'docs']],
    ['GET',  '/about',                           [HomeController::class, 'about']],

    ['GET',  '/login',                           [AuthController::class, 'showLogin']],
    ['POST', '/login',                           [AuthController::class, 'login'],       [VerifyCsrfMiddleware::class]],
    ['GET',  '/register',                        [AuthController::class, 'showRegister']],
    ['POST', '/register',                        [AuthController::class, 'register'],    [VerifyCsrfMiddleware::class]],
    ['POST', '/logout',                          [AuthController::class, 'logout'],      [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/onboarding',                      [AuthController::class, 'showOnboarding'], [RequireAuthMiddleware::class]],
    ['POST', '/onboarding',                      [AuthController::class, 'onboarding'],  [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],

    ['GET',  '/dashboard',                       [DashboardController::class, 'index'],  [RequireAuthMiddleware::class]],

    ['GET',  '/databases',                       [DatabaseController::class, 'index'],   [RequireAuthMiddleware::class]],
    ['POST', '/databases',                       [DatabaseController::class, 'create'],  [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}',            [DatabaseController::class, 'show'],    [RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/delete',     [DatabaseController::class, 'destroy'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}/export.sql', [DatabaseController::class, 'export'],  [RequireAuthMiddleware::class]],

    ['GET',  '/databases/{database}/tables/new', [TableController::class, 'createForm'], [RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables',     [TableController::class, 'create'],     [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}/tables/{table}/structure', [TableController::class, 'structure'], [RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/structure', [TableController::class, 'saveStructure'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/delete',    [TableController::class, 'destroy'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/columns/delete', [TableController::class, 'destroyColumn'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],

    ['GET',  '/databases/{database}/tables/{table}',                   [RecordController::class, 'index'],   [RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}/tables/{table}/records/new',        [RecordController::class, 'createForm'], [RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/records',            [RecordController::class, 'create'],  [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}/tables/{table}/records/{id}',       [RecordController::class, 'edit'],    [RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/records/{id}',       [RecordController::class, 'update'],  [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/databases/{database}/tables/{table}/records/{id}/delete', [RecordController::class, 'destroy'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['GET',  '/databases/{database}/tables/{table}/export.csv',        [RecordController::class, 'export'],  [RequireAuthMiddleware::class]],

    ['GET',  '/query',      [QueryController::class, 'index'],   [RequireAuthMiddleware::class]],
    ['POST', '/query',      [QueryController::class, 'execute'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],

    ['GET',  '/activity',   [ActivityController::class, 'index'], [RequireAuthMiddleware::class]],

    ['GET',  '/profile',          [ProfileController::class, 'show'],          [RequireAuthMiddleware::class]],
    ['POST', '/profile',          [ProfileController::class, 'update'],        [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/profile/password', [ProfileController::class, 'changePassword'], [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/profile/theme',    [ProfileController::class, 'changeTheme'],    [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],
    ['POST', '/profile/mode',     [ProfileController::class, 'changeMode'],     [VerifyCsrfMiddleware::class]],
    ['POST', '/profile/delete',   [ProfileController::class, 'destroy'],       [VerifyCsrfMiddleware::class, RequireAuthMiddleware::class]],

    ['GET',  '/media/avatar/{filename}', [MediaController::class, 'avatar'], [RequireAuthMiddleware::class]],
];
