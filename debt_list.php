<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();

$stmt = $pdo->prepare("
  SELECT id, name, original_amount, remaining_amount, note, created_at
  FROM debts
  WHERE user_id=?
  ORDER BY created_at DESC
");
$stmt->execute([$user_id]);

json_response(["ok" => true, "debts" => $stmt->fetchAll(), "debug_user_id" => $user_id]);
