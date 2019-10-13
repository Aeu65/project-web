<div class="menu">
    <a href="profile/index/">Home (<?= $logged_username ?>)</a>
    <?php if ($isAdminOrManager): ?>
    <a href="profile/users/">Users</a>
    <?php endif; ?>
    <a href="rental/books/<?php
        echo $logged_username;
        
        // for page 'edit book' => affects the link to 'rent a book'
        // in order to keep the filter
        if(isset($filter) && !empty($filter)) {
            if(!isset($filter_is_encoded) || !is_bool($filter_is_encoded)) {
                $filter_is_encoded = FALSE;
            }
            if($filter_is_encoded) {
                echo '/', $filter;
            } else {
                echo '/', Utils::url_safe_encode($filter);
            }
        }
        ?>">Books</a>
    <?php if ($isAdminOrManager): ?>
    <a href="rental/managereturns/">Rentals</a>
    <?php endif; ?>
    <a href="profile/logoff/">Log Out</a>
</div>