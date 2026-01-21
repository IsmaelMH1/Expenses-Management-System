<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$id = $input["id"] ?? null;

if (!$id) {
  json_response(["ok" => false, "error" => "ID is required"], 400);
}

// Unlink transactions first (preserve history, just remove link)
$stmtUnlink = $pdo->prepare("UPDATE transactions SET debt_id = NULL WHERE debt_id=? AND user_id=?");
$stmtUnlink->execute([$id, $user_id]);

// Delete the debt
$stmt = $pdo->prepare("DELETE FROM debts WHERE id=? AND user_id=?");
$stmt->execute([$id, $user_id]);

if ($stmt->rowCount() === 0) {
    // Check if it existed (could already be deleted, so strictly not an error, but good for feedback)
     json_response(["ok" => true, "message" => "Debt deleted or did not exist"]);
}

json_response(["ok" => true]);
