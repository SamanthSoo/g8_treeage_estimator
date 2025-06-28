<?php
$connect = mysqli_connect("localhost", "root", "", "lab") or die("Connection Failed");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tid = $_POST['tid'];
    $NmPkk = $_POST['NmPkk'];
    $lat = $_POST['lat'];
    $long = $_POST['long'];
    $type = $_POST['type'];
    $health = $_POST['health'];

    $query = "INSERT INTO tree(tid, NmPkk, lat, `long`, type, health) 
              VALUES('$tid', '$NmPkk', '$lat', '$long', '$type', '$health')";

    if (mysqli_query($connect, $query)) {
        $message = "<p class='success'> Record Inserted Successfully</p>";
    } else {
        $message = "<p class='error'> Record Not Inserted</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tree Inventory Form</title>
    <style>
        body {
            /* set background image */
            background-image: url('tree.jpg');
            background-size: cover;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            /* Styling the form box*/
            width: 400px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        h2 {
            /*Title styling */
            text-align: center;
            color: #2c5f2d;
        }

        label {
            /* Label styling for inputs */
            font-weight: bold;
        }

        input[type="text"],
        select {
            /* Styling textbox and dropdown */
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="radio"] {
            margin-right: 5px;
        }

        .radio-group {
            /* Group radio button together */
            margin-bottom: 15px;
        }

        input[type="submit"] {
            /*Submit button style */
            background-color: #2c5f2d;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #3b7a3a;
        }

        .success {
            color: green;
            text-align: center;
            font-weight: bold;
        }

        .error {
            color: red;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
     <!-- Page title -->
    <h2>🌳 Tree Inventory Form</h2>
    
    <?php if (isset($message)) echo $message; ?>

    <!-- Form to submit tree data -->
    <form method="POST" action="">

        <!-- Tree ID input -->
        <label>Tree ID:</label>
        <input type="text" name="tid" required>

         <!-- Tree name input -->
        <label>Tree Name (NmPkk):</label>
        <input type="text" name="NmPkk" required>

        <!-- Latitude input -->
        <label>Latitude:</label>
        <input type="text" name="lat" required>

        <!-- Longitude input -->
        <label>Longitude:</label>
        <input type="text" name="long" required>

         <!-- Dropdown list for tree type -->
        <label>Tree Type:</label>
        <select name="type" required>
            <option value="">--Select--</option>
            <option value="Fruit">Fruit</option>
            <option value="Ornamental">Ornamental</option>
            <option value="Shade">Shade</option>
            <option value="Timber">Timber</option>
        </select>

         <!-- Radio buttons for health condition -->
        <label>Health Condition:</label>
        <div class="radio-group">
            <input type="radio" name="health" value="Healthy" required> Healthy
            <input type="radio" name="health" value="Moderate"> Moderate
            <input type="radio" name="health" value="Unhealthy"> Unhealthy
        </div>

        <!-- Submit button -->
        <input type="submit" value="Insert Tree Data">
    </form>
</div>

</body>
</html>
