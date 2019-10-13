<?php

require_once 'MyController.php';

class ControllerMain extends MyController {

    //si l'utilisateur est conecté, redirige vers son profil.
    //sinon, produit la vue d'accueil.
    public function index() {
        if ($this->user_logged()) {
            $this->redirect("profile");
        } else {
            (new View("index"))->show();
        }
    }

    //gestion de la connexion d'un utilisateur
    public function login() {
        $username = '';
        $password = '';
        $errors = [];
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $errors = User::validate_login($username, $password);
            if (empty($errors)) {
                $this->log_user(User::get_user($username), "");
            }
        }
            
        (new View("login"))->show(array("username" => $username, "password" => $password, "errors" => $errors));
    }
    

    //gestion de l'inscription d'un utilisateur
    public function signup() {
        $username = '';
        $password = '';
        $password_confirm = '';
        $fullname = '';
        $email = '';
        $role = '';
        $birthdate = '';
        $errors = [];
        
        if (Utils::check_fields(['username', 'password', 'password_confirm', 'fullname', 'email', 'birthdate'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            if(isset($_POST['birthdate'])) {
                $birthdate = $_POST['birthdate'];
            }
            
            $user_in = User::createMember($username, Tools::my_hash($password), $fullname, $email, $birthdate);
            
            $errors = User::validateUnicity($user_in);
            $errors = array_merge($errors, $user_in->validate());
            $errors = array_merge($errors, User::validate_passwords($password, $password_confirm));
            
            if (count($errors) === 0) {
                $user_in->update();
                $this->log_user($user_in, "");
            }
        }
        
        
        (new View("signup"))->show(array("username" => $username,
                                         "password" => $password,
                                         "password_confirm" =>$password_confirm,
                                         "fullname" => $fullname,
                                         "email" => $email,
                                         "birthdate" => $birthdate,
                                         "errors" => $errors));
        
    }


}
