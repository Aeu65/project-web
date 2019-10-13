<!DOCTYPE html>
<html>
    <head>
        <title>Return</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <!-- FullCalendar TimeLine stuff -->
        <link rel="stylesheet" href="vendor/fullcalendar-scheduler/packages/core/main.min.css">
        <link rel="stylesheet" href="vendor/fullcalendar-scheduler/packages/timeline/main.min.css">
        <link rel="stylesheet" href="vendor/fullcalendar-scheduler/packages/resource-timeline/main.min.css">
        <!-- /FullCalendar TimeLine stuff -->
        
        <!-- jQuery UI stuff -->
        <link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
        <link href="vendor/jquery-ui/jquery-ui.theme.min.css" rel="stylesheet" type="text/css"/>
        <link href="vendor/jquery-ui/jquery-ui.structure.min.css" rel="stylesheet" type="text/css"/>
        <!-- /jQuery UI stuff -->
        
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Manage rentals</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <div class="filter-box full-width-table">
                <form action="rental/managereturns" method="POST">
                    <table>
                        <col style="width: 20%">
                        <col style="width: 40%">
                        <col style="width: 40%">
                        <tr>
			    <th>User</th>
                            <td><input id="searchByMember" name="searchByMember" type="text" value="<?= $usrSearch ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Book</th>
                            <td><input id="searchByBook" name="searchByBook" type="text" value="<?= $bookSearch ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Rental Date</th>
                            <td><input id="searchByDate" name="searchByDate" type="date" value="<?= $dateSearch ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <th>Rental Date</th>
                            <td>
                                <label for="open">Open</label> 
                                <input id="open" type="radio" name="state" 
                                       value ="open" <?= $check = ($state === 'open')? 'checked' : null ?>>
                            
                                <label for="returned">Returned</label>
                                <input id="returned" type="radio" name="state" 
                                       value ="returned" <?= $check = ($state === 'returned')? 'checked' : null ?>>
                            
                                <label for="all">All</label>
                                <input id="all" type="radio" name="state" 
                                       value ="all" <?= $check = ($state === 'all')? 'checked' : null ?>>
                            </td>
                            <td class="align-right">
                                <input type="submit" name="search" value="Search">
                            </td>
                        </tr>
                    </table>
                    
                </form>
            </div>

            <div id="calendar"></div>
            
            <noscript>
            <table class="message_list book-table">
                <thead>
                    <tr>
                        <th>Rental Date/Time</th>
                        <th>User</th>
                        <th>Book</th>
                        <th>Return Date/Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental): ?>
                        <tr>
                            <td><?= $rental->getRentalDate() ?></td>
                            <td><b><?= $rental->getUser()->getUsername() ?></b></td>
                            <td><?= $rental->getBook()->getTitle() ?></td>
                            <td><?= $rental->getReturnDate() ?></td>
                            <td class="td-nowrap"> 
                                <?php if ($isAdmin): ?>
                                <form class="inline"
                                    action="rental/remove<?= $slash_encoded_filter ?>" name="remove" method="POST">
                                    <input type="hidden" name="id" value="<?= $rental-> getId() ?>">
                                    <input title="remove rental" type="submit" value="" class="removerental">
                                </form>    
                                <?php endif; ?>
                                <?php if(null == $rental->getReturnDate()): ?>
                                <form class="inline"
                                      action="rental/encodereturn<?= $slash_encoded_filter ?>" name="encode" method="POST">
                                    <input type="hidden" name="id" value="<?= $rental->getId() ?>">
                                    <input title="return book" type="submit" value="" class="returnbook">
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </noscript>

        </div>
        
        <div id="canDelete" data-value="<?php if($isAdmin) echo "true"; ?>"></div>
        <div id="confirmDialog" title="Rental Details" hidden>
            <p>
                User: <b id="crUser"></b><br>
                Book: <b id="crBook"></b><br>
                Rental Date: <b id="crRentalDate"></b><br>
                Return Date: <b id="crReturnDate"></b>
            </p>
        </div>
        
        <!-- jQuery -->
        <script src="vendor/jquery/jquery.min.js"></script>
        
        <!-- jQuery UI -->
        <script src="vendor/jquery-ui/jquery-ui.min.js"></script>
        
        <!-- FullCalendar TimeLine stuff -->
        <script src='vendor/fullcalendar-scheduler/packages/core/main.min.js'></script>
        <script src='vendor/fullcalendar-scheduler/packages/interaction/main.min.js'></script>
        <script src='vendor/fullcalendar-scheduler/packages/timeline/main.min.js'></script>
        <script src='vendor/fullcalendar-scheduler/packages/resource-common/main.min.js'></script>
        <script src='vendor/fullcalendar-scheduler/packages/resource-timeline/main.min.js'></script>
        <!-- /FullCalendar TimeLine stuff -->
        
        <!-- url_safe_encode / url_safe_decode in JS -->
        <!--<script src='vendor/url-tools-bundle.min.js'></script>-->
        
        <!-- Project's code  -->
        <script src="js/view_rental_book_return.js"></script>
    </body>
</html>



