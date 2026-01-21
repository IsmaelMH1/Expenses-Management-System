<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$id = $input["id"] ?? null;
$name = trim($input["name"] ?? "");
$original_amount = $input["original_amount"] ?? null;
$note = trim($input["note"] ?? "");

// Optimization: Allow updating remaining_amount explicitly if needed, but usually calculated. 
// However, for corrections, user might want to edit it.
$remaining_amount = $input["remaining_amount"] ?? null;

if (!$id || $name === "") {
  json_response(["ok" => false, "error" => "ID and Name are required"], 400);
}

// Prepare update fields
$fields = [];
$params = [];

$fields[] = "name = ?";
$params[] = $name;

if ($original_amount !== null) {
  $fields[] = "original_amount = ?";
  $params[] = (float)$original_amount;
}
if ($remaining_amount !== null) {
  $fields[] = "remaining_amount = ?";
  $params[] = (float)$remaining_amount;
}
if (isset($input["note"])) {
    $fields[] = "note = ?";
    $params[] = $note;
}

// Always need ID and UserID at the end
$params[] = $id;
$params[] = $user_id;

$sql = "UPDATE debts SET " . implode(", ", $fields) . " WHERE id=? AND user_id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    // Check if it exists
    $check = $pdo->prepare("SELECT id FROM debts WHERE id=? AND user_id=?");
    $check->execute([$id, $user_id]);
    if (!$check->fetch()) {
        json_response(["ok" => false, "error" => "Debt not found"], 404);
    }
}

json_response(["ok" => true]);
