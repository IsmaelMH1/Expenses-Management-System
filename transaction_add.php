<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();
$input = json_decode(file_get_contents("php://input"), true) ?? [];

$month_key = $input["month_key"] ?? null;     // "2025-12"
$tx_date   = $input["tx_date"] ?? null;       // "2025-12-16"
$type      = $input["type"] ?? null;          // expense | income | debt_payment
$category  = trim($input["category"] ?? "");
$note      = trim($input["note"] ?? "");
$amount    = $input["amount"] ?? null;
$debt_id   = $input["debt_id"] ?? null;       // required if type=debt_payment

if (!$month_key || !$tx_date || !$type || $category === "" || $amount === null) {
  json_response(["ok" => false, "error" => "month_key, tx_date, type, category, amount are required"], 400);
}

if (!preg_match('/^\d{4}-\d{2}$/', $month_key)) {
  json_response(["ok" => false, "error" => "month_key must be YYYY-MM"], 400);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tx_date)) {
  json_response(["ok" => false, "error" => "tx_date must be YYYY-MM-DD"], 400);
}

$allowed = ["expense", "income", "debt_payment"];
if (!in_array($type, $allowed, true)) {
  json_response(["ok" => false, "error" => "Invalid type"], 400);
}

$amount = (float)$amount;
if ($amount <= 0) {
  json_response(["ok" => false, "error" => "amount must be > 0"], 400);
}

// If debt payment, validate debt
if ($type === "debt_payment") {
  if ($debt_id === null) {
    json_response(["ok" => false, "error" => "debt_id is required when type is debt_payment"], 400);
  }
  $debt_id = (int)$debt_id;

  $stmtD = $pdo->prepare("SELECT id, remaining_amount FROM debts WHERE id=? AND user_id=?");
  $stmtD->execute([$debt_id, $user_id]);
  $debtRow = $stmtD->fetch();

  if (!$debtRow) {
    json_response(["ok" => false, "error" => "Debt not found"], 404);
  }

  $remainingDebt = (float)$debtRow["remaining_amount"];
  if ($amount > $remainingDebt) {
    json_response(["ok" => false, "error" => "Payment exceeds remaining debt"], 400);
  }
} else {
  $debt_id = null;
}

// Find or Create month
$stmt = $pdo->prepare("SELECT id FROM months WHERE user_id=? AND month_key=?");
$stmt->execute([$user_id, $month_key]);
$month = $stmt->fetch();

if (!$month) {
  // Auto-create month with 0 starting money
  $stmtCreate = $pdo->prepare("
    INSERT INTO months (user_id, month_key, starting_money)
    VALUES (?, ?, 0)
  ");
  $stmtCreate->execute([$user_id, $month_key]);
  $month_id = (int)$pdo->lastInsertId();
} else {
  $month_id = (int)$month["id"];
}

// Insert transaction
$stmt2 = $pdo->prepare("
  INSERT INTO transactions (user_id, month_id, debt_id, tx_date, type, category, note, amount)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt2->execute([$user_id, $month_id, $debt_id, $tx_date, $type, $category, $note, $amount]);

// Update debt remaining if needed
if ($type === "debt_payment" && $debt_id !== null) {
  $stmtU = $pdo->prepare("
    UPDATE debts
    SET remaining_amount = remaining_amount - ?
    WHERE id = ? AND user_id = ?
  ");
  $stmtU->execute([$amount, $debt_id, $user_id]);
}

json_response(["ok" => true, "transaction_id" => (int)$pdo->lastInsertId()]);
