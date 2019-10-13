/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// Globals
isbnErrorMsg = "";

$.validator.addMethod("validIsbn", function (value, element, pattern) {
//    console.log("validating isbn");
    
    // required
    if( !(value.length > 0)) {
        isbnErrorMsg = "The field is required";
        return false;
    }
    
    // only digits
    if(!/^\d+$/.test(value)) {
        isbnErrorMsg = "Only digits (0-9)";
        return false;
    }
    
    // for book validation only:
    // firt 3 digits must be either 977 (Serial publications - ISSN),
    // or 978 or 979 (Bookland - ISBN)
    if(!/^(977|978|979)/.test(value)){
        isbnErrorMsg = "Must begin with 977, 978 or 979";
        return false;
    }
    
    // the followings exist by default in jQuery Validation
    // but this method will be used from external code
    // to validate & generate checkdigit when the user leaves the field
    
    // minlength
    if(value.length < 12) {
        isbnErrorMsg = "Please enter at least 12 characters";
        return false;
    }
    
    // maxlength
    if(value.length > 12) {
        isbnErrorMsg = "Please enter no more than 12 characters";
        return false;
    }
    
    isbnErrorMsg = "";
    return true;
    
}, function (params, element) {
    return isbnErrorMsg;
});

$(function() {
    
        $("#formedit").validate({
        rules : {
            isbn: {
                validIsbn: true,
                remote: {
                    url: 'book/ajaxIsbnAvailable',
                    type: 'post',
                    data: {
                        isbn: function() {
                            return $("#isbn").val() + $("#checkdigit").val();
                        },
                        editbook: function() {
                            let action = $("#formedit").attr("action");
                            let id = action.replace("book/edit/","");
                            return id;
                        }
                    }
                }
                // check if isbnAvailable // 'remote'
            },
            title: {
                maxlength: 255
            },
            author: {
                maxlength: 255
            },
            editor: {
                maxlength: 255
            },
            nbCopies: {
                min: 0
            }
        },
        messages: {
            isbn: {
                remote: "This ISBN is unavailable"
            }
        }
    });

    $("input:text:first").focus();
});

// Custom listeners

// when the user leaves the isbn field
$("#isbn").on("blur", function() {
    let isValidISBN = $.validator.methods.validIsbn($("#isbn").val());
    if(isValidISBN) {
        // add dashes
        let currentVal = $("#isbn").val();
        $("#isbn").val(isbnAddDashes(currentVal));
    }
});

// when the user enters the isbn field
$("#isbn").on("focus", function() {
    let currentVal = $("#isbn").val();
    $("#isbn").val(strip_dashes(currentVal));
});

// update checkdigit when typing the ISBN
$("#isbn").on("input", function() {
    let part = this.value;
    
    if(/^\d+$/.test(part)) { // only works on digits
        // pad the part with 0s until it's 12 chars long 
        let paddedPart = part.padEnd(12, 0);
        let checkdigit = getCheckDigit(paddedPart);
        
        $("#checkdigit").val(checkdigit);
    } else {
        $("#checkdigit").val('');
    }
});

$("#formedit").on("submit", function() {
    let currentVal = $("#isbn").val();
    $("#isbn").val(strip_dashes(currentVal));
});

// Helper functions
function strip_dashes(str) {
    return str.replace(/-/gi, "");
}

// example:
//      input   978111924779 (ISBN 13 without checkdigit)
//      output  978-1-1192-4779
//                 | |    |
//      indexes    3 5    10
function isbnAddDashes(isbn) {
    // creates an array of letters from a string
    let arr = isbn.split('');
    
    // inserts the dashes in position
    arr.splice(3, 0, "-");
    arr.splice(5, 0, "-");
    arr.splice(10, 0, "-");
    
    return arr.join('');
}

// checkdigit stuff

//function getRandomInt(max) {
//    return Math.floor(Math.random() * Math.floor(max));
//}

function getCheckDigit(isbn) {
    // should be 12 chars long,
    // only works on digits
    if(isbn.length === 12
       && /^\d+$/.test(isbn)) {     
   
        let total = 0;
    
        // creates an array of letters from a string
        let arr = isbn.split('');

        for (var i = 0; i < 12; i++) {
            let letter = arr[i];
            
            // parse the letter to int
            let n = parseInt(letter);
            
            if(i % 2 === 0) {
                total += n;
            } else {
                total += (n*3);
            }
        }
        
        let checksum = 10 - (total % 10);
        if(checksum === 10) {
            checksum = 0;
        }
        
        return checksum;
        
    } else {
        console.error("isbn should be 12 chars long");
    }
    
}
