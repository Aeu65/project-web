<?php

require_once 'model/MyModel.php';

class Rental extends MyModel {

    private $id;
    private $user;
    private $book;
    private $rentalDate;
    private $returnDate;

    private function __construct($id, $user, $book, $rentalDate, $returnDate) {
        $this->id = $id;
        $this->user = $user;
        $this->book = $book;
        $this->rentalDate = $rentalDate;
        $this->returnDate = $returnDate;
    }

    public static function createRental($id, $user, $book, $rentalDate, $returnDate) {
        return new Rental($id, $user, $book, $rentalDate, $returnDate);
    }

    public static function createBooking($user, $book, $rentalDate, $returnDate) {
        return self::createRentals(null, $user, $book, $rentalDate, $returnDate);
    }

    public function validate() {
        $errors = array();

        return $errors;
    }

//    Function boolean
    public function bookIsReturned() {
        return null !== $this->returnDate;
    }
    
    public function userIsLate() {
        $now = new DateTime();
        return $now > $this->getRentalDateToBeReturned();
    }

//    Getters
    public static function get_current_rentals_by_user($idUser, $deep = false) {
//        $query = self::execute("SELECT * FROM rental where username = :username", array("username"=>$username));
//        rental.id, rental.user, rental.book, rental.rentaldate, rental.returndate

        $query = self::execute("SELECT rental.id, rental.user, rental.book, rental.rentaldate, rental.returndate
                                FROM rental, user, book
                                WHERE
                                    book.id = rental.book
                                AND user.id = rental.user
                                AND user.id = :idUser
                                AND rental.rentaldate IS NOT NULL
                                ORDER BY book.title ASC"
                , array("idUser" => $idUser));
        $data = $query->fetchAll(); // un seul résultat au maximum

        $rentals = [];

        if(!$deep) {
            foreach ($data as $rental) {
                if(null !== $rental["rentaldate"]) {
                    $rentalDate = new DateTime($rental["rentaldate"]);
                } else {
                    $rentalDate = null;
                }
                
                if(null !== $rental["returndate"]) {
                    $returnDate = new DateTime($rental["returndate"]);
                } else {
                    $returnDate = null;
                }
                $rentals[] = new Rental(
                        $rental["id"],
                        $rental['user'],
                        $rental["book"],
                        $rentalDate,
                        $returnDate);
            }
        } else {
            foreach ($data as $rental) {
                if(null !== $rental["rentaldate"]) {
                    $rentalDate = new DateTime($rental["rentaldate"]);
                } else {
                    $rentalDate = null;
                }
                
                if(null !== $rental["returndate"]) {
                    $returnDate = new DateTime($rental["returndate"]);
                } else {
                    $returnDate = null;
                }
                
                $rentals[] = new Rental(
                        $rental["id"],
                        User::byId($rental['user']), // potentially slow !!!
                        Book::byId($rental["book"]), // potentially slow !!!
                        $rentalDate,
                        $returnDate);
            }
        }

        return $rentals;
    }

    public static function get_rentals() {
        $query = self::execute("SELECT
                                r.id as r_id,
                                r.rentaldate as r_rentaldate,
                                r.returndate as r_returndate,
                                r.user as r_user,
                                r.book as r_book

                                FROM rental r, book b
                                WHERE
                                        r.book = b.id
                                    AND r.rentaldate IS NOT NULL
                                ORDER BY r.rentaldate ASC,
                                         b.title ASC",
                array());
        
        $data = $query->fetchAll();

        $rentals = [];
        
        foreach ($data as $rental) {
            if (null !== $rental["r_rentaldate"]) {
                $rentalDate = new DateTime($rental["r_rentaldate"]);
            } else {
                $rentalDate = null;
            }

            if (null !== $rental["r_returndate"]) {
                $returnDate = new DateTime($rental["r_returndate"]);
            } else {
                $returnDate = null;
            }

            $rentals[] = new Rental(
                    $rental["r_id"], User::byId($rental['r_user']), Book::byId($rental["r_book"]), $rentalDate, $returnDate);
        }
        
        return $rentals;
    }
    
