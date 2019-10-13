<?php

require_once 'MyController.php';

class ControllerBook extends MyController {

    const UPLOAD_ERR_OK = 0;
    
    public function index() {
        $user = $this->get_user_or_redirect();
        
        $this->redirect("rental", "books", $user->getUsername());
    }
    
    public function details() {
        $user = $this->get_user_or_redirect();
        
        $filter = '';
        if(Utils::check_fields(['param2'], $_GET)) {
            $filter = $_GET['param2'];
        }
        
        if (!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Unknown book');
        }
        
        $id = $_GET['param1'];
        $book = Book::byId($id);

        if (is_bool($book)) {
            Tools::abort('Unknown book');
        }
        
        (new View('book_details'))->show(array(
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "book" => $book,
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "filter" => $filter,
            "filter_is_encoded" => !empty($filter)
        ));
    }
    
    public function edit() {
        $user = $this->get_user_or_redirect();
        
        $filter = '';
        if(Utils::check_fields(['param2'], $_GET)) {
            $filter = $_GET['param2'];
        }
        
        if (Utils::check_fields(['cancel'])) {
            if(empty($filter)) {
                $this->redirect('rental', 'books', $user->getUsername());
            } else {
                $this->redirect('rental', 'books', $user->getUsername(), $filter);
            }
        }
        
        if(!$user->isAdmin()){
            Tools::abort("You must have the 'admin' role");
        }
        
        if (!Utils::check_fields(['param1'], $_GET)) {
            Tools::abort('Unknown book');
        }
        
        $id = $_GET['param1'];
        $book = Book::byId($id);

        if (is_bool($book)) {
            Tools::abort('Unknown book');
        }
        
        // clear picture
        if(Utils::check_fields(['clear'])) {
            $oldFileName = $book->getPicture();
            if($oldFileName && file_exists("upload/" . $oldFileName)) {
                unlink("upload/" . $oldFileName);
            }
            $book->setPicture(null);
            $book->save();
            
            if(empty($filter)) {
                $this->redirect('book', 'edit', $book->getId());
            } else {
                $this->redirect('book', 'edit', $book->getId(), $filter);
            }
        }
        
        $errors = [];
        $user_has_sent_file = false;
        
        if(Utils::check_fields(array(
            'save',
            'isbn',
            'title',
            'author',
            'editor',
            'nbCopies')))
        {
            $edited = Book::createBook(
                    $book->getId(),
                    $_POST['isbn'],
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['editor'],
                    $_POST['nbCopies'],
                    $book->getPicture());
            
            $errors = array_merge($errors, $edited->createIsbn());
            
            // if the isbn has changed
            if($book->getIsbn() !== $edited->getIsbn()) {
                $errors = array_merge($errors,
                        Book::validateUnicity($edited));
            }
            
            $errors = array_merge($errors, $edited->validate());
            $errors = array_merge($errors, Book::validateCopies($edited));
            
            // file upload handling
            // 
            // Il est nécessaire de vérifier le statut de l'erreur car,
            // dans le cas où on fait un submit
            // sans avoir choisi une image, $_FILES['image'] est "set",
            // mais le statut 'error' est à 4 (UPLOAD_ERR_NO_FILE).
            if (isset($_FILES['picture'])
                    && $_FILES['picture']['error'] === self::UPLOAD_ERR_OK) {
                $user_has_sent_file = true;
                
                $errors = array_merge($errors,
                        Book::validatePicture($_FILES['picture']));
            }
           
            if(empty($errors)) {
                if($user_has_sent_file) {
                    // if the user has already a picture, delete it
                    $oldFileName = $book->getPicture();
                    if($oldFileName && file_exists("upload/" . $oldFileName)) {
                        unlink("upload/" . $oldFileName);
                    }
                    
                    // move the sent file in upload folder
                    $newFileName =
                            $edited->generateFileName($_FILES['picture']);
                    move_uploaded_file($_FILES['picture']['tmp_name'],
                            "upload/$newFileName");
                    $edited->setPicture($newFileName);
                }
                
                $edited->save();
                            
                if(empty($filter)) {
                    $this->redirect('book', 'edit', $edited->getId());
                } else {
                    $this->redirect('book', 'edit', $edited->getId(), $filter);
                }
                
            } else {
                // populate the form with user input
                // so they can correct the data
                $book = $edited;
            }
        }
        
        (new View('book_edit'))->show(array(
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "book" => $book,
            "errors" => $errors,
            "filter" => $filter,
            "filter_is_encoded" => TRUE
        ));
    }
    
