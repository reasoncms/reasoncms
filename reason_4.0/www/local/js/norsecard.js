$(document).ready(function() {
    $.ajax({
        url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=users',
        dataType: 'json',
        success: function(json){
            for (var i = 0; i< json.results.length; i++) {
                var p = json.results[i];
                $('#account-select').append('<option value="'+ p.Patron_SK +'">'+ p.First_Name + ' ' + p.Last_Name + ' (' + p.Plan + ')' +'</option>');
            }
        },
        error: function(xhr, ajaxOptions, thrownError){
            alert('An error has occurred, please try again later.');
        }
    });

    $(function() {
        $( "#from" ).datepicker({
          changeMonth: true,
          numberOfMonths: 1,
          onClose: function( selectedDate ) {
            $( "#to" ).datepicker( "option", "minDate", selectedDate );
          }
        });
        $( "#to" ).datepicker({
          changeMonth: true,
          numberOfMonths: 1,
          onClose: function( selectedDate ) {
            $( "#from" ).datepicker( "option", "maxDate", selectedDate );
          }
        });
    });

    var dt = new Date();

    dt.getFullYear() + "/" + dt.getMonth() + 1 + "/" + dt.getDate();

    $("#to").val(('0'+(dt.getMonth() + 1)).slice(-2) +'/'+ ('0'+dt.getDate()).slice(-2) + '/' + dt.getFullYear());
    $("#from").val(('0'+dt.getMonth()).slice(-2) + '/' + ('0'+dt.getDate()).slice(-2) + '/' + dt.getFullYear());

    Number.prototype.formatMoney = function(c, d, t){
    var n = this, 
        c = isNaN(c = Math.abs(c)) ? 2 : c, 
        d = d == undefined ? ",." : d, 
        t = t == undefined ? ".," : t, 
        s = n < 0 ? "-" : "", 
        i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
        j = (j = i.length) > 3 ? j % 3 : 0;
       return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    };

    $('#account-select').change( function() {
        if (this.value != '--') {
            var from = $.datepicker.formatDate('mm/dd/yy', $('#from').datepicker("getDate"));
            var to = $.datepicker.formatDate('mm/dd/yy', $('#to').datepicker("getDate"));
  
            $.ajax({
                url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=tender&patron='+this.value+'&startdate='+from+'&enddate='+to,
                dataType: 'json',
                success: function(json) {
                    $('#tender').html('');
                    for (var i = 0; i < json.results.length; i++) {
                        var t = json.results[i];
                        if (t.Tender == 'Charge') {
                            $('#tender').append('<tr><td>' + t.Tender + ' ('+from+' - '+to+')' + '</td><td>'+ t.Balance + '</td></tr>');
                        } else {
                            $('#tender').append('<tr><td>' + t.Tender + ' (Total remaining)' + '</td><td>'+ parseFloat(t.Balance,10).formatMoney(2,'.',',') + '</td></tr>');
                        }
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert('An error has occurred, please try again later.');
                    alert(thrownError);
                }
            });

            $.ajax({
                url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=transactions&patron='+this.value+'&startdate='+from+'&enddate='+to,
                dataType: 'json',
                success: function(json) {
                    $('#transactions').html('');
                    $('#transactions').css({'width':''});
                    $('.pagination').remove();
                    $('#transactions').append('<tr><th>Transaction Time</th><th>Terminal</th><th>Function</th><th>Transaction Amount</th><th>Tender</th></tr>');
                    for (var i = 0; i < json.results.length; i++) {
                        var t = json.results[i];
                        $('#transactions').append('<tr><td>' + t.Transaction_Time + '</td><td>'+ t.Terminal + '</td><td>' + t.transaction_function + '</td><td>' + parseFloat(t.Transaction_Amount, 10).formatMoney(2,'.',',') + '</td><td>' + t.Tender + '</td></tr>');
                    }
                    $('#transactions tr:odd').css('background-color', '#AFD0EF');
                    $('#transactions').after('<div class="pagination"></div>');
                    $('.pagination').append('<a href="#" class="first" data-action="first">&laquo;</a>');
                    $('.pagination').append('<a href="#" class="previous" data-action="previous">&lsaquo;</a>');
                    $('.pagination').append('<input type="text" readonly="readonly" data-max-page="40" />');
                    $('.pagination').append('<a href="#" class="next" data-action="next">&rsaquo;</a>');
                    $('.pagination').append('<a href="#" class="last" data-action="last">&raquo;</a>');
                    
                    // hide all but the first of our paragraphs
                    $('#transactions tr').filter(':gt(20)').hide();

                    $('.pagination').jqPagination({
                        max_page    : Math.ceil(($('#transactions tr').length)/20.0),
                        paged        : function(page) {
                            // hide all paragraphs
                            $('#transactions').hide();
                            $('#transactions tr').hide();
                            $('#transactions tr').filter(':first').show();
                            $('#transactions tr').slice(((page-1)*20)+1, (page*20)+1).show();
                            $('#transactions').fadeIn('slow');
                        }
                    });
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert('An error has occurred, please try again later.');
                }
            });
        } else {
            $('#transactions').html('');
            $('#tender').html('');
            $('.pagination').remove();
        }
    });
    $("#from").change( function() {
        $("#from").effect("highlight", {}, 1000);
        $('#account-select').change();
    });
    $("#to").change( function() {
        $("#to").effect("highlight", {}, 1000);
        $('#account-select').change();
    });
});