    public static function get_events_json($filter, $range_start, $range_end) {
        $rentals = self::get_rentals_filtered($filter, $range_start, $range_end);

        $now = new DateTime();

        $json = [];
        
        foreach($rentals as $rental) {
            // setting the event's end date and color
            $userIsLate = $rental->userIsLate();
            $returned_on = $rental->getReturnDate(TRUE);
            
            if($returned_on) {
                $end = $returned_on;
                
                $returnedAfterDueDate
                        = $returned_on > $rental->getRentalDateToBeReturned();
                
                if($returnedAfterDueDate) {
                    $color = "rgba(255,0,0,.5)";
                    $borderColor = "pink";
                } else {
                    $color = "rgba(0,255,0,.6)";
                    $borderColor = "lightgreen";
                }
                
            } else {
                if($userIsLate) {
                    $end = $now;
                    $color = "red";
                    $borderColor = "red";
                } else {
                    $end = $rental->getRentalDateToBeReturned();
                    $color = "green";
                    $borderColor = "green";
                }
            }
            $end = $end->format(DateTime::ATOM);
            
            $rentaldate = $rental->getRentalDate(TRUE)->format(DateTime::ATOM);
            $returndate = $rental->getReturnDate(TRUE);
            if(null !== $returndate) {
                $returndate = $returndate->format(DateTime::ATOM);
            }
            
            $rental = [
                "resourceId" => $rental->getId(),
                "start" => $rentaldate,
                "rentaldate" => $rentaldate,
                "end" => $end,
                "returndate" => $returndate,
                "backgroundColor" => $color,
                "borderColor" => $borderColor
            ];
            $json[] = $rental;
        }
        return json_encode($json);
    }
    
    public static function get_resources_json($filter) {
        $rentals = self::get_rentals_filtered($filter);
        
        $json = [];
       
        $orderId = 1;
        
        foreach($rentals as $rental) {
            $rental = [
                "id" => $rental->getId(),
                "orderId" => ++$orderId,
                "user" => $rental->getUser()->getUsername(),
                "book" => $rental->getBook()->getTitle()
            ];
            $json[] = $rental;
        }
        
        return json_encode($json);
    }
    
