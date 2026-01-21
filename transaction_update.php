<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$tx_id = (int)($input["id"] ?? 0);
$tx_date = $input["tx_date"] ?? null;
$type = $input["type"] ?? null;
$category = trim($input["category"] ?? "");
$note = trim($input["note"] ?? "");
$amount = $input["amount"] ?? null;
$debt_id = $input["debt_id"] ?? null;

if ($tx_id <= 0 || !$tx_date || !$type || $category === "" || $amount === null) {
  json_response(["ok" => false, "error" => "id, tx_date, type, category, amount are required"], 400);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tx_date)) {
  json_response(["ok" => false, "error" => "tx_date must be YYYY-MM-DD"], 400);
}
$allowed = ["expense", "income", "debt_payment"];
if (!in_array($type, $allowed, true)) {
  json_response(["ok" => false, "error" => "Invalid type"], 400);
}

$amount = (float)$amount;
if ($amount <= 0) json_response(["ok" => false, "error" => "amount must be > 0"], 400);

try {
  $pdo->beginTransaction();

  // Lock current transaction
  $stmt = $pdo->prepare("
    SELECT id, type, amount, debt_id
    FROM transactions
    WHERE id=? AND user_id=?
    FOR UPDATE
  ");
  $stmt->execute([$tx_id, $user_id]);
  $old = $stmt->fetch();

  if (!$old) {
    $pdo->rollBack();
    json_response(["ok" => false, "error" => "Transaction not found"], 404);
  }

  $oldType = $old["type"];
  $oldAmount = (float)$old["amount"];
  $oldDebtId = $old["debt_id"] !== null ? (int)$old["debt_id"] : null;

  // 1) Rollback old debt effect
  if ($oldType === "debt_payment" && $oldDebtId !== null) {
    $stmtRB = $pdo->prepare("UPDATE debts SET remaining_amount = remaining_amount + ? WHERE id=? AND user_id=?");
    $stmtRB->execute([$oldAmount, $oldDebtId, $user_id]);
  }

  // 2) Validate new debt payment (after rollback)
  if ($type === "debt_payment") {
    if ($debt_id === null || (int)$debt_id <= 0) {
      $pdo->rollBack();
      json_response(["ok" => false, "error" => "debt_id is required when type is debt_payment"], 400);
    }
    $debt_id = (int)$debt_id;

    $stmtD = $pdo->prepare("SELECT remaining_amount FROM debts WHERE id=? AND user_id=? FOR UPDATE");
    $stmtD->execute([$debt_id, $user_id]);
    $d = $stmtD->fetch();
    if (!$d) {
      $pdo->rollBack();
      json_response(["ok" => false, "error" => "Debt not found"], 404);
    }

    $remainingDebt = (float)$d["remaining_amount"];
    if ($amount > $remainingDebt) {
      $pdo->rollBack();
      json_response(["ok" => false, "error" => "Payment exceeds remaining debt"], 400);
    }
  } else {
    $debt_id = null;
  }

  // 3) Update transaction row
  $stmtU = $pdo->prepare("
    UPDATE transactions
    SET tx_date=?, type=?, category=?, note=?, amount=?, debt_id=?
    WHERE id=? AND user_id=?
  ");
  $stmtU->execute([$tx_date, $type, $category, $note, $amount, $debt_id, $tx_id, $user_id]);

  // 4) Apply new debt effect
  if ($type === "debt_payment" && $debt_id !== null) {
    $stmtApply = $pdo->prepare("UPDATE debts SET remaining_amount = remaining_amount - ? WHERE id=? AND user_id=?");
    $stmtApply->execute([$amount, $debt_id, $user_id]);
  }

  $pdo->commit();
  json_response(["ok" => true]);
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  json_response(["ok" => false, "error" => "Update failed"], 500);
}
