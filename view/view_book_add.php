<!DOCTYPE html>
<html>
    <head>
        <title>Add Book</title>
        <base href="<?= $web_root ?>"/>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    </head>
    <body>
        <div class="title">Add Book</div>
        <?php include('profile_menu.php'); ?>
        <div class="main">
            <div class ="book-details-edit">
                <form action="book/add<?= $slash_encoded_filter ?>" method="POST"
                      enctype="multipart/form-data" id="formadd">
                <table>
                    <tr>
                        <th><label for="isbn">ISBN
                            <i class="field-required"></i></label></th>
                        <td>
                            <input type="text" id="isbn" name="isbn"
                                   value="<?= $isbn ?>" required>
                            -
                            <input type="text" id="checkdigit" name="checkdigit"
                                   class="checkdigit"
                                   minlength="1" maxlength="1"
                                   value="<?= $checkdigit ?>"
                                   disabled>
                                <label for="isbn" generated="true" class="error"
                                ></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="title">Title
                            <i class="field-required"></i></label></th>
                        <td>
                            <input type="text" id="title" name="title"
                                   value="<?= $title ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="author">Author
                            <i class="field-required"></i></label></th>
                        <td>
                            <input type="text" id="author" name="author"
                                   value="<?= $author ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="editor">Editor
                            <i class="field-required"></i></label></th>
                        <td>
                            <input type="text" id="editor" name="editor"
                                   value="<?= $editor ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nbCopies">Amount
                            <i class="field-required"></i></label></th>
                        <td>
                            <input type="number" id="nbCopies" name="nbCopies"
                                   value="<?= $nbCopies ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="picture">Picture</label></th>
                        
                        <td class="manage-picture">
                            <input type="file" id="picture" name="picture"
                                   accept="image/x-png, image/gif, image/jpeg">
                            
                            <div class="no-picture"></div>
                        </td>
                        
                    </tr>
                </table>
                </form>
                <table>
                    <tr>
                        <td class="btnRow" colspan="2">
                            <input type="submit" name="save" value="Save" form="formadd">
                            <form action="book/add<?= $slash_encoded_filter ?>" method="POST" class="inline">
                                <input type="submit" name="cancel" value="Cancel">
                            </form>
                            
                        </td>
                    </tr>
                </table>

            </div>
            <p><i class="field-required"></i> required fields</p>
            
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
        <script src="js/view_book_add.js"></script>
    </body>
</html>