    public static function get_rentals_filtered($filter,
                                                $range_start = false,
                                                $range_end = false) {
        
        // select + sql join
        $query = 'SELECT 
                    r.id as r_id,
                    r.rentaldate as r_rentaldate,
                    r.returndate as r_returndate,
                    
                    u.id as u_id,
                    u.username as u_username,
                    u.password as u_password,
                    u.fullname as u_fullname,
                    u.email as u_email,
                    u.birthdate as u_birthdate,
                    u.role as u_role,
                    
                    b.id as b_id,
                    b.isbn as b_isbn,
                    b.title as b_title,
                    b.author as b_author,
                    b.editor as b_editor,
                    b.picture as b_picture,
                    b.nbCopies as b_nbCopies

                  FROM rental r, user u, book b
                  WHERE
                        r.user = u.id
                  AND   r.book = b.id ' . "\n\n";
        
        $params = []; // data passed as param to the stmt
        
        // state 'all' is just this
        $query .= 'AND r.rentaldate IS NOT NULL ' . "\n";
        
        switch ($filter['state']) {
            case 'open':
                $query .= 'AND r.returndate IS NULL' . "\n";
                break;
            
            case 'returned':
                $query .= 'AND r.returndate IS NOT NULL' . "\n";
                break;
        }
        $query .= "\n";
        
        if(!empty($filter['usrSearch'])) {
            $query .= 'AND (u.username LIKE :usrSearch '
                    . 'OR u.fullname LIKE :usrSearch)' . "\n\n";
            $params['usrSearch'] = '%' . $filter['usrSearch'] . '%';
        }
        
        if(!empty($filter['bookSearch'])) {
            // il faudrait gérer le cas où le bookSearch
            // est un ISBN formatté avec des dashes pour strip avant le LIKE
            // nécessite une fonction de validation isbn dans Book
            
            $query .= 'AND (b.isbn LIKE :bookSearch ' . "\n"
                    . 'OR b.title  LIKE :bookSearch' . "\n"
                    . 'OR b.author LIKE :bookSearch' . "\n"
                    . 'OR b.editor LIKE :bookSearch) ' . "\n\n";
            $params['bookSearch'] = '%' . $filter['bookSearch'] . '%';
        }
        
        if(!empty($filter['dateSearch'])) {
            $query .= 'AND DATE(rentaldate) = :dateSearch' . "\n\n";
            $params['dateSearch'] = $filter['dateSearch'];
        }
        
        // Range filtering
        if($range_start instanceof DateTime
          && $range_end instanceof DateTime)
        {
            $maxRentalDuration = Configuration::get('max_rental_duration');
            
            // event is off the range (on the right)
            $query .= 'AND NOT (r.rentaldate >= :rangeEnd)' . "\n";
            
            // event is off the range (on the left)
            $query .= 'AND NOT (r.returndate IS NOT NULL '
                    .          'AND r.returndate < :rangeStart)' . "\n";
            
            $dateToBeReturned = "DATE_ADD(r.rentaldate, INTERVAL $maxRentalDuration)";
            
            // event is off the range (date to be returned is on the left, user not late)
            $query .= 'AND NOT (r.returndate IS NULL' . "\n"
                    .          "AND $dateToBeReturned < :rangeStart" . "\n"
                    .          "AND NOW() < $dateToBeReturned)" . "\n";
            
            // event is off the range (user is late and now is on the left)
            $query .= 'AND NOT (r.returndate IS NULL' . "\n"
                    .          'AND NOW() > DATE_ADD(r.rentaldate, ' . "\n"
                    .                      'INTERVAL ' . $maxRentalDuration . ')' . "\n"
                    .          'AND NOW() < :rangeStart)' . "\n\n";
            
            $params['rangeStart'] = $range_start->format(DateTime::ATOM);
            $params['rangeEnd'] = $range_end->format(DateTime::ATOM);
        }
        
        $query .= 'ORDER BY r.rentaldate ASC, b.title ASC' ."\n";
        
//        uncomment for debug
//            echo '<pre><code>';
//            echo $query;
//            var_dump($query, $params);
//            echo '</code></pre>';
//            die();

        $stmt = self::execute($query, $params);
        
        $data = $stmt->fetchAll();
        
        $rentals = [];
        
        foreach ($data as $row) {
            if (null !== $row["r_rentaldate"]) {
                $rentalDate = new DateTime($row["r_rentaldate"]);
            } else {
                $rentalDate = null;
            }

            if (null !== $row["r_returndate"]) {
                $returnDate = new DateTime($row["r_returndate"]);
            } else {
                $returnDate = null;
            }

            $rentals[] = new Rental(
                    $row["r_id"],
                    User::createUser(
                            $row['u_id'],
                            $row['u_username'],
                            $row['u_password'],
                            $row['u_fullname'],
                            $row['u_email'],
                            $row['u_birthdate'],
                            $row['u_role']),
                    Book::createBook(
                            $row['b_id'],
                            $row['b_isbn'],
                            $row['b_title'],
                            $row['b_author'],
                            $row['b_editor'],
                            $row['b_nbCopies'],
                            $row['b_picture']),
                    $rentalDate,
                    $returnDate);
        }
        
        return $rentals;
    }
    
//    public static function get_rentals($deep = false, $state = 'all') {
//        switch ($state) {
//            case 'open':
//                $query = self::execute("SELECT *
//                                        FROM rental
//                                        WHERE rentaldate IS NOT NULL
//                                        AND returndate IS NULL", array());
//                break;
//            case 'returned':
//                $query = self::execute("SELECT *
//                                        FROM rental
//                                        WHERE rentaldate IS NOT NULL
//                                        AND returndate IS NOT NULL", array());
//                break;
//            case 'all':
//                $query = self::execute("SELECT *
//                                        FROM rental
//                                        WHERE rentaldate IS NOT NULL", array());
//                break;
//        }
//
//        $data = $query->fetchAll();
//
//        $rentals = [];
//
//        if (!$deep) {
//            foreach ($data as $rental) {
//                if (null !== $rental["rentaldate"]) {
//                    $rentalDate = new DateTime($rental["rentaldate"]);
//                } else {
//                    $rentalDate = null;
//                }
//
//                if (null !== $rental["returndate"]) {
//                    $returnDate = new DateTime($rental["returndate"]);
//                } else {
//                    $returnDate = null;
//                }
//                $rentals[] = new Rental(
//                        $rental["id"], $rental['user'], $rental["book"], $rentalDate, $returnDate);
//            }
//        } else {
//            foreach ($data as $rental) {
//                if (null !== $rental["rentaldate"]) {
//                    $rentalDate = new DateTime($rental["rentaldate"]);
//                } else {
//                    $rentalDate = null;
//                }
//
//                if (null !== $rental["returndate"]) {
//                    $returnDate = new DateTime($rental["returndate"]);
//                } else {
//                    $returnDate = null;
//                }
//
//                $rentals[] = new Rental(
//                        $rental["id"], User::byId($rental['user']), Book::byId($rental["book"]), $rentalDate, $returnDate);
//            }
//        }
//
//        return $rentals;
//    }

