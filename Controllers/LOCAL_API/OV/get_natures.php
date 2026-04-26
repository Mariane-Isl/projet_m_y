<?php
// Headers required for JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include Database and NatureOv classes (adjust the paths according to your structure)
require_once '../../../Classes/Database.php';
require_once '../../../Classes/NatureOv.php';

// Instantiate database and connect
$database = new Database();
$db = $database->getConnection();

// Instantiate the NatureOv object
$natureOv = new NatureOv($db);

// Call readAll() from your class
$stmt = $natureOv->readAll();
$num = $stmt->rowCount();

// Check if any records are found
if ($num > 0) {
    // Initialize the response array exactly how your frontend expects it
    $response = [
        "success" => true,
        "data" => []
    ];

    // Fetch records
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extract row to use $id, $code, $label directly
        extract($row);

        $natureItem = [
            "id" => $id,
            "code" => $code,
            "label" => $label
        ];

        array_push($response["data"], $natureItem);
    }

    http_response_code(200);
    echo json_encode($response);
} else {
    // No records found
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => [],
        "message" => "Aucune nature trouvée."
    ]);
}
