/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // Expose the base href link so that we can use it in JS
  const baseHref = (document.getElementsByTagName('base')[0] || {}).href;
  if(null === baseHref)
  {
    console.error("this page should have a base element\n\
    with an href attribute pointing to the 'web root'");
    baseHref = '';
  }
  
  // Get the elems of the "filter" form
  searchByMemberElem = $("[name=searchByMember]")[0];
  searchByBookElem = $("[name=searchByBook]")[0];
  searchByDateElem = $("[name=searchByDate]")[0];
  searchByStateRadioBtnElems = $("[name=state]");
  
  // Hide the button "Search" - no need for it because requests are in AJAX
  $("[name=search]").hide();
  
  // Defines if the rental dialog can show the delete rental button
  var canDelete = function () {
      return $("#canDelete").attr("data-value") === "true";
  };
  var dialogAction = null; // initializing dialog action
  var currentRental = null; // corresponding to the last clicked event
  
  // FullCalendar stuff
    
  var calendarEl = document.getElementById('calendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
    plugins: [ 'interaction', 'resourceTimeline' ],
    timeZone: 'Europe/Brussels',
    defaultView: 'customWeek',
    header: {
      left: 'today prev,next',
      center: 'title',
      right: 'customWeek,customMonth,customYear'
    },
    scrollTime: '08:00',
    aspectRatio: 2.5,
    resourceColumns: [
        {
          labelText: 'User',
          field: 'user'
        },
        {
          labelText: 'Book',
          field: 'book'
        }
    ],
        views: {
    customWeek: {
//      type: 'resourceTimelineWeek',
//      duration: { days: 7},
//      buttonText: '4 day'
      type: 'resourceTimelineWeek',
      duration: { days : 7 },
      slotDuration: {days: 1},
      buttonText: 'week',
      slotLabelFormat: {
        day: 'numeric',
        weekday: 'short'
      }
    },
    customMonth: {
        type: 'resourceTimelineMonth',
        slotWidth: 15,
        slotLabelFormat: {
            day: 'numeric'
        }
    },
    customYear: {
        type: 'resourceTimelineYear',
        slotDuration: {months: 1},
        slotLabelFormat: {
            month: 'short'
        }
    }
  },
//  columnHeaderFormat: {
//    customWeek: {
//        day: 'short'
//    }
//    ,
//    timelineFifteenDay: 'dddd D M',
//    timelineThirtyDay: 'dddd D M'
//  },
    editable: false,
//    resourceLabelText: 'Rooms',
//    resources: 'https://fullcalendar.io/demo-resources.json?with-nesting&with-colors',
//    resources: baseHref+'js/fixtures-resources.json',
    resourceOrder: 'orderId',
    resources: {
        method: 'POST',
        url: baseHref+'rental/ajaxResources',
        extraParams: function() {
            return {
                searchByMember : searchByMemberElem.value,
                searchByBook : searchByBookElem.value,
                searchByDate : searchByDateElem.value,
                state : stateSelected()
            }
        }
    },
    displayEventTime : false,
    eventClick : eventClickHandler,
    events: {
        method: 'POST',
        url: baseHref+'rental/ajaxEvents',
        extraParams: function() { // a function that returns an object
            return {
                searchByMember : searchByMemberElem.value,
                searchByBook : searchByBookElem.value,
                searchByDate : searchByDateElem.value,
                state : stateSelected()
            }
    //      return {
    //        dynamic_value: Math.random()
            // ici il faut faire appel à jquery qui va chercher
            // les valeurs de données dans le input
            // notes addtionnelle : il met start et stop dans les param de la requete post (automatique quand on met la méthode à POST)
    //      };
        }
  }
//    events: baseHref+'/rental/ajaxResources',
//    events: baseHref+'js/fixtures-events.json',
//    events: 'https://fullcalendar.io/demo-events.json?single-day&for-resource-timeline'
  });

  calendar.render();
  // /FullCalendar stuff
  
  // Custom Code, Helpers, Utils
    // returns the state currently selected in the radio buttons
    // type returned is string
    function stateSelected() {
      for(radioBtn of searchByStateRadioBtnElems) {
          if(radioBtn.checked === true) {
              return radioBtn.value;
          }
      }
      return 'all';
    }

    // AJAX timer
    // If we modify a filter input field,
    // it wont fetch resources in ajax immediately but will instead
    // wait a certain time before fetching the resources/events
    // so that we don't overuse server resources
    
    var delayTimer;
    
    function refreshWithFilter() {
//        console.log("refreshWithFilter");
        clearTimeout(delayTimer);
            delayTimer = setTimeout(function() {
            calendarRefetch();
        }, 1500); // Will do the ajax stuff after 1500 ms, or 1.5 s
    }

    function calendarRefetch() {
//        console.log("effective refetch");
        calendar.refetchEvents();
        calendar.refetchResources();
    }
    
      // Custom Listeners
    $("[name=searchByMember]:first-child").on("input", refreshWithFilter);
    $("[name=searchByBook]:first-child").on("input", refreshWithFilter);
    $("[name=searchByDate]:first-child").on("input", refreshWithFilter);
    
    $("[name=state]:eq(0)").on("change", refreshWithFilter);
    $("[name=state]:eq(1)").on("change", refreshWithFilter);
    $("[name=state]:eq(2)").on("change", refreshWithFilter);
 
    // Event click handling
    
    function eventClickHandler(info) {
        currentRental = getRentalFromEventInfo(info);
//        console.log(currentRental);
        
        refreshDialogData(currentRental);
        
        $('#confirmDialog').dialog({
            resizable: false,
            height: 300,
            width: 500,
            modal: true,
            autoOpen: true,
            buttons: getButtons(),
            close: closeDialog
        });
    };
 
    // Event click handling helpers
 
    function getRentalFromEventInfo(info) {
        // needed to get the rental info
        let event = info.event;
        let resource = event.getResources()[0];
        
        let id = resource.id;
        let user = resource._resource.extendedProps.user;
        let book = resource._resource.extendedProps.book;
        let rentaldate = event.extendedProps.rentaldate;
        let returndate = event.extendedProps.returndate;
        
        return {id, user, book, rentaldate, returndate};
    }
    
    function refreshDialogData(rental) {
        $("#crUser").text(rental.user);
        $("#crBook").text(rental.book);
        
        let options = {year : 'numeric', month: '2-digit', day: '2-digit'};
        
        let rentaldate = new Date(rental.rentaldate);
        $("#crRentalDate").text(rentaldate.toLocaleDateString('fr-BE', options));

        if(rental.returndate === null) {
            $("#crReturnDate").text("not returned yet");
        } else {
            let returndate = new Date(rental.returndate);
            $("#crReturnDate").text(returndate.toLocaleDateString('fr-BE', options));
        }
    }
    
    // Dialog settings
    
    function getButtons() {
        let buttons = {};
        buttons.Close = function () {
            $(this).dialog("close");
        };
        if(canDelete()) {
            buttons.Delete = function () {
                dialogAction = "delete";
                $(this).dialog("close");
            };
        }
        if(currentRental.returndate == null) {
            buttons.Return = function () {
                dialogAction = "return";
                $(this).dialog("close");
            }
        }
        return buttons;
    }
    
    function closeDialog () {
        if(dialogAction === "return") {
            ajaxReturnRental(currentRental.id);
            dialogAction = null;
        } else if(dialogAction === "delete") {
            ajaxDeleteRental(currentRental.id);
            dialogAction = null;
        }
    }
    
    // AJAX dialog actions
    function ajaxReturnRental(rentalId) {
//        console.log("return rental #" +rentalId);
        $.post(baseHref + "rental/ajaxEncodeReturn", { id: rentalId })
        .done(function(data, textStatus, jqXHR) {
            console.log("ajaxReturnRental",
                        jqXHR.status, " ",
                        textStatus, " ",
                        jqXHR.statusText);
            calendar.refetchEvents();
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("ajaxReturnRental",
                          jqXHR.status, " ",
                          textStatus, " ",
                          jqXHR.statusText);
        });
    }
    
    function ajaxDeleteRental(rentalId) {
//        console.log("delete rental #" +rentalId);
    $.post(baseHref + "rental/ajaxRemove", { id: rentalId })
        .done(function(data, textStatus, jqXHR) {
            console.log("ajaxReturnRental",
                        jqXHR.status, " ",
                        textStatus, " ",
                        jqXHR.statusText);
            calendarRefetch();
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("ajaxReturnRental",
                          jqXHR.status, " ",
                          textStatus, " ",
                          jqXHR.statusText);
        });
    }
 
});