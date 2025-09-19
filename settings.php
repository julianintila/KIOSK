<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk Settings</title>
    <link rel="stylesheet" href="./dist/style.css">
    <script src="./js/script.js"></script>
</head>

<body class="bg-black text-white font-sans text-3xl">
    <div class="h-screen flex flex-col container mx-auto max-w-4xl">
        <div class="h-[20%] flex items-center justify-center">
            <img src="images/logo/namelogo.png" alt="Main Logo" class="h-44 w-auto">
        </div>

        <div class="flex-1 flex flex-col items-center justify-center space-y-8">
            <h2 class="text-4xl font-medium">Kiosk Settings</h2>
            <div class="w-full max-w-lg">
                <div class="mb-6">
                    <label for="kioskRegNo" class="block text-2xl mb-2">Kiosk Registration Number:</label>
                    <input type="text" id="kioskRegNo"
                        class="w-full p-4 text-2xl bg-gray-700 border border-gray-600 rounded text-white"
                        placeholder="Enter Kiosk Reg No">
                </div>
                <div class="flex justify-center space-x-4">
                    <button id="saveBtn" class="border-2 border-white px-9 py-4 cursor-pointer">Save</button>
                    <button id="backBtn" class="border-2 border-white px-9 py-4 cursor-pointer">Back</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load existing KioskRegNo if any
        document.getElementById('kioskRegNo').value = getKioskRegNo();

        // Save button handler
        document.getElementById('saveBtn').addEventListener('click', () => {
            const kioskRegNo = document.getElementById('kioskRegNo').value.trim();
            if (!kioskRegNo) {
                alert('Please enter a valid Kiosk Registration Number');
                return;
            }
            setKioskRegNo(kioskRegNo);
            alert('Settings saved successfully!');
            window.location.href = 'index.php';
        });

        // Back button handler
        document.getElementById('backBtn').addEventListener('click', () => {
            window.location.href = 'index.php';
        });
    </script>
</body>

</html>