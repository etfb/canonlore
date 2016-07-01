// Event Calendar - table of years, multiple tbody elements
function MakeCalendar(id)
{    
    var $ = jQuery;

    var CalendarEarliest = $(id).attr('earliest-year');
    var CalendarLatest = $(id).attr('latest-year');
    var CalendarCurrent = CalendarLatest;
    
    var CalendarGoToYear = function (year) {
        if (year < CalendarEarliest) year = CalendarEarliest;
        if (year > CalendarLatest) year = CalendarLatest;
        CalendarCurrent = +year;
        
        $(id+' button.year').button('option','label',CalendarCurrent);
        
        $(id+' tbody').each(function () {
            var year = $(this).attr('year');
            $(this).toggle(year == CalendarCurrent);
        });
    };
    
    var CalendarPrevYear = function () { CalendarGoToYear(CalendarCurrent-1); };
    var CalendarThisYear = function () { CalendarGoToYear(CalendarLatest); };
    var CalendarNextYear = function () { CalendarGoToYear(CalendarCurrent+1); };
    
    $(id+' button.prev').button().click(CalendarPrevYear);
    $(id+' button.year').button().click(CalendarThisYear);
    $(id+' button.next').button().click(CalendarNextYear);
    
    CalendarThisYear();
}

jQuery(document).ready(function () {
    var $ = jQuery;
    
    var DoFixMe = function () {
        alert('Fix Me!');
    };
    
    $('#tabs').tabs();
    $('a.canon-fixme').click(DoFixMe);
    $('.accordion').accordion({collapsible: true, heightStyle: 'content'});
    
    MakeCalendar('#canon-calendar');
});