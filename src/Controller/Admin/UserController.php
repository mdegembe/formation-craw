<?php

declare(strict_types=1);

namespace Craw\Controller\Admin;

use Craw\Core\Audit;
use Craw\Core\Auth;
use Craw\Core\Database;
use Craw\Core\Request;
use Craw\Core\Response;
use Craw\Core\Session;
use Craw\Core\Validator;

final class UserController extends AdminController
{
    private const MIN_PASSWORD_LENGTH = 12;

    public function index(Request $request, array $params): Response
    {
        return $this->adminView('admin/users', [
            'title' => 'Utilisateurs — Administration',
            'activeAdminNav' => 'users',
            'users' => Database::select(
                'SELECT id, email, display_name, role, is_active, last_login_at, locked_until, created_at
                 FROM admin_users ORDER BY display_name ASC'
            ),
            'audit' => Database::select(
                'SELECT a.*, u.display_name
                 FROM audit_log a
                 LEFT JOIN admin_users u ON u.id = a.admin_user_id
                 ORDER BY a.id DESC LIMIT 40'
            ),
        ]);
    }

    public function store(Request $request, array $params): Response
    {
        $input = $request->all();
        unset($input['_token'], $input['password']);

        $password = (string) ($request->input('password') ?? '');

        $validator = Validator::make($request->all())
            ->required('email', 'Adresse e-mail')->email('email', 'Adresse e-mail')
            ->required('display_name', 'Nom affiché')->maxLength('display_name', 'Nom affiché', 120)
            ->in('role', 'Rôle', ['admin', 'gestionnaire'])
            ->required('password', 'Mot de passe')
            ->custom(
                'password',
                mb_strlen($password) >= self::MIN_PASSWORD_LENGTH,
                sprintf('Le mot de passe doit comporter au moins %d caractères.', self::MIN_PASSWORD_LENGTH)
            );

        if ($validator->fails()) {
            return $this->back('/admin/utilisateurs', $validator->errors(), $input);
        }

        $email = mb_strtolower((string) $request->input('email'));

        if (Database::selectOne('SELECT id FROM admin_users WHERE email = :email', ['email' => $email]) !== null) {
            return $this->back(
                '/admin/utilisateurs',
                ['email' => 'Un compte existe déjà avec cette adresse.'],
                $input
            );
        }

        $id = Database::insert('admin_users', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name' => (string) $request->input('display_name'),
            'role' => (string) ($request->input('role') ?? 'gestionnaire'),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Audit::record('user.create', 'admin_user', $id, [
            'email' => $email,
            'role' => (string) $request->input('role'),
        ], $request->ipHash());

        Session::flash('success', 'Compte créé.');

        return $this->redirect('/admin/utilisateurs');
    }

    public function toggle(Request $request, array $params): Response
    {
        $id = (int) $params['id'];

        if ($id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');

            return $this->redirect('/admin/utilisateurs');
        }

        $user = Database::selectOne('SELECT * FROM admin_users WHERE id = :id', ['id' => $id]);

        if ($user === null) {
            $this->notFound('Compte introuvable.');
        }

        $active = (int) $user['is_active'] === 1;

        // Ne jamais laisser l'application sans administrateur actif.
        if ($active && (string) $user['role'] === 'admin') {
            $remaining = (int) Database::scalar(
                "SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND is_active = 1 AND id <> :id",
                ['id' => $id]
            );

            if ($remaining === 0) {
                Session::flash('error', 'Impossible : ce compte est le dernier administrateur actif.');

                return $this->redirect('/admin/utilisateurs');
            }
        }

        Database::update('admin_users', [
            'is_active' => $active ? 0 : 1,
            'failed_attempts' => 0,
            'locked_until' => null,
        ], ['id' => $id]);

        Audit::record($active ? 'user.disable' : 'user.enable', 'admin_user', $id, [], $request->ipHash());

        Session::flash('success', $active ? 'Compte désactivé.' : 'Compte réactivé.');

        return $this->redirect('/admin/utilisateurs');
    }
}
