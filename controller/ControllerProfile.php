<?php

require_once 'MyController.php';

class ControllerProfile extends MyController {
    //put your code here
    public function index() {
        $user = $this->get_user_or_redirect();
        $rentals = Rental::get_current_rentals_by_user($user->getId());

        (new View('profile_home'))->show(array(
            "logged_username" => $user->getUsername(),
            "fullname" => $user->getFullname(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "rentals" => $rentals
        ));
    }
    
    public function users() {
        $user = $this->get_user_or_redirect();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            Tools::abort("You must have the 'manager' or the 'admin' role");
        }

        $users = User::get_users();
        
        (new View('profile_users'))->show(array(
            "logged_userid" => $user->getId(),
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "isAdmin" => $user->isAdmin(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "users" => $users
        ));
    }
    
    public function delete() {
        $user = $this->get_user_or_redirect();
       
        if(!$user->isAdmin()) {
            Tools::abort("You must have the 'admin' role");
        }
         
        if (Utils::check_fields(['param1'], $_GET)) {
            $id = $_GET['param1'];
            
            if($user->getId() === $id) {
                Tools::abort("You may not delete yourself!");
            }
            
            $userToDelete = User::byId($id);
            
            if(is_bool($userToDelete)) {
                Tools::abort("Unknown user");
            }
            
        } else {
            $this->redirect('profile', 'users');
        }
        
        if(Utils::check_fields(['confirm'])) {
            // delete the user
            $userToDelete->delete();
            $this->redirect('profile', 'users');
            
        } else if(Utils::check_fields(['cancel'])) {
            $this->redirect('profile', 'users');
        }

        (new View('profile_delete_user'))->show(array(
            "logged_username" => $user->getUsername(),
            "usrDelFullname" => $userToDelete->getFullname(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
        ));
        
    }
    
    public function edit() {
        if(Utils::check_fields(['cancel'])) {
            $this->redirect('profile', 'users');
        }
        
        $user = $this->get_user_or_redirect();
        
        if(!$user->isAdmin() && Utils::check_fields(['role'])) {
            Tools::abort("You may not change the role since you're not an admin.");
        }
        
        if(!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Unknown user');
        } else {
            $id = $_GET['param1'];
            $toEdit = User::byId($id);
            
            if(is_bool($toEdit)) {
                Tools::abort('Unknown user');
            }
        }
        
        $errors = [];
        if(Utils::check_fields(['save',
            'username','fullname','email','birthdate'])) {
            
            $username = $_POST['username'];
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $birthdate = $_POST['birthdate'];
            $role = $toEdit->getRole();
            
            if($user->isAdmin() && Utils::check_fields(['role'])) {
                $role = $_POST['role'];
                
                // si j'édite un user existant et que je mets un rôle différent
                // d'admin alors que le rôle courant de ce user est admin, et si
                // c'est le seul admin en base de données,
                // alors je dois déclencher une erreur:
                if($toEdit->getRole() === 'admin'
                && $role !== 'admin'
                && User::count_admins() == 1)
                {
                    $errors[] = "You're the last admin in the system: 
                        you must keep your role";
                }
            }
            
            // validate user for update
            
            $edited = User::createUser(
                    $toEdit->getId(),
                    $username,
                    $toEdit->getHashed_password(),
                    $fullname,
                    $email,
                    $birthdate,
                    $role);
            
//            var_dump('edited', $edited);
            
            // validate unicity
            $errors = array_merge($errors, User::validateUnicityEdit(
                    $edited,
                    $toEdit->getUsername() !== $edited->getUsername(),
                    $toEdit->getEmail() !== $edited->getEmail()));
            
            // validate user
            $errors = array_merge($errors, $edited->validate());
            
            if(count($errors) === 0) {
                // Si le user dont on a reçu l'id dans l'url est le user connecté
                // et si son rôle ou son username ont changé,
                // mettre à jour la session en reloguant l'utilisateur, sans
                // faire de redirection
                
                if ($user->getId() === $edited->getId()
                && ($user->getUsername() !== $edited->getUsername()
                    || $user->getRole() !== $edited->getRole()))
                {
                    $_SESSION["user"] = $edited;
                    session_write_close();
                }
                
                // sauver l'utilisateur modifié
                $edited->update();
                
                $user = $this->get_user_or_redirect();
                
                // si à cause d'un update du rôle on est devenu un membre,
                // rediriger vers le profile
                if($user->isMember()) {
                    $this->redirect('profile', 'index');
                }
                
                $this->redirect('profile', 'users');
                
            } else {
                $toEdit = $edited;
            }
            
        }
        
        
        (new View("profile_edit"))->show(array(
            "id" => $toEdit->getId(),
            "username" => $toEdit->getUsername(),
            "fullname" => $toEdit->getFullname(),
            "email" => $toEdit->getEmail(),
            "birthdate" => $toEdit->getBirthdate(),
            "role" => $toEdit->getRole(),
            
            "errors" => $errors,
            
            "logged_username" => $user->getUsername(),
            "isAdmin" => $user->isAdmin(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager()
        ));
    }
    
    public function add() {
//        var_dump($_POST);
        
        if(Utils::check_fields(['cancel'])) {
            $this->redirect('profile', 'users');
        }
 
        $user = $this->get_user_or_redirect();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            Tools::abort("You must have the 'manager' or the 'admin' role");
        }
        
        if(!$user->isAdmin() && Utils::check_fields(['role'])) {
            Tools::abort("You may not specify the role since you're not an admin.");
        }
        
        $username = '';
        $fullname = '';
        $email = '';
        $birthdate = '';
        $role = '';
        
        $errors = [];

        if (Utils::check_fields(['save',
            'username','fullname','email','birthdate'])) {
            
            $username = trim($_POST['username']);
            $password = $username;
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $birthdate = $_POST['birthdate'];
            
            if($user->isAdmin() && Utils::check_fields(['role'])) {
                $role = $_POST['role'];
            } else {
                $role = 'member';
            }
            
            $toAdd = User::createUser(null, $username, Tools::my_hash($password), $fullname, $email, $birthdate, $role);
            
            $errors = User::validateUnicity($toAdd);
            $errors = array_merge($errors, $toAdd->validate());

            if (count($errors) == 0) {
                $toAdd->update(); //sauve l'utilisateur
                $this->redirect('profile/users');
            }
        }
        
        (new View("profile_add"))->show(array(
            "username" => $username,
            "fullname" => $fullname,
            "email" => $email,
            "birthdate" => $birthdate,
            "role" => $role,
            
            "errors" => $errors,
            
            "logged_username" => $user->getUsername(),
            "isAdmin" => $user->isAdmin(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager()
        ));
    }

    function ajaxUsernameAvailable() {
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        if(!$isAdminOrManager) {
            header("HTTP/1.1 403 Forbidden");
            echo "You must have the 'admin' or 'manager' role";
            die();
        }
        
        // get the username param
        if(!Utils::check_fields(['username'])
                || $_POST["username"] === ""
                ) {
            header("HTTP/1.1 403 Forbidden");
            echo "username param required";
            die();
        }
        
        $username = $_POST["username"];
        
        if(Utils::check_fields(['editusername'])
                && is_numeric($_POST["editusername"])) {
            $exceptId = $_POST['editusername'];
            
            $toEdit = User::byId($exceptId);

            if (is_bool($toEdit)) {
                header("HTTP/1.1 403 Forbidden");
                echo "Unknown user";
                die();
            }
            
            $exceptUsername = $toEdit->getUsername();
        }
        
        try {
            if(isset($exceptUsername) && $username === $exceptUsername) {
                $username_is_available = TRUE;
            } else {
                $username_is_available = User::isUsernameAvailable($username);
            }
        } catch (Exception $exc) {
            header("HTTP/1.1 500 Internal Server Error"); // failed
            echo "Unexpected server exception";
            die();
        }
        
        header("HTTP/1.1 200 OK"); // success
        if($username_is_available) {
            echo "true";
        } else {
            echo "false";
        }
        
    }
    
    function ajaxEmailAvailable() {
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        if(!$isAdminOrManager) {
            header("HTTP/1.1 403 Forbidden");
            echo "You must have the 'admin' or 'manager' role";
            die();
        }
        
        // get the email param
        if(!Utils::check_fields(['email'])
                || $_POST["email"] === ""
                ) {
            header("HTTP/1.1 403 Forbidden");
            echo "email param required";
            die();
        }
        
        $email = $_POST["email"];
        
        if(Utils::check_fields(['editemail'])
                && is_numeric($_POST["editemail"])) {
            $exceptId = $_POST['editemail'];
            
            $toEdit = User::byId($exceptId);

            if (is_bool($toEdit)) {
                header("HTTP/1.1 403 Forbidden");
                echo "Unknown user";
                die();
            }
            
            $exceptEmail = $toEdit->getEmail();
        }
        
        try {
            if(isset($exceptEmail) && $email === $exceptEmail) {
                $email_is_available = TRUE;
            } else {
                $email_is_available = User::isEmailAvailable($email);
            }
        } catch (Exception $exc) {
            header("HTTP/1.1 500 Internal Server Error"); // failed
            echo "Unexpected server exception";
            die();
        }
        
        header("HTTP/1.1 200 OK"); // success
        if($email_is_available) {
            echo "true";
        } else {
            echo "false";
        }
        
    }

    public function logoff() {
        $this->logout();
    }
    
}
