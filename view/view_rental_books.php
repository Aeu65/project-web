<!DOCTYPE html>
<html>
    <head>
        <title>Rentals</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
        
    </head>
    <body>
        <div class="title">Rent a book</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <div class="filter-box">
                <form action="rental/books/<?= $basketIsFor->getUsername() ?>"
                      onsubmit="return preventSubmit(event)"
                      method="POST">
                    <label for="filter">Text filter:</label>
                    <input id="filter" name="q" type="search" value="<?= $filter ?>">
                    <input type="submit" name="applyfilter" value="Apply Filter">
                </form>
            </div>
            <div id="basketisfor" data-value="<?= $basketIsFor->getUsername() ?>"></div>
            <div id="slashencodedfilter" data-value="<?= $slash_encoded_filter ?>"></div>
            
            <h2>Books available to rent</h2>
            
            <div class="wrapper-scrollable">
            <table id="table-available-books" class="message_list book-table">
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Editor</th>
                        <th>Availabilities</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                 <?php foreach ($available as $book): ?>
                    <tr>
                        <td><?= $book->getIsbnFormatted() ?></td>
                        <td><b><?= $book->getTitle() ?></b></td>
                        <td><?= $book->getAuthor() ?></td>
                        <td><?= $book->getEditor() ?></td>
                        <td><?= $book->availabilities() ?></td>
                        <td class="td-nowrap">
                            <?php if($isAdmin): ?>
                            <a href="book/edit/<?= $book->getId().$slash_encoded_filter ?>"
                               title="edit book" class=""><img src="assets/google-icons/ic_create_black_36dp.png" alt="edit icon"></a>
                            <?php else: ?>
                            <a href="book/details/<?= $book->getId().$slash_encoded_filter ?>"
                               title="book details" class=""><img src="assets/google-icons/ic_visibility_black_36dp.png" alt="preview icon"></a>
                            <?php endif; ?>
                            
                           <form class="inline"
                                 action="rental/addtobasket/<?= $basketIsFor->getUsername().$slash_encoded_filter ?>"
                                 name="addtobasket" method="POST">
                               <input type="hidden" name="id" value="<?= $book-> getId() ?>">
                               <input title="add to basket" type="submit" value="" class="addtobasket">
                           </form>
                            <?php if($isAdmin): ?>
                                <a href="book/remove/<?= $book->getId().$slash_encoded_filter ?>"
                               title="delete book" class=""><img src="assets/google-icons/ic_delete_forever_black_36dp.png" alt="delete icon"></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            
            <div class="add-book-btn">
                <h2>Basket of Books to rent</h2>
                
                <?php if($isAdmin): ?>
                <form action="book/add<?= $slash_encoded_filter ?>" id="addbook" method="GET">
                    <input title="add a book" type="submit" value="Add Book">
                </form>
                <?php endif; ?>
            </div>

            <div class="wrapper-scrollable">
            <table id="table-basket" class="message_list book-table">
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Editor</th>
                        <th>Availabilities</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($basket as $book): ?>
                    <tr>
                        <td><?= $book->getIsbnFormatted() ?></td>
                        <td><b><?= $book->getTitle() ?></b></td>
                        <td><?= $book->getAuthor() ?></td>
                        <td><?= $book->getEditor() ?></td>
                        <td><?= $book->availabilities() ?></td>
                        <td class="td-nowrap">
                            <?php if($isAdmin): ?>
                            <a href="book/edit/<?= $book->getId().$slash_encoded_filter ?>"
                               title="edit book" class=""><img src="assets/google-icons/ic_create_black_36dp.png" alt="edit icon"></a>
                            <?php else: ?>
                            <a href="book/details/<?= $book->getId().$slash_encoded_filter ?>"
                               title="book details" class=""><img src="assets/google-icons/ic_visibility_black_36dp.png" alt="preview icon"></a>
                            <?php endif; ?>
                           
                            <form class="inline"
                                action="rental/removefrombasket/<?= $basketIsFor->getUsername().$slash_encoded_filter ?>" name="removefrombasket" method="POST">
                                <input type="hidden" name="id" value="<?= $book-> getId() ?>">
                                <input title="remove from basket" type="submit" value="" class="removefrombasket">
                            </form>   
                               
                            <?php if($isAdmin): ?>
                                <a href="book/remove/<?= $book->getId().$slash_encoded_filter ?>"
                               title="delete book" class=""><img src="assets/google-icons/ic_delete_forever_black_36dp.png" alt="delete icon"></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
                
            <?php
            if (!empty($errors)) {
                echo "<div class='errors'><ul>";
                foreach ($errors as $error) {
                    echo "<li>" . $error . "</li>";
                }
                echo '</ul></div>';
            }
            ?>

            <div class="basketControls">
            
                <?php if($isAdminOrManager): ?>
                <form action="rental/basketisfor<?= $slash_encoded_filter ?>"
                      id="preparebasket"
                      method="POST" class="inline">
                <label for="select-user">Prepare basket for</label>
                <select name="user" id="select-user" required>
                    <option label="Choose" value=""></option>
                    <?php foreach ($users as $usr){
                        $username = $usr->getUsername();
                        $selected =($usr->getUsername() === $basketIsFor->getUsername()) ?
                                'selected' : '';
                        
                        echo "<option value=\"", $username, "\" ", $selected,
                                ">", $username, "</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="basketisfor" value="Apply">
                </form>
                <?php endif; ?>
            <form id="managebasket" class="inline"
                  action="rental/managebasket/<?= $basketIsFor->getUsername().$slash_encoded_filter ?>"
                  method="POST">
                <div class="float-right">
                    <input title="confirm basket"
                       type="submit" name="confirm" value="Confirm Basket">
                    <input title="clear basket"
                           type="submit" name="clear" value="Clear Basket">
                </div>
            </form>
            </div>
        </div>
        
        <!-- url-tools-bundle -->
        <script src="vendor/url-tools-bundle.min.js"></script>
        
        <!-- jQuery -->
        <script src="vendor/jquery/jquery.min.js"></script>
        
        <!-- Project's code  -->
        <script src="js/view_rental_books.js"></script>
    </body>
</html>

