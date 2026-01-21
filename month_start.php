<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();

$input = json_decode(file_get_contents("php://input"), true) ?? [];

$month_key = $input["month_key"] ?? null;        // "2025-12"
$starting_money = $input["starting_money"] ?? null;

if (!$month_key || $starting_money === null) {
  json_response(["ok" => false, "error" => "month_key and starting_money are required"], 400);
}

if (!preg_match('/^\d{4}-\d{2}$/', $month_key)) {
  json_response(["ok" => false, "error" => "month_key must be YYYY-MM"], 400);
}

$starting_money = (float)$starting_money;
if ($starting_money < 0) {
  json_response(["ok" => false, "error" => "starting_money must be >= 0"], 400);
}

$stmt = $pdo->prepare("
  INSERT INTO months (user_id, month_key, starting_money)
  VALUES (?, ?, ?)
  ON DUPLICATE KEY UPDATE starting_money = VALUES(starting_money)
");
$stmt->execute([$user_id, $month_key, $starting_money]);

// Return the month row (id is useful later)
$stmt2 = $pdo->prepare("SELECT id, month_key, starting_money FROM months WHERE user_id=? AND month_key=?");
$stmt2->execute([$user_id, $month_key]);
$month = $stmt2->fetch();

json_response(["ok" => true, "month" => $month]);
