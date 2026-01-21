<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();

$month_key = $_GET["month_key"] ?? null;
if (!$month_key || !preg_match('/^\d{4}-\d{2}$/', $month_key)) {
  json_response(["ok" => false, "error" => "month_key is required (YYYY-MM)"], 400);
}

// Get month info
$stmt = $pdo->prepare("SELECT id, month_key, starting_money FROM months WHERE user_id=? AND month_key=?");
$stmt->execute([$user_id, $month_key]);
$month = $stmt->fetch();

if (!$month) {
  json_response(["ok" => false, "error" => "Month not found"], 404);
}

$month_id = (int)$month["id"];
$starting = (float)$month["starting_money"];

// Totals by type
$stmt = $pdo->prepare("
  SELECT type, COALESCE(SUM(amount),0) AS total
  FROM transactions
  WHERE user_id=? AND month_id=?
  GROUP BY type
");
$stmt->execute([$user_id, $month_id]);

$totals = ["income" => 0.0, "expense" => 0.0, "debt_payment" => 0.0];
foreach ($stmt->fetchAll() as $row) {
  $totals[$row["type"]] = (float)$row["total"];
}

$income = $totals["income"];
$expense = $totals["expense"];
$debt = $totals["debt_payment"];

$remaining = $starting + $income - $expense - $debt;

// Category breakdown (expenses only)
$stmt = $pdo->prepare("
  SELECT category, COALESCE(SUM(amount),0) AS total
  FROM transactions
  WHERE user_id=? AND month_id=? AND type='expense'
  GROUP BY category
  ORDER BY total DESC
");
$stmt->execute([$user_id, $month_id]);
$by_category = $stmt->fetchAll();

// Daily totals (nice for charts later)
$stmt = $pdo->prepare("
  SELECT tx_date,
         SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS income,
         SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense,
         SUM(CASE WHEN type='debt_payment' THEN amount ELSE 0 END) AS debt_payment
  FROM transactions
  WHERE user_id=? AND month_id=?
  GROUP BY tx_date
  ORDER BY tx_date ASC
");
$stmt->execute([$user_id, $month_id]);
$daily = $stmt->fetchAll();

json_response([
  "ok" => true,
  "month" => [
    "id" => $month_id,
    "month_key" => $month_key,
    "starting_money" => $starting
  ],
  "totals" => [
    "income" => $income,
    "expense" => $expense,
    "debt_payment" => $debt,
    "remaining" => $remaining
  ],
  "breakdown" => [
    "expenses_by_category" => $by_category,
    "daily" => $daily
  ]
]);
