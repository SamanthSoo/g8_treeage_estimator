<?php
// Start the session to store messages
session_start();

// Connect to MySQL database
$connect = mysqli_connect("localhost", "root", "", "treeage") or die("Connection Failed");

// Function to determine tree age category
function get_age_category($age) {
    if ($age < 10) return 'Young';
    elseif ($age < 50) return 'Mature';
    else return 'Legacy';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form inputs
    $tree_id = $_POST['Tree_ID'];
    $common = $_POST['Common_Name'];
    $scientific = $_POST['Scientific_Name'];
    $rate = $_POST['Growth_Rate_cm'];
    $lat = $_POST['Latitude'];
    $lon = $_POST['Longitude'];
    $circ = $_POST['Circumference_cm'];
    $dbh = $_POST['DBH_cm'];
    $height = $_POST['Height_m'];
    $density = $_POST['Wood_Density'];
    $agb = $_POST['AGB_kg'];
    $carbon = $_POST['Carbon_Stock_kg'];

    // Calculate tree age
    $age = round($dbh * $rate, 1);
    $category = get_age_category($age);

    // Insert data into database
    $sql = "INSERT INTO newtree 
            (Tree_ID, Common_Name, Scientific_Name, Growth_Rate_cm, Tree_Age, Latitude, Longitude, 
            Circumference_cm, DBH_cm, Height_m, Wood_Density, AGB_kg, Carbon_Stock_kg) 
            VALUES 
            ('$tree_id', '$common', '$scientific', '$rate', '$age', '$lat', '$lon', 
            '$circ', '$dbh', '$height', '$density', '$agb', '$carbon')";

    // Set session message based on success
    if (mysqli_query($connect, $sql)) {
        $_SESSION['message'] = "✅ Tree saved successfully. Estimated Age: <strong>$age years</strong> ($category)";
    } else {
        $_SESSION['message'] = "❌ Failed to save tree.";
    }

    // Redirect to the same page after submission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tree Age Estimator + Map</title>

    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Chart.js for pie chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Leaflet Draw CSS and JS for drawing tools -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <style>
        /* --- Styling section --- */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: url('geotrees.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .title {
            text-align: center;
            font-size: 48px;
            font-weight: bold;
            color: black;
            margin: 30px 0;
        }

        .map-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto 30px;
            flex-wrap: wrap;
        }

        #map {
            height: 580px;
            width: 100%;
            max-width: 900px;
            border-radius: 12px;
            border: 2px solid #ccc;
        }

        #chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            max-width: 400px;
        }

        .form-toggle {
            text-align: center;
            margin-bottom: 10px;
        }

        .form-toggle button {
            background-color: #2e7d32;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .form-toggle button:hover {
            background-color: #1e5822;
        }

        .form-container {
            display: none;
            max-width: 520px;
            margin: 20px auto 60px;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        h3 {
            text-align: center;
            color: #2e7d32;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 15px;
            color: #2e7d32;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #388e3c;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 25px;
        }

        input[type="submit"]:hover {
            background-color: #2e7d32;
        }

        .legend {
            background: white;
            padding: 10px;
            line-height: 1.5em;
            color: #333;
            border-radius: 6px;
            font-size: 14px;
        }

        #response {
            font-weight: bold;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Main Title -->
<div class="title">🌳 Tree Age Monitoring Map</div>

<!-- Map and Chart Container -->
<div class="map-container">
    <div id="map"></div>
    <div id="chart-container">
        <h3 style="text-align:center;">Tree Age Distribution</h3>
        <p id="totalCount" style="text-align:center; font-weight:bold;">Total: 0 trees</p>
        <canvas id="agePieChart" width="350" height="350"></canvas>

        <!-- Search and filter buttons -->
        <div class="filters" style="text-align:center; margin-top: 10px;">
            <div class="search-tree-id" style="text-align:center; margin-top:15px;">
                <input type="text" id="searchInput" placeholder="Enter Tree ID" style="padding:6px; width:70%; border-radius:6px; border:1px solid #ccc;">
                <button onclick="searchTreeByID()" style="padding:6px 12px; background:#2e7d32; color:white; border:none; border-radius:6px;">Search</button>
                <p id="searchResult" style="font-weight:bold; margin-top:6px;"></p>
            </div>
            <button onclick="filterMarkers('all')" style="background:gray; color:white; padding:6px 12px; margin:4px; border:none; border-radius:6px;">All</button>
            <button onclick="filterMarkers('yellow')" style="background:yellow; padding:6px 12px; margin:4px; border:none; border-radius:6px;">Young</button>
            <button onclick="filterMarkers('green')" style="background:green; color:white; padding:6px 12px; margin:4px; border:none; border-radius:6px;">Mature</button>
            <button onclick="filterMarkers('red')" style="background:red; color:white; padding:6px 12px; margin:4px; border:none; border-radius:6px;">Legacy</button>
        </div>
    </div>
</div>

<!-- Buttons to show forms -->
<div class="form-toggle">
    <button onclick="document.querySelector('.form-container').style.display='block'">Insert New Tree</button>
    <button onclick="document.querySelector('.calc-container').style.display='block'">Tree Age Calculator</button>
</div>

<!-- Insert New Tree Form -->
<div class="form-container">
    <h3>Insert New Tree</h3>
    <form id="treeForm">
        <!-- All required fields -->
        <label>Tree ID:</label>
        <input type="text" name="Tree_ID" required>
        <label>Common Name:</label>
        <input type="text" name="Common_Name" required>
        <label>Scientific Name:</label>
        <input type="text" name="Scientific_Name" required>
        <label>Growth Rate (cm/year):</label>
        <input type="number" step="any" name="Growth_Rate_cm" required>
        <label>Latitude:</label>
        <input type="text" name="Latitude" required>
        <label>Longitude:</label>
        <input type="text" name="Longitude" required>
        <button type="button" onclick="getCurrentLocation()" style="margin-top:10px;">📍 Use My Current Location</button>
        <label>Circumference (cm):</label>
        <input type="number" step="any" name="Circumference_cm" required>
        <label>DBH (cm):</label>
        <input type="number" step="any" name="DBH_cm" required>
        <label>Height (m):</label>
        <input type="number" step="any" name="Height_m" required>
        <label>Wood Density:</label>
        <input type="number" step="any" name="Wood_Density" required>
        <label>AGB (kg):</label>
        <input type="number" step="any" name="AGB_kg" required>
        <label>Carbon Stock (kg):</label>
        <input type="number" step="any" name="Carbon_Stock_kg" required>
        <input type="submit" value="Estimate & Save">
    </form>
    <div id="response"></div>
</div>

<!-- Age Calculator Section -->
<div class="form-container calc-container" style="display:none;">
    <h3>Tree Age Calculator</h3>
    <form id="ageCalcForm" onsubmit="return false;">
        <label>Growth Factor (cm/year):</label>
        <input type="number" step="any" id="calcGrowth" required>

        <label>DBH (cm):</label>
        <input type="number" step="any" id="calcDBH" required>

        <input type="button" value="Estimate Tree Age" onclick="estimateTreeAge()" style="margin-top: 25px;">
    </form>
    <div id="calcResult" style="text-align:center; font-weight:bold; margin-top:15px;"></div>
</div>


<script>
// Initialize the Leaflet map centered at a specific location with zoom level 16
const map = L.map('map').setView([1.55, 103.64], 16);

// Declare variables to hold the GeoJSON layer, chart instance, marker array, and age category counts
let geojsonLayer;
let ageChart;
let allMarkers = [];
let ageCounts = { young: 0, mature: 0, legacy: 0 };

// Add OpenStreetMap tiles to the base map
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Create a Leaflet FeatureGroup to store user-drawn items
const drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

// Set up Leaflet.draw control (drawing tools on the map)
const drawControl = new L.Control.Draw({
    edit: {
        featureGroup: drawnItems, // Enable editing/removing of drawn shapes
        remove: true
    },
    draw: {
        polygon: true,
        polyline: true,
        rectangle: true,
        circle: true,
        marker: true
    }
});
map.addControl(drawControl);

// When a shape is drawn, add it to the map and optionally show area or radius
map.on(L.Draw.Event.CREATED, function (e) {
    const layer = e.layer;
    drawnItems.addLayer(layer);

    // Display radius for circles
    if (layer instanceof L.Circle) {
        layer.bindPopup(`Radius: ${(layer.getRadius() / 1000).toFixed(2)} km`).openPopup();
    } 
    // Display area for polygons
    else if (layer instanceof L.Polygon) {
        const area = L.GeometryUtil.geodesicArea(layer.getLatLngs()[0]) / 10000; // Convert m² to hectares
        layer.bindPopup(`Area: ${area.toFixed(2)} ha`).openPopup();
    }
});

// Assign a color to the tree marker based on its age
function getColor(age) {
    if (age < 10) return "yellow";
    else if (age < 50) return "green";
    return "red";
}

// Add a new tree marker to the map and open its popup
function addMarker(tree) {
    const lat = parseFloat(tree.Latitude);
    const lng = parseFloat(tree.Longitude);
    const color = getColor(parseFloat(tree.Tree_Age));

    // Prepare the HTML popup content
    const popup = `
        <strong>📍 Tree Details</strong><br>
        <b>Tree ID:</b> ${tree.Tree_ID}<br>
        <b>Name:</b> ${tree.Common_Name}<br>
        <b>Scientific Name:</b> ${tree.Scientific_Name}<br>
        <b>Growth Rate:</b> ${tree.Growth_Rate_cm} cm/year<br>
        <b>Tree Age:</b> ${tree.Tree_Age} years<br>
        <b>Latitude:</b> ${tree.Latitude}<br>
        <b>Longitude:</b> ${tree.Longitude}<br>
        <b>Circumference:</b> ${tree.Circumference_cm} cm<br>
        <b>DBH:</b> ${tree.DBH_cm} cm<br>
        <b>Height:</b> ${tree.Height_m} m<br>
        <b>Wood Density:</b> ${tree.Wood_Density}<br>
        <b>AGB:</b> ${tree.AGB_kg}<br>
        <b>Carbon Stock:</b> ${tree.Carbon_Stock_kg} kg
    `;

    // Create and display the circle marker
    const marker = L.circleMarker([lat, lng], {
        radius: 6,
        fillColor: color,
        color: "#fff",
        weight: 1,
        fillOpacity: 0.9
    }).addTo(map).bindPopup(popup).openPopup();

    marker.ageColor = color; // Save color for filtering
    allMarkers.push(marker); // Add to marker list
    map.setView([lat, lng], 18); // Zoom to marker
}

// Update the pie chart that shows tree age distribution
function updateChart(young, mature, legacy) {
    const total = young + mature + legacy;
    const data = [young, mature, legacy];
    const labels = ['Young (<10)', 'Mature (10–49)', 'Legacy (50+)'];

    // Create chart if not exist, otherwise update it
    if (!ageChart) {
        ageChart = new Chart(document.getElementById('agePieChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['yellow', 'green', 'red']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                let count = ctx.raw;
                                let pct = ((count / total) * 100).toFixed(1);
                                return `${ctx.label}: ${count} trees (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        ageChart.data.datasets[0].data = data;
        ageChart.update();
    }

    // Update total count text
    document.getElementById("totalCount").innerText = `Total: ${total} trees`;
}

// Filter tree markers by age color
function filterMarkers(color) {
    allMarkers.forEach(marker => {
        if (color === 'all' || marker.ageColor === color) {
            map.addLayer(marker);
        } else {
            map.removeLayer(marker);
        }
    });
}

// Search for a tree on the map by Tree ID
function searchTreeByID() {
    const id = document.getElementById("searchInput").value.trim();
    const resultDisplay = document.getElementById("searchResult");

    if (!id) {
        resultDisplay.innerText = "⚠️ Please enter a Tree ID.";
        return;
    }

    let found = false;
    allMarkers.forEach(marker => {
        const props = marker.feature?.properties;
        if (props && props.Tree_ID === id) {
            map.setView(marker.getLatLng(), 18);
            marker.openPopup();
            resultDisplay.innerText = `✅ Tree ID "${id}" found.`;
            found = true;
        }
    });

    if (!found) {
        resultDisplay.innerText = `❌ Tree ID "${id}" not found.`;
    }
}

// Load tree data from GeoJSON and add to map
function loadGeoJSON() {
    fetch('tree_carbon_inventory2.geojson')
        .then(res => res.json())
        .then(data => {
            // Clear previous layer and marker list
            if (geojsonLayer) map.removeLayer(geojsonLayer);
            allMarkers = [];
            ageCounts = { young: 0, mature: 0, legacy: 0 };

            // Load GeoJSON data as Leaflet markers
            geojsonLayer = L.geoJSON(data, {
                pointToLayer: (feature, latlng) => {
                    const age = parseFloat(feature.properties["Tree_Age"]);
                    const color = getColor(age);
                    const marker = L.circleMarker(latlng, {
                        radius: 6,
                        fillColor: color,
                        color: "#fff",
                        weight: 1,
                        fillOpacity: 0.9
                    });
                    marker.ageColor = color;
                    marker.feature = feature;
                    allMarkers.push(marker);
                    return marker;
                },
                onEachFeature: (feature, layer) => {
                    const p = feature.properties;
                    const popup = `
                        <strong>📍 Tree Details</strong><br>
                        <b>Tree ID:</b> ${p["Tree_ID"]}<br>
                        <b>Name:</b> ${p["Common_Name"]}<br>
                        <b>Scientific Name:</b> ${p["Scientific_Name"]}<br>
                        <b>Growth Rate:</b> ${p["Growth_Rate_cm"]} cm/year<br>
                        <b>Tree Age:</b> ${p["Tree_Age"]} years<br>
                        <b>Latitude:</b> ${p["Latitude"]}<br>
                        <b>Longitude:</b> ${p["Longitude"]}<br>
                        <b>Circumference:</b> ${p["Circumference_cm"]} cm<br>
                        <b>DBH:</b> ${p["DBH_cm"]} cm<br>
                        <b>Height:</b> ${p["Height_m"]} m<br>
                        <b>Wood Density:</b> ${p["Wood_Density"]}<br>
                        <b>AGB:</b> ${p["AGB_kg"]}<br>
                        <b>Carbon Stock:</b> ${p["Carbon_Stock_kg"]} kg
                    `;
                    layer.bindPopup(popup);
                }
            }).addTo(map);

            // Zoom to fit all markers
            map.fitBounds(geojsonLayer.getBounds());

            // Count tree ages
            data.features.forEach(f => {
                const age = parseFloat(f.properties.Tree_Age);
                if (age < 10) ageCounts.young++;
                else if (age < 50) ageCounts.mature++;
                else ageCounts.legacy++;
            });

            updateChart(ageCounts.young, ageCounts.mature, ageCounts.legacy);
        });
}

// Load data on initial page load
loadGeoJSON();

// Handle form submission for new tree entry
document.getElementById("treeForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const form = new FormData(this);
    const dbh = parseFloat(form.get("DBH_cm"));
    const rate = parseFloat(form.get("Growth_Rate_cm"));
    const age = dbh * rate;
    form.append("Tree_Age", age);

    // Send data to PHP backend via POST
    fetch("save_tree.php", {
        method: "POST",
        body: form
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("response").innerText = "✅ Tree inserted!";
        addMarker(data); // Add new marker to map

        // Update chart count
        const ageVal = parseFloat(data.Tree_Age);
        if (ageVal < 10) ageCounts.young++;
        else if (ageVal < 50) ageCounts.mature++;
        else ageCounts.legacy++;

        updateChart(ageCounts.young, ageCounts.mature, ageCounts.legacy);
    })
    .catch(() => {
        document.getElementById("response").innerText = "❌ Failed to insert tree.";
    });
});

// Add popup showing tree count in drawn areas
map.on(L.Draw.Event.CREATED, function (event) {
    const layer = event.layer;
    drawnItems.addLayer(layer);

    let counts = { young: 0, mature: 0, legacy: 0 };

    // Count markers inside the shape
    allMarkers.forEach(marker => {
        const latlng = marker.getLatLng();
        const age = marker.feature?.properties?.Tree_Age || 0;

        // Circle selection
        if (layer instanceof L.Circle) {
            if (layer.getLatLng().distanceTo(latlng) <= layer.getRadius()) {
                if (age < 10) counts.young++;
                else if (age < 50) counts.mature++;
                else counts.legacy++;
            }
        }

        // Polygon or rectangle selection
        if (layer instanceof L.Polygon || layer instanceof L.Rectangle) {
            if (leafletPip.pointInLayer(latlng, layer).length > 0) {
                if (age < 10) counts.young++;
                else if (age < 50) counts.mature++;
                else counts.legacy++;
            }
        }
    });

    const radius = layer.getRadius?.().toFixed(2);
    const total = counts.young + counts.mature + counts.legacy;

    // Popup for count results
    const popupContent = `
        <b>🌲 Trees in area:</b><br>
        📏 Radius: ${radius} meters<br>
        Total: ${total} trees<br>
        🟡 Young: ${counts.young}<br>
        🟢 Mature: ${counts.mature}<br>
        🔴 Legacy: ${counts.legacy}
    `;

    layer.bindPopup(popupContent).openPopup();
});

// Add legend for tree age categories
const legend = L.control({ position: "bottomright" });
legend.onAdd = function () {
    const div = L.DomUtil.create("div", "legend");
    div.innerHTML = `<strong>Age Category</strong><br>
        <i style="background:yellow;width:10px;height:10px;display:inline-block;"></i> Young<br>
        <i style="background:green;width:10px;height:10px;display:inline-block;"></i> Mature<br>
        <i style="background:red;width:10px;height:10px;display:inline-block;"></i> Legacy`;
    return div;
};
legend.addTo(map);

// Estimate age based on growth rate and DBH (in calculator tool)
function estimateTreeAge() {
    const growth = parseFloat(document.getElementById("calcGrowth").value);
    const dbh = parseFloat(document.getElementById("calcDBH").value);
    const resultBox = document.getElementById("calcResult");

    if (isNaN(growth) || isNaN(dbh) || growth <= 0 || dbh <= 0) {
        resultBox.innerText = "❌ Please enter valid numbers.";
        return;
    }

    const finalAge = (dbh * growth).toFixed(1);
    let current = 0;
    const duration = 800;
    const increment = Math.max(0.1, finalAge / (duration / 20));

    resultBox.innerText = "✅ Estimated Tree Age: 0 years";

    const counter = setInterval(() => {
        current += increment;
        if (current >= finalAge) {
            clearInterval(counter);
            resultBox.innerText = `✅ Estimated Tree Age: ${finalAge} years`;
        } else {
            resultBox.innerText = `✅ Estimated Tree Age: ${current.toFixed(1)} years`;
        }
    }, 20);
}

// Autofill latitude and longitude using browser geolocation
function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            document.querySelector('[name="Latitude"]').value = lat;
            document.querySelector('[name="Longitude"]').value = lng;
            showToast("✅ Location added successfully!");
        },
        () => {
            alert("Unable to retrieve your location.");
        }
    );
}
</script>
</body>
</html>









