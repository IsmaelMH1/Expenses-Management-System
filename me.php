<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();

$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);

json_response(["ok" => true, "user" => $stmt->fetch()]);
