/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$.validator.addMethod("regex", function (value, element, pattern) {
    if (pattern instanceof Array) {
        for(p of pattern) {
            if (!p.test(value))
                return false;
        }
        return true;
    } else {
        return pattern.test(value);
    }
}, "Please enter a valid input.");

$.validator.addMethod("pastDate", function (value, element) {
    if("" === value) return true;
    
    let birthdate = new Date(value);
    let now = new Date();
    
    return birthdate <= now;
}, "Birthdate in the future. Are you... the terminator ? :)");

$(function() {
    
        $("#editprofile").validate({
        rules : {
            username: {
                required: true,
                minlength: 3,
                maxlength: 16,
                regex: /^[a-zA-Z][a-zA-Z0-9]*$/,
                remote: {
                    url: 'profile/ajaxUsernameAvailable',
                    type: 'post',
                    data: {
                        username: function() {
                            return $("#username").val();
                        },
                        editusername: function() {
                            let action = $("#editprofile").attr("action");
                            let id = action.replace("profile/edit/","");
                            return id;
                        }
                    }
                }
            },
            fullname: {
                required: true,
                maxlength: 255
            },
            email: {
                required: true,
                email: true,
                regex: /.+@.+\..+$/,
                remote: {
                    url: 'profile/ajaxEmailAvailable',
                    type: 'post',
                    data: {
                        email: function() {
                            return $("#email").val();
                        },
                        editemail: function() {
                            let action = $("#editprofile").attr("action");
                            let id = action.replace("profile/edit/","");
                            return id;
                        }
                    }
                }
            },
            birthdate: {
                dateISO: true,
                pastDate: true
            },
            role: {
                regex: /^(member|manager|admin)$/
            }
        },
        messages: {
            username: {
                regex: "Username must start by a letter and must contain only letters and numbers.",
                remote: "This username is unavailable"
            },
            email: {
                regex: "Missing TLD",
                remote: "An account has already been registered with this email address."
            },
            role: {
                regex: "Wrong Role"
            }
        }
    });

    $("input:text:first").focus();
});

// Custom listeners
$("#birthdate").on("change", function() {
    $("#birthdate").valid();
});

// Custom helpers
//function isValidDate(d) {
//    if (Object.prototype.toString.call(d) === "[object Date]"
//        && !isNaN(d.getTime())) {
//        return true;
//    }
//    return false;
//}
