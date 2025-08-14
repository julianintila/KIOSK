<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <button id="enterButton">Enter</button>

    <script>
        document.getElementById("enterButton").addEventListener("click", function() {
            window.location.href = "menu.php";
        });
    </script>
</body>
</html>