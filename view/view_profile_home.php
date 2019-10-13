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
        <div class="title">Rentals</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <table class="message_list">
                <thead>
                    <tr>
                        <th>Rental Date</th>
                        <th>Book</th>
                        <th>To be returned on</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rentals)): ?>
                    <tr>
                        <td class="center" colspan="3">no content here</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($rentals as $rental) : ?>
                        <?php if (!$rental->bookIsReturned()) { ?>
                            <tr>
                                <td><?= $rental->getRentalDate() ?></td>
                                <td><?= $rental->getBookTitle() ?></td>
                                <?php if ($rental->userIsLate()) { ?>
                                    <td style="color:red;"><?= $rental->getRentalDateToBeReturned()->format('d/m/Y H:i:s') ?></td>
                                <?php } else { ?>
                                    <td><?= $rental->getRentalDateToBeReturned()->format('d/m/Y H:i:s') ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <form class="button" method="GET" action="rental/books/<?= $logged_username ?>">
                <input type="submit" value="Books">
            </form>
            <?php if ($isAdminOrManager) { ?>
                <form class="button" method="GET" action="rental/managereturns">
                    <input type="submit" value="Book Return">
                </form>
            <?php } ?>
        </div>
    </body>
</html>

