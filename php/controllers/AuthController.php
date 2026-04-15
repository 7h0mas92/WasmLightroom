<?php


require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/functions.php';

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(): void
    {
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlash('error', 'Veuillez remplir tous les champs.');
            redirect('/index.php?route=login');
        }

        $user = $this->userModel->authenticate($email, $password);

        if (!$user) {
            setFlash('error', 'Email ou mot de passe incorrect.');
            redirect('/index.php?route=login');
        }

        $_SESSION['user_id']  = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];

        setFlash('success', 'Bienvenue, ' . $user['username'] . ' !');
        redirect('/index.php?route=feed');
    }

    public function showRegister(): void
    {
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        $username        = trim($_POST['username'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            setFlash('error', 'Veuillez remplir tous les champs.');
            redirect('/index.php?route=register');
        }

        if ($password !== $passwordConfirm) {
            setFlash('error', 'Les mots de passe ne correspondent pas.');
            redirect('/index.php?route=register');
        }

        if (strlen($password) < 6) {
            setFlash('error', 'Le mot de passe doit faire au moins 6 caractères.');
            redirect('/index.php?route=register');
        }

        if ($this->userModel->findByEmail($email)) {
            setFlash('error', 'Cet email est déjà utilisé.');
            redirect('/index.php?route=register');
        }

        if ($this->userModel->findByUsername($username)) {
            setFlash('error', 'Ce nom d\'utilisateur est déjà pris.');
            redirect('/index.php?route=register');
        }

        $userId = $this->userModel->create($username, $email, $password);

        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;

        setFlash('success', 'Compte créé avec succès !');
        redirect('/index.php?route=feed');
    }

    public function logout(): void
    {
        session_destroy();
        redirect('/index.php?route=login');
    }
}
