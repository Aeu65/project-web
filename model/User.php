<?php

require_once 'model/MyModel.php';

class User extends MyModel {
    private $id;
    private $username;
    private $hashed_password;
    private $fullname;
    private $email;
    private $birthdate;
    private $role;
    
    private static $roles = array('member','manager', 'admin');
    
    // private because we do not directly create a User:
    // we create Members, Managers and Admins or retrieve them from db
    private function __construct($id, $username, $hashed_password, $fullname, $email,
                                $birthdate, $role) {
	    $this->id = $id;
            $this->username = $username;
            $this->hashed_password = $hashed_password;
	    $this->fullname = $fullname;
            $this->email = $email;
            $this->birthdate = $birthdate;
            $this->role = $role;
    }
    
    public static function createMember($username, $hashed_password, $fullname, $email,
                                $birthdate) {
        return self::createUser(
                null,
                $username,
                $hashed_password,
                $fullname,
                $email,
                $birthdate,
                'member');
    }
    
    public static function createUser($id, $username, $hashed_password, $fullname, $email,
                                $birthdate, $role) {
        return new self(
                        $id, $username, $hashed_password, $fullname, $email,
                        $birthdate, $role);
    }
    
    // renvoie un tableau d'erreur(s) 
    // le tableau est vide s'il n'y a pas d'erreur.
    // ne s'occupe que de la validation "métier" des champs obligatoires (le pseudo)
    // les autres champs (mot de passe) sont gérés par d'autres
    // méthodes.
    public function validate(){
        $errors = array();
        
        // validate username
        if (!(isset($this->username) && is_string($this->username) && strlen($this->username) > 0)) {
            $errors[] = "Username is required.";
        } if (!(isset($this->username) && is_string($this->username) && strlen($this->username) >= 3 && strlen($this->username) <= 16)) {
            $errors[] = "Username length must be between 3 and 16.";
        } if (!(isset($this->username) && is_string($this->username) && preg_match("/^[a-zA-Z][a-zA-Z0-9]*$/", $this->username))) {
            $errors[] = "Username must start by a letter and must contain only letters and numbers.";
        }
        
        // validate fullname
        if (!(isset($this->fullname) && is_string($this->fullname) && strlen($this->fullname) > 0)) {
            $errors[] = "Fullname is required.";
        } if (!(isset($this->username) && is_string($this->username) && strlen($this->username) <= 255)) {
            $errors[] = "Fullname too long(255 characters max).";
        }
        
        // validate email
        if (!(isset($this->email) && is_string($this->email) && strlen($this->email) > 0)) {
            $errors[] = "Email is required.";
        } if (!filter_var($this->email, FILTER_VALIDATE_EMAIL) || strlen($this->email) > 64) {
            $errors[] = "Email has no valid format or is too long (max 64 characters).";
        }
        
        // validate role
        if (!(isset($this->role) && is_string($this->role) && strlen($this->role) > 0)) {
            $errors[] = "Role is required.";
        } elseif (!in_array($this->role, self::$roles, true)) {
            $errors[] = "Wrong Role";
        }
        
        // validate birthdate
        if($this->birthdate !== NULL && is_string($this->birthdate) && strlen($this->birthdate) > 0) {
            if(Utils::is_valid_date($this->birthdate)) {
                
                $birthdate = new DateTime(Utils::get_date($this->birthdate));
                $now = new DateTime("now");
                
                if($birthdate > $now) {
                    $errors[] = "Birthdate in the future. Are you... the Terminator?";
                }
            } else {
                $errors[] = "Date format should be YYYY-MM-DD";
            }
        }
        
        return $errors;
    }
    
    public static function isEmailAvailable($email) {
        try{
            $stmt = self::execute("SELECT * FROM user WHERE email =:email",
                    array("email"=>$email));
            $result = $stmt->fetchAll();
            return count($result) === 0;
        
        } catch (Exception $e) {
            Tools::abort("Error while accessing database. Please contact your administrator.");
        }
    }
    
    public static function isUsernameAvailable($username) {
        try {
            $stmt = self::execute("SELECT * FROM user where username = :username",
                array("username" => $username));
            $result = $stmt->fetchAll();
            return count($result) === 0;
            
        } catch (Exception $e) {
            Tools::abort("Error while accessing database. Please contact your administrator.");
        }
    }
    
    public static function validateUnicity(User $user) {
        $errors = [];
        if(!User::isUsernameAvailable($user->getUsername())) {
            $errors[] = "This username is unavailable";
        }
         if(!User::isEmailAvailable($user->getEmail())) {
            $errors[] = "An account has already been registered with this email address.";
        }
        
        return $errors;
    }
    
    public static function validateUnicityEdit(User $user,
            $changedUsername = false, $changedEmail = false) {
        
        $errors = [];
        if($changedUsername && !User::isUsernameAvailable($user->getUsername())) {
            $errors[] = "This username is unavailable";
        }
         if($changedEmail && !User::isEmailAvailable($user->getEmail())) {
            $errors[] = "An account has already been registered with this email address.";
        }
        
        return $errors;
    }
    
