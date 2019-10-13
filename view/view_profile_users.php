<!DOCTYPE html>
<html>
    <head>
        <title>Users</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Users</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <table class="message_list">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Birth Date</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $usr) : ?>
                        <tr>
                        <td><?= $usr->getUsername() ?></td>
                        <td><?= $usr->getFullname() ?></td>
                        <td><?= $usr->getEmail() ?></td>
                        <td><?= Utils::format_date($usr->getBirthdate()) ?></td>
                        <td><?= $usr->getRole() ?></td>
                        <td>
                            <form class="button" action="profile/edit/<?= $usr->getId() ?>" method="GET">
                                <input type="submit" value="Edit">
                            </form>
                            <?php if ($isAdmin && $usr->getId() !== $logged_userid): ?>
                                <form class="button" action="profile/delete/<?= $usr->getId() ?>" method="GET">
                                    <input type="submit" value="Delete">
                                </form>
                            <?php endif; ?>
                        </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <form class="button" action="profile/add" method="GET">
                <input type="submit" value="New User">
            </form>
        </div>
    </body>
</html>