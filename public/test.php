<!DOCTYPE html>
<html>
<head>
    <title>666</title>
</head>
<body>
<div></div>
</body>
</html>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script type="text/javascript">
    $.ajax({
        url: 'https://duty.lizhen123.cn/api/admin/login?username=1234&password=123&is_remember=1',
        type: 'POST',
        dataType: 'json',
        data: {param1: 'value1'},
    })
        .done(function() {
            console.log("success");
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            console.log("complete");
        });

</script>