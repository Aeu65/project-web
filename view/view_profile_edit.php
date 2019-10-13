<!DOCTYPE html>
<html>
    <head>
        <title>Edit User</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Edit User</div>
        <?php include("profile_menu.php"); ?>
        <div class="main">
            Please enter the user details :
            <br><br>
            <form id="editprofile" action="profile/edit/<?= $id ?>" method="POST">
                <table>
                    <tr>
                        <td>User Name:</td>
                        <td><input id="username" name="username" type="text" value="<?php echo $username ?>" required></td>
                    </tr>
                    <tr>
                        <td>Full Name:</td>
                        <td><input id="fullname" name="fullname" type="text" value="<?php echo $fullname ?>" required></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td><input id="email" name="email" type="email" value="<?php echo $email ?>" required></td>
                    </tr>
                    <tr>
                        <td>Birth Date:</td>
                        <td>
                            <input id="birthdate" name="birthdate" type="date" value="<?php echo $birthdate ?>">
                            <input id="trickyhack" type="hidden" name="save" value="Save">
                        </td>
                    </tr>
                    <tr>
                        <td>Role:</td>
                        <td>
                            <select id="role" name="role" <?= $isAdmin ? 'required' : 'disabled ' ?>>
                                <option label="Choose" value=""></option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>admin</option>
                                <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>manager</option>
                                <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>member</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
            <table>
                <tr><td></td><td></td></tr>
                <tr>
                    <td class="btnRow" colspan="2">
                        <input type="submit" name="save" value="Save" form="editprofile">
                        <form action="profile/edit/<?= $id ?>" method="POST" class="inline">
                            <input type="submit" name="cancel" value="Cancel">
                        </form>
                    </td>
                </tr>
            </table>
            
            <?php
            if (isset($errors) && count($errors) > 0) {
                echo "<div class='errors'>
                          <p>Please correct the following error(s) :</p>
                          <ul>";
                foreach ($errors as $error) {
                    echo "<li>" . $error . "</li>";
                }
                echo '</ul></div>';
            }
            ?>
        </div>
        
        <!-- jQuery -->
        <script src="vendor/jquery/jquery.min.js"></script>
        
        <!-- jQuery Validation -->
        <script src="vendor/jquery-validation/jquery.validate.min.js"></script>
        <script src="vendor/jquery-validation/additional-methods.min.js"></script>
        
        <!-- Project's code  -->
        <script src="js/view_profile_edit.js"></script>
    </body>
</html>