    private static function validate_password($password){
        $errors = [];
        if (strlen($password) < 8 || strlen($password) > 16) {
            $errors[] = "Password length must be between 8 and 16.";
        } if (!((preg_match("/[A-Z]/", $password)) && preg_match("/\d/", $password) && preg_match("/['\";:,.\/?\\-]/", $password))) {
            $errors[] = "Password must contain one uppercase letter, one number and one punctuation mark.";
        }
        return $errors;
    }
    
    public static function validate_passwords($password, $password_confirm){
        $errors = User::validate_password($password);
        if ($password != $password_confirm) {
            $errors[] = "You have to enter twice the same password.";
        }
        return $errors;
    }
    
    public static function get_user($username) {
        $query = self::execute("SELECT * FROM user where username = :username", array("username"=>$username));
        $data = $query->fetch(); // un seul résultat au maximum
        if ($query->rowCount() == 0) {
            return false;
        } else {
            return new User(
                    $data["id"],
                    $data["username"],
                    $data["password"],
                    $data["fullname"],
                    $data["email"],
                    $data["birthdate"],
                    $data["role"]);
                    
        }
    }
    
    public static function get_users() {
        $stmt = self::execute("SELECT * from user order by fullname asc", []);
        $data = $stmt->fetchAll();
        
        $users = [];
        
        foreach ($data as $user) {
            $users[] = new User(
                    $user["id"],
                    $user['username'],
                    $user["password"],
                    $user["fullname"],
                    $user["email"],
                    $user["birthdate"],
                    $user["role"]);
        }
        
        return $users;
    }
    
    // get the user by id
    public static function byId($id) {
        $query = self::execute("SELECT * FROM user where id = :id", array("id"=>$id));
        $data = $query->fetch(); // un seul résultat au maximum
        if ($query->rowCount() == 0) {
            return false;
        } else {
            return new User(
                    $data["id"],
                    $data["username"],
                    $data["password"],
                    $data["fullname"],
                    $data["email"],
                    $data["birthdate"],
                    $data["role"]);
                    
        }
    }
    
    public function delete() {
        Rental::deleteAllByUserId($this->id);
        self::deleteById($this->id);
    }
    
    private static function deleteById($id) {
        $query = self::execute("DELETE FROM user where id = :id", array("id"=>$id));
    }
    
    public static function count_admins() {
        $query = self::execute("SELECT COUNT(*) as nb FROM user
            where role = 'admin'", []);
        $res = $query->fetch();
        return $res['nb'];
    }
    
    //indique si un mot de passe correspond à son hash
    private static function check_password($clear_password, $hash) {
        return $hash === Tools::my_hash($clear_password);
    }
    
    //renvoie un tableau d'erreur(s) 
    //le tableau est vide s'il n'y a pas d'erreur.
    public static function validate_login($username, $password) {
        $errors = [];
        $member = self::get_user($username);
        if ($member) {
            if (!self::check_password($password, $member->hashed_password)) {
                $errors[] = "Wrong password. Please try again.";
            }
        } else {
            $errors[] = "Can't find a member with the pseudo '$username'. Please sign up.";
        }
        return $errors;
    }
    
    private function addUser(){
        if (empty($this->birthdate)) {
            $this->birthdate = null;
        }
        
        $stmt = self::execute("INSERT INTO user(username,password, fullname, email, birthdate, role)
                     VALUES(:username,:password, :fullname, :email, :birthdate, :role)",
            array(
                "username" => $this->username, 
                "password" => $this->hashed_password,
                "fullname" => $this->fullname,
                "email" => $this->email,
                "birthdate" => $this->birthdate,
                "role" => $this->role
            )
        );
        return self::lastInsertId();
    }
    
    private function updateUser() {
        if (empty($this->birthdate)) {
            $this->birthdate = null;
        }
        
        $stmt = self::execute(
                "UPDATE user SET
                    username=:username,
                    fullname=:fullname,
                    email=:email, 
                    birthdate=:birthdate,
                    role=:role
                WHERE
                    id=:id",
            array(
                "username" => $this->getUsername(),
                "fullname" => $this->getFullname(),
                "email" => $this->getEmail(),
                "birthdate" => $this->getBirthdate(),
                "role" => $this->getRole(),
                "id" => $this->getId()
            ));
        
    }
    
    public function update() {
        if(null == $this->getId()) {
            $inserted_id = $this->addUser();
            $this->id = $inserted_id;
        } else {
            $this->updateUser();
        }
    }
    
    public function getId() {
        return $this->id;
    }

    public function getFullname() {
        return $this->fullname;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    function getHashed_password() {
        return $this->hashed_password;
    }
        
    function getEmail() {
        return $this->email;
    }

    function getBirthdate() {
        return $this->birthdate;
    }

    public function getRole() {
        return $this->role;
    }

    public function isAdmin() {
        return $this->getRole() === 'admin';
    }

    public function isManager() {
        return $this->getRole() === 'manager';
    }

    public function isMember() {
        return $this->getRole() === 'member';
    }



    
    
}
