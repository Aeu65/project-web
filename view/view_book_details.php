<!DOCTYPE html>
<html>
    <head>
        <title>Book Details</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Book details</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <div class ="book-details">
                <table>
                    <tr>
                        <th>ISBN</th>
                        <td><?= $book->getIsbnFormatted() ?></td>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <td><b><?= $book->getTitle() ?></b></td>
                    </tr>
                    <tr>
                        <th>Author</th>
                        <td><?= $book->getAuthor() ?></td>
                    </tr>
                    <tr>
                        <th>Editor</th>
                        <td><?= $book->getEditor() ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td><?= $book->getNbCopies() ?></td>
                    </tr>
                    <tr>
                        <th>Picture</th>
                        <?php if(empty($book->getPicture())): ?>
                        <td><div class="no-picture"></div></td>
                        <?php else: ?>
                        <td><img class="picture" src="upload/<?= $book->getPicture() ?>"
                                 alt="<?= $book->getTitle() ?>"></td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>
        </div>
    </body>
</html>


