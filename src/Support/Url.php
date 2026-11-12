<?php

declare(strict_types=1);

namespace DataForge\Support;

/**
 * Construccion de URLs de la aplicacion.
 *
 * Centralizar las rutas evita que el destino de un formulario y el token CSRF
 * se construyan de forma distinta.
 */
final class Url
{
    public static function home(): string
    {
        return '/';
    }

    public static function docs(): string
    {
        return '/docs';
    }

    public static function about(): string
    {
        return '/about';
    }

    public static function dashboard(): string
    {
        return '/dashboard';
    }

    public static function login(): string
    {
        return '/login';
    }

    public static function register(): string
    {
        return '/register';
    }

    public static function logout(): string
    {
        return '/logout';
    }

    public static function onboarding(): string
    {
        return '/onboarding';
    }

    public static function profile(): string
    {
        return '/profile';
    }

    public static function profileDelete(): string
    {
        return '/profile/delete';
    }

    public static function profilePassword(): string
    {
        return '/profile/password';
    }

    public static function profileTheme(): string
    {
        return '/profile/theme';
    }

    public static function profileMode(): string
    {
        return '/profile/mode';
    }

    public static function databases(): string
    {
        return '/databases';
    }

    public static function databaseCreate(): string
    {
        return '/databases';
    }

    public static function database(string $database): string
    {
        return '/databases/'.rawurlencode($database);
    }

    public static function databaseDelete(string $database): string
    {
        return '/databases/'.rawurlencode($database).'/delete';
    }

    public static function databaseExport(string $database): string
    {
        return '/databases/'.rawurlencode($database).'/export.sql';
    }

    public static function tableCreate(string $database): string
    {
        return '/databases/'.rawurlencode($database).'/tables';
    }

    public static function tableCreateForm(string $database): string
    {
        return '/databases/'.rawurlencode($database).'/tables/new';
    }

    public static function table(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table);
    }

    public static function tableStructure(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/structure';
    }

    public static function tableStructureSave(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/structure';
    }

    public static function tableDelete(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/delete';
    }

    public static function columnDelete(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/columns/delete';
    }

    public static function recordCreate(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/records';
    }

    public static function recordCreateForm(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/records/new';
    }

    public static function record(string $database, string $table, int $id): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/records/'.$id;
    }

    public static function recordUpdate(string $database, string $table, int $id): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/records/'.$id;
    }

    public static function recordDelete(string $database, string $table, int $id): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/records/'.$id.'/delete';
    }

    public static function tableExport(string $database, string $table): string
    {
        return '/databases/'.rawurlencode($database).'/tables/'.rawurlencode($table).'/export.csv';
    }

    public static function query(): string
    {
        return '/query';
    }

    public static function activity(): string
    {
        return '/activity';
    }

    public static function avatar(string $filename): string
    {
        return '/media/avatar/'.rawurlencode($filename);
    }
}
