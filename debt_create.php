<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$name = trim($input["name"] ?? "");
$original_amount = $input["original_amount"] ?? null;
$note = trim($input["note"] ?? "");

if ($name === "" || $original_amount === null) {
  json_response(["ok" => false, "error" => "name and original_amount are required"], 400);
}

$original_amount = (float)$original_amount;
if ($original_amount < 0) {
  json_response(["ok" => false, "error" => "original_amount must be >= 0"], 400);
}

try {
  $stmt = $pdo->prepare("
    INSERT INTO debts (user_id, name, original_amount, remaining_amount, note)
    VALUES (?, ?, ?, ?, ?)
  ");
  $stmt->execute([$user_id, $name, $original_amount, $original_amount, $note]);

  json_response(["ok" => true, "debt_id" => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
  json_response(["ok" => false, "error" => $e->getMessage()], 500);
}
