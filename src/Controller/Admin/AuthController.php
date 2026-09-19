<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Controller\BaseController;
use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Logger;
use Craw\Core\RateLimiter;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Validator;

final class AuthController extends BaseController
{
    public function showLogin(Request $request, array $params): Response
    {
        if (Auth::check()) {
            return $this->redirect('/admin');
        }

        return $this->view('admin/login', [
            'title' => 'Connexion — Administration',
            'noindex' => true,
        ]);
    }

    public function login(Request $request, array $params): Response
    {
        $email = (string) ($request->input('email') ?? '');
        $password = (string) ($request->input('password') ?? '');

        $validator = Validator::make($request->all())
            ->required('email', 'Adresse e-mail')
            ->required('password', 'Mot de passe');

        if ($validator->fails()) {
            return $this->back('/admin/connexion', $validator->errors(), ['email' => $email]);
        }

        // Limitation par compte, en plus de la limitation par IP du middleware :
        // un attaquant distribue ne doit pas pouvoir cribler un compte donne.
        $accountKey = 'login:' . mb_strtolower($email);

        if (RateLimiter::tooManyAttempts($accountKey, 8, 900)) {
            return $this->back(
                '/admin/connexion',
                [],
                ['email' => $email],
                'Trop de tentatives sur ce compte. Réessayez dans quelques minutes.'
            );
        }

        $result = Auth::attempt($email, $password);

        if (!$result['ok']) {
            RateLimiter::hit($accountKey, 900);

            Logger::warning('Echec de connexion au back-office.', [
                'email' => mb_substr($email, 0, 3) . '***',
                'ip' => $request->ipHash(),
            ]);

            return $this->back(
                '/admin/connexion',
                [],
                ['email' => $email],
                $result['message'] ?? 'Identifiants incorrects.'
            );
        }

        RateLimiter::clear($accountKey);
        Audit::record('auth.login', 'admin_user', Auth::id(), [], $request->ipHash());

        Session::flash('success', 'Bienvenue, ' . (string) (Auth::user()['display_name'] ?? '') . '.');

        return $this->redirect('/admin');
    }

    public function logout(Request $request, array $params): Response
    {
        Audit::record('auth.logout', 'admin_user', Auth::id(), [], $request->ipHash());
        Auth::logout();

        Session::flash('info', 'Vous êtes déconnecté.');

        return $this->redirect('/admin/connexion');
    }
}
