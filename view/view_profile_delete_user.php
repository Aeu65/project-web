<!DOCTYPE html>
<html>
    <head>
        <title>Confirm Deletion</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Confirm Deletion</div>
        <?php include("profile_menu.php"); ?>
        <div class="main">
            <p> You are about to delete the user '<?= $usrDelFullname ?>'.<br>If this is correct, please confirm.</p>
            <form method="post">
                <input type="submit" name="confirm" value="Confirm">
                <input type="submit" name="cancel" value="Cancel">
            </form>
        </div>
    </body>
</html>
