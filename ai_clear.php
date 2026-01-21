<?php
require_once __DIR__ . "/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
if (!$user_id) {
  json_response(["ok" => false, "error" => "Not authenticated"], 401);
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$month_key = trim((string)($input["month_key"] ?? ""));
if ($month_key === "") $month_key = date("Y-m");

$stmt = $pdo->prepare("DELETE FROM ai_messages WHERE user_id=? AND month_key=?");
$stmt->execute([$user_id, $month_key]);

json_response(["ok" => true]);
