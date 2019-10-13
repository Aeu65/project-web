<?php

require_once 'MyController.php';

class ControllerRental extends MyController {
    
    public function index() {
        $user = $this->get_user_or_redirect();
        
        $this->redirect('rental', 'books', $user->getUsername());
    }
    
    // param1 = 'basket is for' username (mandatory username)
    // param2 = filter (mandatory; or, if error is set, must be 'nofilter')
    // param3 = error (optional)
    
    // petite explication
    
    // rental/books/ben => pas de filtre, pas d'erreur
    // rental/books/ben/kdkdkieksi => filtre, pas d'erreur
    // rental/books/ben/nofilter/kdiekeidiek => pas de filtre, erreur
    // rental/books/ben/kdiekeid/kdiekeidkdi => filtre et erreur

    // param1 = basketIsFor
    // param2 = encoded filter
    public function books() {
        $user = $this->get_user_or_redirect();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        $isAdmin = $user->isAdmin();
        $errors = [];
        
        // get the user the basket is for
        if(!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('User param required');
        } else {
            $basketisfor = $_GET['param1'];
            
            if($basketisfor !== $user->getUsername()) {
                // simple security check
                if(!$isAdminOrManager) {
                    Tools::abort("You must have the 'admin' or 'manager' role");
                }
                
                // retrieve 'basket is for' user
                $basketIsFor = User::get_user($basketisfor);
                
                if(is_bool($basketIsFor)) {
                    Tools::abort('Unknown user (to prepare a basket)');
                }
            } else {
                $basketIsFor = $user;
            }
        }
        
        // create filter
        if(isset($_POST['q']) && is_string($_POST['q'])) {
            $filter = $_POST['q'];
            
            if(empty($filter)) {
                // post-redirect-get
                $this->redirect('rental', 'books', $basketIsFor->getUsername());
            } else {
                $filter = Utils::url_safe_encode($filter);
                
                $this->redirect('rental', 'books',
                    $basketIsFor->getUsername(), $filter);
            }
        }
        
        // read & apply filter
        if(Utils::check_fields(['param2'], $_GET)
                && $_GET['param2'] !== 'nofilter') {
            
            $filter = Utils::url_safe_decode($_GET['param2']);
            if(!$filter) {
                $errors[] = 'bad filter encoding';
            }
        }
        
        // read & transmit errors
        if(Utils::check_fields(['param3'], $_GET)) {
            $error = Utils::url_safe_decode($_GET['param3']);
            if(!$error) {
                $errors[] = 'bad error encoding';
            } else if($error ) {
                $errors[] = $error;
            }
        }
        
        // get the users
        // this is for the 'basket is for' feature
        $users = [];
        if($isAdminOrManager) {
            $users = User::get_users();
        }
        
        // get available books for the 'basket is for' user
        if(!empty($filter)) {
            $available = Book::notInBasketFiltered($basketIsFor->getId(), $filter);
        } else {
            $available = Book::notInBasket($basketIsFor->getId());
        }
        
        // get basket
        $basket = Book::inBasket($basketIsFor->getId());
        
        $slash_encoded_filter = "";
        if (!empty($filter)) {
            $slash_encoded_filter = "/" . Utils::url_safe_encode($filter);
        }
        
        (new View('rental_books'))->show(array(
            "logged_username" => $user->getUsername(),
            "fullname" => $user->getFullname(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $isAdminOrManager,
            "isAdmin" => $isAdmin,
            "available" => $available,
            "basket" => $basket,
            "basketIsFor" => $basketIsFor,
            "errors" => $errors,
            "users" => $users,
            "filter" => isset($filter) ? $filter : '',
            "slash_encoded_filter" => $slash_encoded_filter
        ));
    }
    
    // requested POST params:
    //      basketisfor
    // optional POST params:
    //      textfilter (encoded filter)
    function ajaxAvailableBooks() {
    
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        $isAdmin = $user->isAdmin();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        // get the user the basket is for
        if(!Utils::check_fields(['basketisfor'])) {
            header("HTTP/1.1 403 Forbidden");
            echo "basketisfor param required";
            die();
        }
            
        $basketisfor = $_POST['basketisfor'];
        
        if($basketisfor !== $user->getUsername()) {
            
            // simple security check
            if(!$isAdminOrManager) {
                header("HTTP/1.1 403 Forbidden");
                echo "You must have the 'admin' or 'manager' role";
                die();
            }

            // retrieve 'basket is for' user
            $basketIsFor = User::get_user($basketisfor);

            if(is_bool($basketIsFor)) {
                header("HTTP/1.1 403 Forbidden");
                echo "Unknown user (to prepare a basket)";
                die();
            }
        } else {
            $basketIsFor = $user;
        }
        
        // read & apply filter
        if(Utils::check_fields(['textfilter'])) {
            $filter = Utils::url_safe_decode($_POST['textfilter']);
            if(!$filter) {
                header("HTTP/1.1 400 Bad Request");
                echo "Bad filter encoding";
                die();
            }
        }
        
        // get available books for the 'basket is for' user
        if(!empty($filter)) {
            try {
                $available
                    = Book::notInBasketFiltered($basketIsFor->getId(), $filter);
            } catch (Exception $exc) {
                header("HTTP/1.1 500 Internal Server Error"); // failed
                echo "Unexpected server exception";
                die();
            }
        } else {
            try {
                $available = Book::notInBasket($basketIsFor->getId());
            } catch (Exception $exc) {
                header("HTTP/1.1 500 Internal Server Error"); // failed
                echo "Unexpected server exception";
                die();
            }
        }
        
        $slash_encoded_filter = "";
        if (!empty($filter)) {
            $slash_encoded_filter = "/" . Utils::url_safe_encode($filter);
        }
        
        header("HTTP/1.1 200 OK"); // success
        (new View('ajax_available_books'))->show(array(
            "isAdmin" => $isAdmin,
            "available" => $available,
            "basketIsFor" => $basketIsFor,
            "slash_encoded_filter" => $slash_encoded_filter
        ));
    }
    
    // requested POST params:
    //      basketisfor
    // optional POST params:
    //      textfilter (encoded filter)
    function ajaxBasket() {
        
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        $isAdmin = $user->isAdmin();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        // get the user the basket is for
        if(!Utils::check_fields(['basketisfor'])) {
            header("HTTP/1.1 403 Forbidden");
            echo "basketisfor param required";
            die();
        }
            
        $basketisfor = $_POST['basketisfor'];
        
        if($basketisfor !== $user->getUsername()) {
            
            // simple security check
            if(!$isAdminOrManager) {
                header("HTTP/1.1 403 Forbidden");
                echo "You must have the 'admin' or 'manager' role";
                die();
            }

            // retrieve 'basket is for' user
            $basketIsFor = User::get_user($basketisfor);

            if(is_bool($basketIsFor)) {
                header("HTTP/1.1 403 Forbidden");
                echo "Unknown user (to prepare a basket)";
                die();
            }
        } else {
            $basketIsFor = $user;
        }
        
        // read & apply filter
        if(Utils::check_fields(['textfilter'])) {
            $filter = Utils::url_safe_decode($_POST['textfilter']);
            if(!$filter) {
                header("HTTP/1.1 400 Bad Request");
                echo "Bad filter encoding";
                die();
            }
        }
        
        // get basket
        $basket = Book::inBasket($basketIsFor->getId());
        
        $slash_encoded_filter = "";
        if (!empty($filter)) {
            $slash_encoded_filter = "/" . Utils::url_safe_encode($filter);
        }
        
        (new View('ajax_basket'))->show(array(
            "isAdmin" => $isAdmin,
            "basket" => $basket,
            "basketIsFor" => $basketIsFor,
            "slash_encoded_filter" => $slash_encoded_filter
        ));
    }
    
    // here the filter is param1
    public function basketisfor() {
        $this->get_user_or_redirect();
        
        if(!isset($_POST['user'])) {
            Tools::abort('missing user param');
        }
        
        $filter = '';
        if(Utils::check_fields(['param1'], $_GET)) {
            $filter = $_GET['param1'];
        }
        
        if(empty($filter)) {
            $this->redirect('rental', 'books', $_POST['user']);
        } else {
            $this->redirect('rental', 'books', $_POST['user'], $filter);
        }
    }
    
    public function addtobasket() {
        $user = $this->get_user_or_redirect();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        if(!Utils::check_fields(['id'])) {
            Tools::abort('Adding to basket requires a book id');
        }
        $book_id = $_POST['id'];
        
        if(!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Adding to basket requires a target user');
        } else {
            $basketisfor = $_GET['param1'];
            
            if($basketisfor !== $user->getUsername()) {
                // simple security check
                if(!$isAdminOrManager) {
                    Tools::abort("You must have the 'admin' or 'manager' role");
                }
                
                // retrieve 'basket is for' user
                $basketIsFor = User::get_user($basketisfor);
                
                if(is_bool($basketIsFor)) {
                    Tools::abort('Unknown user (to prepare a basket)');
                }
            } else {
                $basketIsFor = $user;
            }
        }
        
        $filter = '';
        if(Utils::check_fields(['param2'], $_GET)) {
            $filter = $_GET['param2'];
        }
        
        // check regle métier 'max nb de livres en location'
        if(!Rental::canAddToBasket($basketIsFor->getId())) {
            $error = 'max '.Configuration::get('max_items_in_basket') .' book rentals';
            $filter = '' === $filter ? 'nofilter' : $filter;
            $this->redirect('rental', 'books', $basketIsFor->getUsername(),
                    $filter, Utils::url_safe_encode($error));
        }
        
        if(!Book::checkAvailabilitiesBook($book_id)){
            $error = 'Book not available';
            $filter = '' === $filter ? 'nofilter' : $filter;
            $this->redirect('rental', 'books', $basketIsFor->getUsername(),
                    $filter, Utils::url_safe_encode($error));
        }
        
        $toBasket = Rental::createRental(null, $basketIsFor, $book_id, null, null);
        $toBasket->upsert();
        
        $this->redirect('rental', 'books', $basketIsFor->getUsername(), $filter);
    }

    public function removefrombasket() {
        $user = $this->get_user_or_redirect();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();

        if(!isset($_POST['id'])) {
            Tools::abort('Removing from basket requires a book id');
        }
        $book_id = $_POST['id'];
        
        if(!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Removing from basket requires a target user');
        } else {
            $basketisfor = $_GET['param1'];
            
            if($basketisfor !== $user->getUsername()) {
                // simple security check
                if(!$isAdminOrManager) {
                    Tools::abort("You must have the 'admin' or 'manager' role");
                }
                
                // retrieve 'basket is for' user
                $basketIsFor = User::get_user($basketisfor);
                
                if(is_bool($basketIsFor)) {
                    Tools::abort('Unknown user (to prepare a basket)');
                }
            } else {
                $basketIsFor = $user;
            }
        }
        
        $filter = '';
        if(Utils::check_fields(['param2'], $_GET)) {
            $filter = $_GET['param2'];
        }
        
        Rental::removeFromBasket($basketIsFor->getId(), $book_id);
        
        $this->redirect('rental', 'books',
                $basketIsFor->getUsername(), $filter);
    }
    
    public function managebasket() {
        $user = $this->get_user_or_redirect();
        $isAdminOrManager = $user->isAdmin() || $user->isManager();
        
        if(!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Clearing or confirming basket requires a target user');
        } else {
            $basketisfor = $_GET['param1'];
            
            if($basketisfor !== $user->getUsername()) {
                // simple security check
                if(!$isAdminOrManager) {
                    Tools::abort("You must have the 'admin' or 'manager' role");
                }
                
                // retrieve 'basket is for' user
                $basketIsFor = User::get_user($basketisfor);
                
                if(is_bool($basketIsFor)) {
                    Tools::abort('Unknown user (to prepare a basket)');
                }
            } else {
                $basketIsFor = $user;
            }
        }
        
        if(isset($_POST['clear'])) {
            Rental::clearBasket($basketIsFor->getId());
            
        } else if(isset($_POST['confirm'])) {
            Rental::confirmBasket($basketIsFor->getId());
        }
                
        if(Utils::check_fields(['param2'], $_GET)) {
            $this->redirect('rental', 'books', $basketIsFor->getUsername(), $_GET['param2']);
        } else {
            $this->redirect('rental', 'books', $basketIsFor->getUsername());
        }
    }
    
    public function managereturns() {
        $user = $this->get_user_or_redirect();

        if (!$user->isAdmin() && !$user->isManager()) {
            Tools::abort("You must have the 'manager'
                        or the 'admin' role");
        }

        if (Utils::check_fields(['cancel'])) {
            $this->redirect('rental', 'bookReturn');
        }

//        var_dump($_GET, $_POST);
//        die();
        
        $usrSearch = '';
        $bookSearch = '';
        $dateSearch = '';
        $state = '';
        
        // if the user filtered the entries
        if (   isset($_POST['searchByMember'])
            && isset($_POST['searchByBook'])
            && isset($_POST['searchByDate'])

            && isset($_POST['state'])
               && !empty($_POST['state'])
               && in_array($_POST['state'], ['all', 'open', 'returned'])) {
            
            // populate the filter
            $filter['usrSearch'] =  $_POST['searchByMember'];
            $filter['bookSearch'] = $_POST['searchByBook'];
            $filter['dateSearch'] = $_POST['searchByDate'];
            $filter['state'] =      $_POST['state'];
            
            // post-redirect-get with array $filter
            $this->redirect('rental', 'managereturns',
                    Utils::url_safe_encode($filter));
        }
        
        // if we got an encoded filter from get request
        if(isset($_GET['param1'])) {
            $encoded_filter = $_GET['param1'];
            $filter = Utils::url_safe_decode($encoded_filter);
            if(!$filter) {
                Tools::abort('Bad url parameters');
            }
            
            // get the rentals filtered
            $rentals = Rental::get_rentals_filtered($filter);
            
            $usrSearch = $filter['usrSearch'];
            $bookSearch = $filter['bookSearch'];
            $dateSearch = $filter['dateSearch'];
            $state = $filter['state'];
            
        } else {
            $rentals = Rental::get_rentals();
            $state = 'all';
        }
        
        $slash_encoded_filter = "";
        if (isset($encoded_filter) && !empty($encoded_filter)) {
            $slash_encoded_filter = "/" . $encoded_filter;
        }
        
        (new View('rental_book_return'))->show(array(
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "isAdmin" => $user->isAdmin(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "rentals" => $rentals,
            "usrSearch" => $usrSearch,
            "bookSearch" => $bookSearch,
            "dateSearch" => $dateSearch,
            "state" => $state,
            "slash_encoded_filter" => $slash_encoded_filter
        ));
    }
    
    public function ajaxEvents() {
        $user = $this->get_user_or_redirect();

        if (!$user->isAdmin() && !$user->isManager()) {
            Tools::abort("You must have the 'manager'
                        or the 'admin' role");
        }
        
        // Range stuff
        $range_start = FALSE;
        $range_end = FALSE;
        if(Utils::check_fields(['start', 'end'])) {
            $range_start = new DateTime($_POST['start']);
            $range_end = new DateTime($_POST['end']);
        }
        
        $filter['usrSearch']  = "";
        $filter['bookSearch'] = "";
        $filter['dateSearch'] = "";
        $filter['state']      = "";
        
        // if the user filtered the entries
        if (   isset($_POST['searchByMember'])
            && isset($_POST['searchByBook'])
            && isset($_POST['searchByDate'])

            && isset($_POST['state'])
               && !empty($_POST['state'])
               && in_array($_POST['state'], ['all', 'open', 'returned'])) {
            
            // populate the filter
            $filter['usrSearch'] =  $_POST['searchByMember'];
            $filter['bookSearch'] = $_POST['searchByBook'];
            $filter['dateSearch'] = $_POST['searchByDate'];
            $filter['state'] =      $_POST['state'];
        }
        
        
        $json = Rental::get_events_json($filter, $range_start, $range_end);
        
        echo $json;
    }
    
    public function ajaxResources() {
        $user = $this->get_user_or_redirect();

        if (!$user->isAdmin() && !$user->isManager()) {
            Tools::abort("You must have the 'manager'
                        or the 'admin' role");
        }
        
        $filter['usrSearch']  = "";
        $filter['bookSearch'] = "";
        $filter['dateSearch'] = "";
        $filter['state']      = "";
        
        // if the user filtered the entries
        if (   isset($_POST['searchByMember'])
            && isset($_POST['searchByBook'])
            && isset($_POST['searchByDate'])

            && isset($_POST['state'])
               && !empty($_POST['state'])
               && in_array($_POST['state'], ['all', 'open', 'returned'])) {
            
            // populate the filter
            $filter['usrSearch'] =  $_POST['searchByMember'];
            $filter['bookSearch'] = $_POST['searchByBook'];
            $filter['dateSearch'] = $_POST['searchByDate'];
            $filter['state'] =      $_POST['state'];
        }
        
        $json = Rental::get_resources_json($filter);
        
        echo $json;
    }
    
    // param1 is encoded filter
    public function remove() {
        $user = $this->get_user_or_redirect();

        if (!$user->isAdmin()) {
            Tools::abort('Only an administrator can delete a rental');
        }

        $encoded_filter = '';
        if(Utils::check_fields(['param1'], $_GET)) {
            $encoded_filter = $_GET['param1'];
        }
        
        if (!isset($_POST['id'])) {
            Tools::abort('Removing a rental requires an id');
        }

        // manque une vue intermédiaire pour la suppression du rental
        // "êtes vous sûr.e de supprimer cet élément de location ?"
        
        $rentalId = $_POST['id'];

        Rental::removeRental($rentalId);

        if(empty($encoded_filter)) {
            $this->redirect('rental', 'managereturns');
        } else {
            $this->redirect('rental', 'managereturns', $encoded_filter);
        }
    }
    
    public function ajaxRemove() {
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        if(!$user->isAdmin()) {
            header("HTTP/1.1 403 Forbidden");
            echo "Only an administrator can delete a rental";
            die();
        }
        
        if (!Utils::check_fields(['id'])
                || !is_numeric($_POST['id'])) {
            header("HTTP/1.1 403 Forbidden");
            echo "Unknown rental - missing rental id";
            die();
        }
        
        $rentalId = $_POST['id'];

        // get the corresponding rental
        $rental = Rental::byId($rentalId);
        if (is_bool($rentalToEdit)) {
            header("HTTP/1.1 403 Forbidden");
            echo "Unknown rental";
            die();
        }
        
        try {
            Rental::removeRental($rentalId);
            header("HTTP/1.1 204 No Content"); // success
            die();
        } catch (Exception $exc) {
            header("HTTP/1.1 500 Internal Server Error"); // failed
            echo "Unexpected server exception";
            die();
        }
        
    }
    
    // param1 is encoded filter
    public function encodereturn() {
        $user = $this->get_user_or_redirect();
        
        if(!$user->isAdmin() && !$user->isManager()) {
            Tools::abort('Only admins and managers can encode book returns');
        }
        
        $encoded_filter = '';
        if(Utils::check_fields(['param1'], $_GET)) {
            $encoded_filter = $_GET['param1'];
        }
        
        if (!Utils::check_fields(['id'])
                || !is_numeric($_POST['id'])) {
            Tools::abort('Unknown rental');
        }
        
        $rentalId = $_POST['id'];
        
        // get the corresponding rental
        $rentalToEdit = Rental::byId($rentalId);
        if (is_bool($rentalToEdit)) {
            Tools::abort('Unknown rental');
        }
        
        $rentalToEdit->encodeReturn();
        
        if(empty($encoded_filter)) {
            $this->redirect('rental', 'managereturns');
        } else {
            $this->redirect('rental', 'managereturns', $encoded_filter);
        }
        
    }
    
    public function ajaxEncodeReturn() {
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        if(!$user->isAdmin() && !$user->isManager()) {
            header("HTTP/1.1 403 Forbidden");
            echo "Only admins and managers can encode book returns";
            die();
        }
        
        if (!Utils::check_fields(['id'])
                || !is_numeric($_POST['id'])) {
            header("HTTP/1.1 403 Forbidden");
            echo "Unknown rental - missing rental id";
            die();
        }
        
        $rentalId = $_POST['id'];
        
        // get the corresponding rental
        $rentalToEdit = Rental::byId($rentalId);
        if (is_bool($rentalToEdit)) {
            header("HTTP/1.1 403 Forbidden");
            echo "Unknown rental";
            die();
        }
        
        try {
            $rentalToEdit->encodeReturn();
            header("HTTP/1.1 204 No Content"); // success
            die();
        } catch (Exception $exc) {
            header("HTTP/1.1 500 Internal Server Error"); // failed
            echo "Unexpected server exception";
            die();
        }
        
    }
    
}