    /*
    public function theSearchIsEmptyOrMatches($usrSearch, $bookSearch, $dateSearch) {
        return self::theSearchMatches($usrSearch, $bookSearch, $dateSearch) 
                || self::theSearchIsEmpty($usrSearch, $bookSearch, $dateSearch);
    }

    public function theSearchIsEmpty($usrSearch, $bookSearch, $dateSearch) {
        if ($usrSearch === null && $bookSearch === null && $dateSearch === null 
                || $usrSearch === '' && $bookSearch === '' && $dateSearch === '') {
            return true;
        } else
            return false;
    }
    
    public function theSearchMatches($usrSearch, $bookSearch, $dateSearch) {
        $boolUsr = true;
        $boolBook = true;
        $boolDate = true;

        if ($usrSearch !== null) {
            $boolUsr = (FALSE !== (stristr($this->getUser()->getUsername(), $usrSearch))
                    || (stristr($this->getUser()->getFullname(), $usrSearch)));
        }

        if ($bookSearch !== null) {
            $boolBook = (FALSE !== (stristr($this->getBook()->getIsbn(), $bookSearch))
                    || (stristr($this->getBook()->getTitle(), $bookSearch))
                    || (stristr($this->getBook()->getAuthor(), $bookSearch))
                    || (stristr($this->getBook()->getEditor(), $bookSearch)));
        }

        if ($dateSearch !== null) {
            $boolDate = $dateSearch === $this->getRentalDate();
        }

        return $boolUsr && $boolBook && $boolDate;
    }
    */
    
    public static function byId($rentalId) {
        $query = self::execute("SELECT * FROM rental where id = :id", array("id"=>$rentalId));
        $data = $query->fetch(); // un seul résultat au maximum
        if ($query->rowCount() == 0) {
            return false;
        } else {
          return new Rental(
            $data['id'],
            $data['user'],
            $data['book'],
            $data['rentaldate'],
            $data['returndate']);
        }
    }
    
    public static function insert(Rental $rental) {
        
        $stmt = self::execute("INSERT INTO rental
            (`id`,
             `user`,
             `book`,
             `rentaldate`,
             `returndate`) VALUES
                (null,
                :userId,
                :bookId,
                null,
                null)",
            array(
                "userId" => $rental->getUser()->getId(),
                "bookId" => $rental->getBook()->getId()
            )
        );
        
        $rental->id = self::lastInsertId();
        
        return $rental;
        
    }
    
