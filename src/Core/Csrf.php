<?php

declare(strict_types=1);

namespace Craw\Core;

use Craw\Core\Exception\HttpException;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }

        return $token;
    }

    /** Champ pret a inserer dans un formulaire. */
    public static function field(): string
    {
        return sprintf('<input type="hidden" name="_token" value="%s">', e(self::token()));
    }

    public static function isValid(?string $candidate): bool
    {
        $token = Session::get(self::KEY);

        if (!is_string($token) || $token === '' || $candidate === null || $candidate === '') {
            return false;
        }

        return hash_equals($token, $candidate);
    }

    /**
     * @throws HttpException
     */
    public static function verify(Request $request): void
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $candidate = $request->input('_token') ?? $request->header('X-CSRF-Token');

        if (!self::isValid($candidate)) {
            throw new HttpException(419, 'Votre session a expire. Merci de renvoyer le formulaire.');
        }
    }

    /** Fait tourner le jeton — appele apres une connexion reussie. */
    public static function rotate(): void
    {
        Session::forget(self::KEY);
    }
}
