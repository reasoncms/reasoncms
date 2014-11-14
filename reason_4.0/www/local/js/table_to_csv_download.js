$(document).ready(function() {
    function exportTableToCsv($table, filename) {
        var $rows = $table.find('tr:has(td,th)'),

            // Temporary delimiter characters unlikely to be typed by keyboard
            // This is to avoid accidentally splitting the actual contents
            tmpColDelim = String.fromCharCode(11), // vertical tab character
            tmpRowDelim = String.fromCharCode(0), // null character

            // actual delimiter characters for CSV format
            colDelim = ',',
            rowDelim = '\r\n',

            // Grab text from table into CSV formatted string
            csv = $rows.map(function (i, row) {
                var $row = $(row),
                    $cols = $row.find('td,th');

                return $cols.map(function (j, col) {
                    var $col = $(col),
                        text = $col.text();
                    var t = text.replace('"', '""'); // escape double quotes
            var regex = t.replace(/\n/g, " ");
            return regex;
                }).get().join(tmpColDelim);

            }).get().join(tmpRowDelim)
                .split(tmpRowDelim).join(rowDelim)
                .split(tmpColDelim).join(colDelim) + ' ',

            // Data URI
            csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

       console.log(csv);
         $(this)
        .attr({
        'download': filename,
            'href': csvData,
            'target': '_blank'
    });
    }

    $(".table_to_csv").on('click', function () {
        exportTableToCsv.apply(this, [$('#'+table_id), file]);
    });
});