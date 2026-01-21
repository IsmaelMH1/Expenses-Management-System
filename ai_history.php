<?php
require_once __DIR__ . "/db.php";

// must be logged in
$user_id = (int)($_SESSION["user_id"] ?? 0);
if (!$user_id) {
  json_response(["ok" => false, "error" => "Not authenticated"], 401);
}

$month_key = trim((string)($_GET["month_key"] ?? ""));
if ($month_key === "") $month_key = date("Y-m");

$limit = (int)($_GET["limit"] ?? 30);
if ($limit < 5) $limit = 5;
if ($limit > 100) $limit = 100;

$stmt = $pdo->prepare("
  SELECT role, message, created_at
  FROM ai_messages
  WHERE user_id = ? AND month_key = ?
  ORDER BY id DESC
  LIMIT ?
");

$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $month_key, PDO::PARAM_STR);
$stmt->bindValue(3, $limit, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll();
$rows = array_reverse($rows);

json_response(["ok" => true, "messages" => $rows, "month_key" => $month_key]);