    // here filter is param1
    public function add() {
        $user = $this->get_user_or_redirect();
        
        if(!$user->isAdmin()){
            Tools::abort("You must have the 'admin' role");
        }
        
        $filter = '';
        if(Utils::check_fields(['param1'], $_GET)) {
            $filter = $_GET['param1'];
        }
        
        if (Utils::check_fields(['cancel'])) {
            if(empty($filter)) {
                $this->redirect('rental', 'books', $user->getUsername());
            } else {
                $this->redirect('rental', 'books', $user->getUsername(), $filter);
            }
        }
        
        $errors = [];
        
        $isbn = '';
        $checkdigit = '';
        $title = '';
        $author = '';
        $editor = '';
        $picture = '';
        $nbCopies = '';
        $user_has_sent_file = false;
        
        if(Utils::check_fields(array(
            'save',
            'isbn',
            'title',
            'author',
            'editor',
            'nbCopies')))
        {
            $newBook = Book::createBook(
                    null,
                    $_POST['isbn'],
                    $_POST['title'],
                    $_POST['author'],
                    $_POST['editor'],
                    $_POST['nbCopies'],
                    null);
            
            $errors = array_merge($errors, $newBook->createIsbn());
            $errors = array_merge($errors, Book::validateUnicity($newBook));
            $errors = array_merge($errors, $newBook->validate());
            
            // file upload handling
            // 
            // Il est nécessaire de vérifier le statut de l'erreur car,
            // dans le cas où on fait un submit
            // sans avoir choisi une image, $_FILES['image'] est "set",
            // mais le statut 'error' est à 4 (UPLOAD_ERR_NO_FILE).
            if (isset($_FILES['picture'])
                    && $_FILES['picture']['error'] === self::UPLOAD_ERR_OK) {
                $user_has_sent_file = true;
                
                $errors = array_merge($errors,
                        Book::validatePicture($_FILES['picture']));
            }
            
            if(empty($errors)) {
                if($user_has_sent_file) {
                    $newFileName =
                            $newBook->generateFileName($_FILES['picture']);
                    move_uploaded_file($_FILES['picture']['tmp_name'],
                            "upload/$newFileName");
                    $newBook->setPicture($newFileName);
                }
                
                $newBook->save();
                
                if(empty($filter)) {
                    $this->redirect('rental', 'books', $user->getUsername());
                } else {
                    $this->redirect('rental', 'books', $user->getUsername(), $filter);
                }
            } else {
                // populate the form with user input
                // so they can correct the data
                
                $isbn = $newBook->getIsbnFormatted(false);
                $checkdigit = $newBook->getIsbnCheckDigit();
                $title = $newBook->getTitle();
                $author = $newBook->getAuthor();
                $editor = $newBook->getEditor();
                $picture = $newBook->getPicture();
                $nbCopies = $newBook->getNbCopies();
            }
            
        }
        
        $slash_encoded_filter = "";
        if (!empty($filter)) {
            $slash_encoded_filter = "/" . $filter;
        }

        (new View('book_add'))->show(array(
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            
            "isbn" => $isbn,
            "checkdigit" => $checkdigit,
            "title" => $title,
            "author" => $author,
            "editor" => $editor,
            "picture" => $picture,
            "nbCopies" => $nbCopies,
            
            "errors" => $errors,
            
            "filter" => $filter,
            "filter_is_encoded" => !empty($filter),
            "slash_encoded_filter" => $slash_encoded_filter
        ));
    }
    
    
    
    public function ajaxIsbnAvailable(){
        $user = $this->get_user_or_false();
        if(FALSE === $user) {
            header("HTTP/1.1 401 Unauthorized");
            echo "User is not logged in";
            die();
        }
        
        $isAdmin = $user->isAdmin();
        
        if(!$isAdmin) {
            header("HTTP/1.1 403 Forbidden");
            echo "You must have the 'admin' role";
            die();
        }
        
        // get the isbn param
        if(!Utils::check_fields(['isbn'])
                || $_POST["isbn"] === ""
                ) {
            header("HTTP/1.1 403 Forbidden");
            echo "isbn param required";
            die();
        }
        
        $isbn = $_POST["isbn"];
        
        if(!Book::validateIsbn13($isbn)) {
            header("HTTP/1.1 400 Bad Request");
            echo "Not a valid ISBN-13";
            die();
        }
        
        if(Utils::check_fields(['editbook'])
                && is_numeric($_POST["editbook"])) {
            $exceptId = $_POST['editbook'];
            
            $book = Book::byId($exceptId);

            if (is_bool($book)) {
                header("HTTP/1.1 403 Forbidden");
                echo "Unknown book";
                die();
            }
            
            $exceptIsbn = $book->getIsbn();
        }
        
        try {
            if(isset($exceptIsbn) && $isbn === $exceptIsbn) {
                $isbn_is_available = TRUE;
            } else {
                $isbn_is_available = Book::isIsbnAvailable($isbn);
            }
        } catch (Exception $exc) {
            header("HTTP/1.1 500 Internal Server Error"); // failed
            echo "Unexpected server exception";
            die();
        }
        
        header("HTTP/1.1 200 OK"); // success
        if($isbn_is_available) {
            echo "true";
        } else {
            echo "false";
        }
    }
    
    // param1 = bookId
    // param2 = filter
    public function remove() {
        $user = $this->get_user_or_redirect();
        
        $filter = '';
        if(Utils::check_fields(['param2'], $_GET)) {
            $filter = $_GET['param2'];
        }
        
        if (Utils::check_fields(['cancel'])) {
            if(empty($filter)) {
                $this->redirect('rental', 'books', $user->getUsername());
            } else {
                $this->redirect('rental', 'books', $user->getUsername(), $filter);
            }
        }
        
        if(!$user->isAdmin()){
            Tools::abort("You must have the 'admin' role");
        }
        
        if (!isset($_GET['param1'])
                || !is_numeric($_GET['param1'])) {
            Tools::abort('Removing a rental requires an id');
        }
        
        $id = $_GET['param1'];
        
        $toRemove = Book::byId($id);
        
        if(is_bool($toRemove)) {
            Tools::abort('Unknown book');
        }
        
        // user confirmed the deletion
        if(isset($_POST['confirm'])) {
            $toRemove->remove();
            
            if(empty($filter)) {
                $this->redirect('rental', 'books', $user->getUsername());
            } else {
                $this->redirect('rental', 'books', $user->getUsername(), $filter);
            }
        }
        
        (new View('book_delete'))->show(array(
            "logged_username" => $user->getUsername(),
            "isMember" => $user->isMember(),
            "isAdminOrManager" => $user->isAdmin() || $user->isManager(),
            "book" => $toRemove,
            "filter" => $filter
        ));
    }

}

