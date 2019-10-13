<?php

require_once 'model/MyModel.php';

class Book extends MyModel {
    private $id;
    private $isbn;
    private $title;
    private $author;
    private $editor;
    private $picture;
    private $nbCopies;
    
    private function __construct($id, $isbn, $title, $author, $editor, $picture, $nbCopies) {
        $this->id = $id;
        $this->setIsbn($isbn);
        $this->title = $title;
        $this->author = $author;
        $this->editor = $editor;
        $this->picture = $picture;
        $this->nbCopies = $nbCopies;
    }
    
    public static function createBook($id, $isbn, $title, $author, $editor, $nbCopies, $picture) {
        return new self($id, $isbn, $title, $author, $editor, $picture, $nbCopies);
    }
    
    public function validate() {
        $errors = array();
        
//        if(!self::validateIsbn13($this->isbn)) {
//            $errors[] = 'Invalid ISBN';
//        }
        
        if (!(isset($this->title)
              && is_string($this->title)
              && strlen($this->title) <= 255))
        {
            $errors[] = "Title too long(255 characters max).";
        }
        
        if (!(isset($this->author)
              && is_string($this->author)
              && strlen($this->author) <= 255))
        {
            $errors[] = "Author too long(255 characters max).";
        }
        
        if (!(isset($this->editor)
              && is_string($this->editor)
              && strlen($this->editor) <= 255))
        {
            $errors[] = "Editor too long(255 characters max).";
        }
        
        if (!(isset($this->nbCopies)
              && $this->nbCopies >= 0))
        {
            $errors[] = "The number of copies of a book must be greater than or equal to 0.";
        }
        
        return $errors;
    }
    
    public static function validateCopies(Book $book){
        $errors = [];
        
        if($book->availabilities() < 0) {
            $errors[] = "Negative availability";
        }
        
        return $errors;
    }
    
    public static function validateUnicity(Book $book){
        $errors = [];
        
        if(!Book::isIsbnAvailable($book->getIsbn())) {
            $errors[] = "This isbn is unavailable";
        }
        
        return $errors;
    }
    
    public static function isIsbnAvailable($isbn) {
        try{
            $stmt = self::execute("SELECT * FROM book WHERE isbn =:isbn",
                    array("isbn"=>$isbn));
            $result = $stmt->fetchAll();
            return count($result) === 0;
        
        } catch (Exception $e) {
            Tools::abort("Error while accessing database. Please contact your administrator.");
        }
    }
    
    public static function validatePicture($file) {
        $errors = [];
        if (isset($file['name']) && $file['name'] != '') {
            if ($file['error'] == 0) {
                $valid_types = array("image/gif", "image/jpeg", "image/png");
                if (!in_array($file['type'], $valid_types)) {
                    $errors[] = "Unsupported image format :
                        use gif, jpg/jpeg or png.";
                }
            } else {
                $errors[] = "Error while uploading file.";
            }
        }
        return $errors;
    }

    //pre : validatePicture($file) returns true
    public function generateFileName($file) {
        // unique, no need to concatenate time()
        $saveTo = $this->isbn;
        
        switch ($file['type']) {
            case "image/gif": $saveTo .= '.gif'; break;
            case "image/jpeg": $saveTo .= '.jpg'; break;
            case "image/png": $saveTo .= '.png'; break;
        }
        
        return $saveTo;
    }

    public static function byId($idBook) {
        $query = self::execute("SELECT * FROM book where id = :id", array("id"=>$idBook));
        $data = $query->fetch(); // un seul résultat au maximum
        if ($query->rowCount() == 0) {
            return false;
        } else {
            return new Book(
                    $data["id"],
                    $data["isbn"],
                    $data["title"],
                    $data["author"],
                    $data["editor"],
                    $data["picture"],
                    $data["nbCopies"]);
        }
    }
    
    public static function inBasket($idUser) {
        
        $query = self::execute("SELECT DISTINCT
                                    b.id, b.isbn, b.title, b.author,
                                    b.editor, b.picture, b.nbCopies
                                FROM book b, rental r, user u
                                WHERE
                                        b.id = r.book
                                    AND u.id = r.user
                                    AND r.user = :idUser
                                    AND r.rentaldate IS NULL
                                    AND r.returndate IS NULL
                                ORDER BY title ASC",
                array("idUser" => $idUser));
        
        $data = $query->fetchAll();

        $books = [];

        foreach ($data as $book) {
            $books[] = new Book(
                    $book["id"],
                    $book["isbn"],
                    $book["title"],
                    $book["author"],
                    $book["editor"],
                    $book["picture"],
                    $book["nbCopies"]);
        }

        return $books;
    }
    
