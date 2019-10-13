/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Expose the base href link so that we can use it in JS
    baseHref = (document.getElementsByTagName('base')[0] || {}).href;
    if(null === baseHref)
    {
      console.error("this page should have a base element\n\
      with an href attribute pointing to the 'web root'");
      baseHref = '';
    }
    
    // Globals
    basketIsFor = $("#basketisfor").attr("data-value");
    oldBasketIsFor = basketIsFor;
    
    slashEncodedFilter = $("#slashencodedfilter").attr("data-value");
    oldFilter = slashEncodedFilter;
    
    // Hide the button "Apply Filter" next to the label "Text filter"
    $("[name=applyfilter]").hide();
    // Hide the button "Apply" next to the select "Prepare for $user"
    $("[name=basketisfor]").hide();
    
    // Get the elems corresponding to the filters
    searchByTextFilter = $("#filter");
    selectUser = $("#select-user");
    
    // Custom Listeners
    $("#filter").on("input", refreshWithFilter);
    $("#select-user").on("change", changeBasketIsFor);
    
    // AJAX timer
    // If we modify a filter input field,
    // it wont fetch resources in ajax immediately but will instead
    // wait a certain time before fetching the data
    // so that we don't overuse server resources
    
    var delayTimer;
    
    function refetchData() {
        clearTimeout(delayTimer);
            delayTimer = setTimeout(function() {
                console.log("delayed ajax request");
                ajaxAvailableBooks();
                ajaxBasket();
        }, 1500); // Will do the ajax stuff after 1500 ms, or 1.5 s
    }
    
    
    function refreshWithFilter() {
        console.log(searchByTextFilter.val());
        reGenFilter();
        updateFormsAndLinks();
        refreshUrl();
        refetchData();
    }
    
    function changeBasketIsFor() {
        oldBasketIsFor = basketIsFor;
        basketIsFor = $("#select-user :selected").val();
        updateFormsAndLinks();
        refreshUrl();
        refetchData();
    }
    
    function reGenFilter() {
        oldFilter = slashEncodedFilter;
        let filter = searchByTextFilter.val();
        if(filter.length > 0) {
            let encoded_filter = url_safe_encode(filter);
            slashEncodedFilter = '/' +encoded_filter;
        } else {
            slashEncodedFilter = "";
        }
    }
    
    replaceHelper = function (str, oldValue, newValue) {
        if(oldValue.length > 0) {
            return str.replace(oldValue, newValue);
        }
        // appends instead of default (prepend)
        return str +newValue;
    }
    
    function updateFormsAndLinks() {
        // edit/addtobasket/delete book links
        $("[title='edit book']").each(function (index, elem) {
            console.log(oldFilter, slashEncodedFilter);
            elem.href = replaceHelper(elem.href, oldFilter, slashEncodedFilter);
            console.log(elem);
        });
        $("[name='addtobasket']").each(function (index, elem) {
            console.log(oldFilter, slashEncodedFilter);
            elem.action = replaceHelper(elem.action, oldBasketIsFor, basketIsFor);
            elem.action = replaceHelper(elem.action, oldFilter, slashEncodedFilter);
            console.log(elem);
        });
        $("[title='delete book']").each(function (index, elem) {
            console.log(oldFilter, slashEncodedFilter);
            elem.href = replaceHelper(elem.href, oldFilter, slashEncodedFilter)
            console.log(elem);
        });
        $("[title='book details']").each(function (index, elem) {
            console.log(oldFilter, slashEncodedFilter);
            elem.href = replaceHelper(elem.href, oldFilter, slashEncodedFilter)
            console.log(elem);
        });
        $("[name='removefrombasket']").each(function (index, elem) {
            console.log(oldFilter, slashEncodedFilter);
            elem.action = replaceHelper(elem.action, oldBasketIsFor, basketIsFor);
            elem.action = replaceHelper(elem.action, oldFilter, slashEncodedFilter);
            console.log(elem);
        });
        
        // Add book button
        if($("#addbook").length) { // if the element exists
            let action = $("#addbook").attr("action");
            action = replaceHelper(action, oldFilter, slashEncodedFilter);
            
            console.log(oldFilter, slashEncodedFilter); 
            $("#addbook").attr("action", action);
            console.log($("#addbook"));
        }
        
        // Prepare basket form
        if($("#preparebasket").length) { // if the element exists
            let action = $("#preparebasket").attr("action");
            action = replaceHelper(action, oldBasketIsFor, basketIsFor);
            action = replaceHelper(action, oldFilter, slashEncodedFilter);
            
            console.log(oldFilter, slashEncodedFilter);
            $("#preparebasket").attr("action", action);
            console.log($("#preparebasket"));
        }
        
        // Manage basket form
        {
            let action = $("#managebasket").attr("action");
            action = replaceHelper(action, oldBasketIsFor, basketIsFor);
            action = replaceHelper(action, oldFilter, slashEncodedFilter);

            console.log(oldFilter, slashEncodedFilter);
            $("#managebasket").attr("action", action);
            console.log($("#managebasket"));
        }
        
        
    }
    
    function refreshUrl() {
        history.pushState(null, null,
                          "rental/books/" +basketIsFor +slashEncodedFilter);
    }
 
    preventSubmit = function (e) {
        e.preventDefault();
        return false;
    }
 
    // AJAX actions
    
    function ajaxAvailableBooks() {
        let params = { basketisfor: basketIsFor };
        if(slashEncodedFilter.length > 1) {
            // strip initial "/"
            params.textfilter = slashEncodedFilter.substring(1);
        }
        
        $.post(baseHref + "rental/ajaxAvailableBooks", params)
        .done(function(data, textStatus, jqXHR) {
            console.log("ajaxReturnRental",
                        jqXHR.status, " ",
                        textStatus, " ",
                        jqXHR.statusText);
            
            // data handling
            $("#table-available-books tbody").html(data);
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("ajaxReturnRental",
                          jqXHR.status, " ",
                          textStatus, " ",
                          jqXHR.statusText);
        });
    }
    
    function ajaxBasket() {
        let params = { basketisfor: basketIsFor };
        if(slashEncodedFilter.length > 1) {
            // strip initial "/"
            params.textfilter = slashEncodedFilter.substring(1);
        }
        
        $.post(baseHref + "rental/ajaxBasket", params)
        .done(function(data, textStatus, jqXHR) {
            console.log("ajaxReturnRental",
                        jqXHR.status, " ",
                        textStatus, " ",
                        jqXHR.statusText);
            
            // data handling
            $("#table-basket tbody").html(data);
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("ajaxReturnRental",
                          jqXHR.status, " ",
                          textStatus, " ",
                          jqXHR.statusText);
        });
    }
 
});
