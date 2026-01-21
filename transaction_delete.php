<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$tx_id = (int)($input["id"] ?? 0);
if ($tx_id <= 0) {
  json_response(["ok" => false, "error" => "id is required"], 400);
}

try {
  $pdo->beginTransaction();

  // Lock the transaction row
  $stmt = $pdo->prepare("
    SELECT id, type, amount, debt_id
    FROM transactions
    WHERE id=? AND user_id=?
    FOR UPDATE
  ");
  $stmt->execute([$tx_id, $user_id]);
  $tx = $stmt->fetch();

  if (!$tx) {
    $pdo->rollBack();
    json_response(["ok" => false, "error" => "Transaction not found"], 404);
  }

  $type = $tx["type"];
  $amount = (float)$tx["amount"];
  $debt_id = $tx["debt_id"] !== null ? (int)$tx["debt_id"] : null;

  // If it was a debt payment, add it back to remaining_amount
  if ($type === "debt_payment" && $debt_id !== null) {
    $stmtD = $pdo->prepare("UPDATE debts SET remaining_amount = remaining_amount + ? WHERE id=? AND user_id=?");
    $stmtD->execute([$amount, $debt_id, $user_id]);
  }

  // Delete transaction
  $stmtDel = $pdo->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
  $stmtDel->execute([$tx_id, $user_id]);

  $pdo->commit();
  json_response(["ok" => true]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(["ok" => false, "error" => "Delete failed"], 500);
}