    public static function notInBasket($idUser) {
        $query = self::execute("SELECT
                                    id, isbn, title, author,
                                    editor, picture, nbCopies
                                FROM book
                                WHERE
                                    id NOT IN
                                    (SELECT b.id FROM book b, rental r, user u
                                        WHERE
                                        b.id = r.book
                                    AND u.id = r.user
                                    AND r.user = :idUser
                                    AND r.rentaldate IS NULL
                                    AND r.returndate IS NULL)
                                ORDER BY title ASC",
                array("idUser" => $idUser));
        
        $data = $query->fetchAll();

        $books = [];

        foreach ($data as $book) {
            $books[] = new Book(
                    $book["id"],
                    $book["isbn"],
                    $book["title"],
                    $book["author"],
                    $book["editor"],
                    $book["picture"],
                    $book["nbCopies"]);
        }

        return $books;
    }
    
    // $q is the filter
    public static function notInBasketFiltered($idUser, $q) {
        
        // makes it easier to find a dashed formatted ISBN-13
        // however a side-effect is induced in that a string 17 char long
        // with a dash in it would be assumed ISBN-13;
        // using a proper ISBN-13 validation would solve this problem
        if(strlen($q) == 17 && strpos($q, '-') !== false) {
            $q = str_replace('-', '', $q);
        }
        $q = '%' . $q . '%';
        
        $query = self::execute("SELECT
                                    id, isbn, title, author,
                                    editor, picture, nbCopies
                                FROM book
                                WHERE
                                    id NOT IN
                                    
                                    (SELECT b.id FROM book b, rental r, user u
                                        WHERE
                                        b.id = r.book
                                    AND u.id = r.user
                                    AND r.user = :idUser
                                    AND r.rentaldate IS NULL
                                    AND r.returndate IS NULL)
                                
                                AND (   isbn  LIKE :q
                                    OR  title LIKE :q
                                    OR author LIKE :q
                                    OR editor LIKE :q)
                                ORDER BY title ASC",
                array(
                    "idUser" => $idUser,
                    "q" => $q
                ));
        
        $data = $query->fetchAll();

        $books = [];

        foreach ($data as $book) {
            $books[] = new Book(
                    $book["id"],
                    $book["isbn"],
                    $book["title"],
                    $book["author"],
                    $book["editor"],
                    $book["picture"],
                    $book["nbCopies"]);
        }

        return $books;
    }

    public function remove() {
        Rental::deleteAllByBookId($this->id);
        
        $stmt = self::execute("
            DELETE FROM book
            WHERE id = :bookId",
            array(
                "bookId" => $this->id
            )
        );
    }
//    public static function removeBook($bookId) {
//        $stmt = self::execute("
//            DELETE FROM book
//            WHERE id = :bookId",
//            array(
//                "bookId" => $bookId
//            )
//        );
//    }
    
    function save(){
        if(NULL == $this->id || !is_numeric($this->id)) {
            $this->insert();
        } else {
            $this->update();
        }
    }
    
//    ModifRomain
    public function available() {
        return ($this->availabilities() > 0);
    }
    
    public static function checkAvailabilitiesBook($book_id){
        $book = Book::byId($book_id);
        return $book->available();  
    }
            
    public function availabilities() {
        try{
            $stmt = self::execute("SELECT * 
                                   FROM rental, book 
                                   WHERE rental.book = book.id
                                   and rental.returndate is null
                                   and book.id = :id",
                    array("id"=>$this->id));
            $result = $stmt->fetchAll();
            $availabilities = $this->nbCopies - count($result);
            return $availabilities;
        
        } catch (Exception $e) {
            Tools::abort("Error while accessing database. Please contact your administrator.");
        }
    }
    
    private function insert() {
        $stmt = self::execute("INSERT INTO book(
            isbn,
            title,
            author,
            editor,
            picture,
            nbCopies)
        VALUES(
            :isbn,
            :title,
            :author,
            :editor,
            :picture,
            :nbCopies)",
                
            array(
            "isbn" => $this->isbn,
            "title" => $this->title,
            "author" => $this->author,
            "editor" => $this->editor,
            "picture" => $this->picture,
            "nbCopies" => $this->nbCopies
            )
        );
        
