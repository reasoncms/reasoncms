$(document).ready(function() {
    $.ajax({
        url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=users',
        dataType: 'json',
        success: function(json){
            //alert(json.results[0].Email);
            for (var i = 0; i< json.results.length; i++) {
                var p = json.results[i];
                $('#account-select').append('<option value="'+ p.Patron_SK +'">'+ p.First_Name + ' ' + p.Last_Name + ' (' + p.Plan + ')' +'</option>');
            }
        },
        error: function(xhr, ajaxOptions, thrownError){
            alert('An error has occurred, please try again later.');
        }
    });
    $('#account-select').change( function() {
        if (this.value != '--') {
            $.ajax({
                url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=tender&patron='+this.value,
                dataType: 'json',
                success: function(json) {
                    $('#tender').html('');
                    for (var i = 0; i < json.results.length; i++) {
                        var t = json.results[i];
                        $('#tender').append('<tr><td>' + t.Tender + '</td><td>'+ t.Balance + '</td></tr>');
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert('An error has occurred, please try again later.');
                }
            });
            $.ajax({
                url: 'https://reasondev.luther.edu/reason/norsecard_connect.php?action=transactions&patron='+this.value,
                dataType: 'json',
                success: function(json) {
                    $('#transactions').html('');
                    $('#transactions').css({'width':''});
                    $('.pagination').remove();
                    $('#transactions').append('<tr><th>Transaction Time</th><th>Terminal</th><th>Function</th><th>Previous Balance</th><th>Transaction Amount</th><th>Resulting Balance</th><th>Tender</th></tr>');
                    for (var i = 0; i < json.results.length; i++) {
                        var t = json.results[i];
                        $('#transactions').append('<tr><td>' + t.Transaction_Time + '</td><td>'+ t.Terminal + '</td><td>' + t.transaction_function + '</td><td>' + t.Previous_Balance + '</td><td>' + t.Transaction_Amount + '</td><td>' + t.Resulting_Balance + '</td><td>' + t.Tender + '</td></tr>');
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
});