    public static function confirmBasket($userId) {
        $stmt = self::execute(
                "UPDATE rental SET
                    rentaldate = NOW()
                WHERE
                        user = :userId
                    AND rentaldate IS NULL
                    AND returndate IS NULL",
            array(
                "userId" => $userId
            ));
    }
    
    public function upsert() {
        // populate user and book (lazily loading them here)
        if(is_numeric($this->user)) {
            $this->user = User::byId($this->user);
        }
        if(is_numeric($this->book)) {
            $this->book = Book::byId($this->book);
        }
        
        if(null == $this->id) {
            return self::insert($this);
        } else {
//            return self::update($this);
        }
    }
    
    public static function removeRental($rentalId) {
        $stmt = self::execute("
            DELETE FROM rental
            WHERE id = :rentalId",
            array(
                "rentalId" => $rentalId
            )
        );
    }
    
    public static function removeFromBasket($userId, $bookId) {
        $stmt = self::execute("
            DELETE FROM rental
            WHERE user = :userId AND book = :bookId
            AND rentaldate IS NULL and returndate IS NULL",
            array(
                "userId" => $userId, 
                "bookId" => $bookId
            )
        );
    }
    
    public static function clearBasket($userId) {
        $stmt = self::execute("
            DELETE FROM rental
            WHERE user = :userId
            AND rentaldate IS NULL and returndate IS NULL",
            array(
                "userId" => $userId
            )
        );
    }
    
    
//    public static function encodeReturn($rentalId) {
//        $stmt = self::execute(
//                "UPDATE rental SET
//                    returndate = NOW()
//                WHERE
//                        returndate IS NOT NULL
//                    AND rental.id = :rentalId",
//            array(
//                "rentalId" => $rentalId
//            ));
//    }
    
    public function encodeReturn() {
        $stmt = self::execute(
                "UPDATE rental SET
                    returndate = NOW()
                WHERE
                        returndate IS NULL
                    AND id = :rentalId",
            array(
                "rentalId" => $this->id
            ));
        
    }
    
    public static function canAddToBasket($userId) {
        $query = self::execute("SELECT COUNT(*) as nb
                                FROM rental
                                WHERE
                                        user = :userId
                                    AND returndate IS NULL",
            array(
                "userId" => $userId
            ));
        
        $data = $query->fetch(); // un seul résultat au maximum

        $not_returned_books = (int) $data['nb'];
        $max = (int) Configuration::get('max_items_in_basket');
        
        return $not_returned_books +1 <= $max;
    }

    public static function deleteAllByUserId($userId) {
        $query = self::execute("DELETE FROM rental "
                . "WHERE user = :id", array("id"=>$userId));
    }
    
    public static function deleteAllByBookId($bookId) {
        $query = self::execute("DELETE FROM rental "
                . "WHERE book = :id", array("id"=>$bookId));
    }
            
    function getBookTitle() {
        if(is_numeric($this->book)) {
            $this->book = Book::byId($this->book);
        }
        return $this->book->getTitle();
    }

    function getRentalDateToBeReturned() {
        $res = clone $this->rentalDate; // clone necessary
        $res = $res->modify(Configuration::get('max_rental_duration'));
        return $res;
    }

    function getId() {
        return $this->id;
    }

    function getUser() {
        return $this->user;
    }

    function getBook() {
        return $this->book;
    }

    function getRentalDate($obj = false) {
        if($obj)
            return $this->rentalDate;
        
        if ($this->rentalDate === null)
            return null;
        else
            return $this->rentalDate->format('d/m/Y H:i:s');
    }
    
    function getReturnDate($obj = false) {
        if($obj)
            return $this->returnDate;
        
        if ($this->returnDate === null)
            return null;
        else
            return $this->returnDate->format('d/m/Y H:i:s');
    }

}
