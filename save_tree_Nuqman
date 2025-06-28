<?php
// Set the response header to JSON format
header("Content-Type: application/json");

// Connect to MySQL database (host, username, password, database name)
$connect = mysqli_connect("localhost", "root", "", "treeage") or die("Connection Failed");

// Get form data sent via POST method and store them in variables
$tree_id = $_POST['Tree_ID'];
$common_name = $_POST['Common_Name'];
$scientific_name = $_POST['Scientific_Name'];
$growth_rate = $_POST['Growth_Rate_cm'];
$lat = $_POST['Latitude'];
$long = $_POST['Longitude'];
$circumference = $_POST['Circumference_cm'];
$dbh = $_POST['DBH_cm'];
$height = $_POST['Height_m'];
$wood_density = $_POST['Wood_Density'];
$agb = $_POST['AGB_kg'];
$carbon_stock = $_POST['Carbon_Stock_kg'];
$tree_age = $_POST['Tree_Age'];

// Prepare SQL INSERT query to store new tree data into `newtree` table
$query = "INSERT INTO newtree 
    (Tree_ID, Common_Name, Scientific_Name, Growth_Rate_cm, Tree_Age, Latitude, Longitude, Circumference_cm, DBH_cm, Height_m, Wood_Density, AGB_kg, Carbon_Stock_kg)
    VALUES 
    ('$tree_id', '$common_name', '$scientific_name', '$growth_rate', '$tree_age', '$lat', '$long', '$circumference', '$dbh', '$height', '$wood_density', '$agb', '$carbon_stock')";

// If query successfully executed
if (mysqli_query($connect, $query)) {

    // Fetch all rows from the database to regenerate the entire GeoJSON file
    $result = mysqli_query($connect, "SELECT * FROM newtree");
    $features = [];

    // Loop through each row and structure as GeoJSON features
    while ($row = mysqli_fetch_assoc($result)) {
        $features[] = [
            "type" => "Feature",
            "properties" => [
                "Tree_ID" => $row['Tree_ID'],
                "Common_Name" => $row['Common_Name'],
                "Scientific_Name" => $row['Scientific_Name'],
                "Growth_Rate_cm" => $row['Growth_Rate_cm'],
                "Tree_Age" => $row['Tree_Age'],
                "Circumference_cm" => $row['Circumference_cm'],
                "DBH_cm" => $row['DBH_cm'],
                "Height_m" => $row['Height_m'],
                "Wood_Density" => $row['Wood_Density'],
                "AGB_kg" => $row['AGB_kg'],
                "Carbon_Stock_kg" => $row['Carbon_Stock_kg']
            ],
            "geometry" => [
                "type" => "Point",
                "coordinates" => [
                    floatval($row['Longitude']),  // Ensure longitude is a float
                    floatval($row['Latitude'])    // Ensure latitude is a float
                ]
            ]
        ];
    }

    // Construct the final GeoJSON FeatureCollection structure
    $geojson = [
        "type" => "FeatureCollection",
        "features" => $features
    ];

    // Write the newly constructed GeoJSON data to a file
    file_put_contents("tree_carbon_inventory2.geojson", json_encode($geojson, JSON_PRETTY_PRINT));

    // Return the newly inserted data back to the client as JSON
    echo json_encode([
        "Tree_ID" => $tree_id,
        "Common_Name" => $common_name,
        "Scientific_Name" => $scientific_name,
        "Growth_Rate_cm" => $growth_rate,
        "Tree_Age" => $tree_age,
        "Latitude" => $lat,
        "Longitude" => $long,
        "Circumference_cm" => $circumference,
        "DBH_cm" => $dbh,
        "Height_m" => $height,
        "Wood_Density" => $wood_density,
        "AGB_kg" => $agb,
        "Carbon_Stock_kg" => $carbon_stock
    ]);

} else {
    // If insertion failed, return HTTP 500 error and a JSON error message
    http_response_code(500);
    echo json_encode(["error" => "❌ Failed to insert tree data into database."]);
}
?>
