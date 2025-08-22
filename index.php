<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="./js/script.js"></script>
</head>

<body>

    <div>
        <img src="" alt="">
    </div>
    <div id="orderTypeModal" class="modal"
        style="align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background-color:black; background-size: cover; background-position: center; z-index: 9999; display: flex; flex-direction: column;">


        <img src="images/logo/namelogo.png" alt="Top Logo"
            style="position: absolute; top: 20px; left: 50%; transform: translateX(-50%); width: 150px; z-index: 10000;height:180px;width:180px;" />


        <button id="settingsBtn"
            style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: #fff; border: 2px solid #a7a0a0; border-radius: 50%; width: 50px; height: 50px; font-size: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 10001;">
            <img src="images/icons/settings.png" alt="Settings" style="width:35px; height:35px;" />
        </button>


        <img src="images/logo/logo.png" alt="Bowl"
            style="width: 650px; max-width: 80%; display: block; margin: 0 auto 30px auto;" />


        <div style="display: flex; justify-content: center; align-items: center; margin-top: 100px;">
            <button id="enterButton"
                style="background-color: transparent; color: white; border: 2px solid #a7a0a0;width:500px; font-size: 50px; padding: 30px 40px; border-radius: 5px; cursor: pointer; text-transform: capitalize; transition: all 0.3s ease;">
                Enter
            </button>
        </div>
    </div>

    <script>
        const enterButton = document.getElementById("enterButton");

        enterButton.addEventListener("click", function() {
            clearAll();

            fetch("api/generate_reference_no.php")
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error("Failed to generate reference number:", data.message);
                        return;
                    }
                    setReferenceNo(data.data.referenceNo)
                    window.location.href = "menu.php";
                })
                .catch(error => console.error("Error generating reference number:", error));
        });
    </script>
</body>

</html>