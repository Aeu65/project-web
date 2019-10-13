<?php
/*
 * IMPORTANT note on maintenance
 * the following code is a copy taken from view_rental_books.php
 * 
 * It's better not to modify it directely, but instead
 * updating the view_rental_books.php and then paste a copy
 * of the original code here below, so that both codes match
 * 
 */

foreach ($available as $book): ?>
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