        $this->id = self::lastInsertId();
    }
    
    private function update() {
        $stmt = self::execute(
            "UPDATE book SET
                 isbn = :isbn,
                 title = :title,
                 author = :author,
                 editor = :editor,
                 picture = :picture,
                 nbCopies = :nbCopies
             WHERE
                id = :id",
                
            array(
            "isbn" => $this->isbn,
            "title" => $this->title,
            "author" => $this->author,
            "editor" => $this->editor,
            "picture" => $this->picture,
            "nbCopies" => $this->nbCopies,
            "id" => $this->id
            )
        );
    }
    
    function getId() {
        return $this->id;
    }

    function getIsbn() {
        return $this->isbn;
    }

    function getIsbnFormatted($withCheckDigit = true) {
        if ($withCheckDigit) {
            $code = $this->isbn;
        } else {
            $code = substr($this->isbn, 0, -1);
        }

        // add dashes        
        $code = substr_replace($code, '-', 3, 0);
        $code = substr_replace($code, '-', 5, 0);
        $code = substr_replace($code, '-', 10, 0);
        if ($withCheckDigit) {
            $code = substr_replace($code, '-', 15, 0);
        }
        
        return $code;
    }
    
    function getIsbnCheckDigit() {
        // tricky, better validate isbn before
        // only use it from database
        return substr($this->getIsbn(), -1);
    }
    
    public static function validateIsbn13($isbn) {
//        var_dump($_POST);
        
        if(null == $isbn
                || !is_string($isbn)) {
            return FALSE;
        }
        
        // removes '-' chars
        $isbn = Utils::strip_dashes($isbn);
        
        // must be 13 chars long
        if(strlen($isbn) != 13) {
            return FALSE;
        }
        
        // all chars must be digits
        if(!ctype_digit($isbn)) {
            return FALSE;
        }
        
        // for book validation only:
        // firt 3 digits must be either 977 (Serial publications - ISSN),
        // or 978 or 979 (Bookland - ISBN)
        $code_GS1 = substr($isbn, 0, 3);
        switch ($code_GS1) {
            case '977': break;
            case '978': break;
            case '979': break;
            default:
                return false;
                break;
        }
        
        $total = 0;
        
//        $digits = [];
        
        for($i = 0; $i < 12; ++$i) {
            $digit = (int)(substr($isbn, $i, 1));
//            $digits[] = $digit;
            if($i % 2 == 0) {
                $total += $digit;
            } else {
                $total += $digit*3;
            }
        }
        
//        var_dump($digits, $total);
        
        $checksum = 10 - ($total % 10);
        if($checksum == 10) {
            $checksum = 0;
        }
        
//        var_dump($checksum);
        
        $check_digit = (int)substr($isbn, -1);
        
        return $check_digit === $checksum;
        
    }
            
    function createIsbn() {
        // ------------ validate incoming 12 char isbn ----------------

        $errors = [];
        
        if(null == $this->isbn
                || !is_string($this->isbn)) {
            $errors[] = 'Wrong ISBN format : not a string';
        }
        
        // removes '-' chars
        $this->isbn = Utils::strip_dashes($this->isbn);
        
        // if isbn is 13 char long, strip the last char
        if(strlen($this->isbn) === 13) {
            $this->isbn = substr($this->isbn, 0, -1);
        }
        
        // must be 12 chars long
        if(strlen($this->isbn) != 12) {
            $errors[] = 'ISBN must have 12 characters in it';
        }
        
        // all chars must be digits
        if(!ctype_digit($this->isbn)) {
            $errors[] = 'Wrong ISBN format : not all digits';
        }
        
        // for book validation only:
        // firt 3 digits must be either 977 (Serial publications - ISSN),
        // or 978 or 979 (Bookland - ISBN)
        $code_GS1 = substr($this->isbn, 0, 3);
        switch ($code_GS1) {
            case '977': break;
            case '978': break;
            case '979': break;
            default:
                $errors[] = 'Wrong ISBN format : must begin with 977,978 or 979';
                break;
        }

        // ------------ /validate incoming 12 char isbn ---------------
        
        $total = 0;
        
        for($i = 0; $i < 12; ++$i) {
            $digit = (int)(substr($this->isbn, $i, 1));
            if($i % 2 == 0) {
                $total += $digit;
            } else {
                $total += $digit*3;
            }
        }
        
        $checksum = 10 - ($total % 10);
        if($checksum == 10) {
            $checksum = 0;
        }

        $this->isbn .= $checksum;
        
        return $errors;
    }
    
    function getTitle() {
        return $this->title;
    }

    function getAuthor() {
        return $this->author;
    }

    function getEditor() {
        return $this->editor;
    }

    function getPicture() {
        return $this->picture;
    }
    
    function getNbCopies() {
        return $this->nbCopies;
    }

    function setIsbn($isbn) {
        $this->isbn = Utils::strip_dashes($isbn);
    }
    
    function setPicture($picture) {
        $this->picture = $picture;
    }
}

