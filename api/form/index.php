<!doctype html>
<html>
<head>
    <title>API Form</title>
    <script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script type="text/javascript">
    function postJSON()
    {
        $.ajax({
            url: '../index.php',
            type: 'post',
            data: JSON.stringify({upc:$('#upc').val()}),
            dataType: 'json',
            contentType: 'application/json',
            success: function(res){
                $('#output-area').html(JSON.stringify(res));
            }
        });
    }
    </script>
</head>
<body>
<form onsubmit="postJSON(); return false;">
    <label>UPC</label>
    <input type="text" id="upc" />
    <button type="submit">Search</button>
</form>
<div id="output-area">
</div>
</body>
</html>
