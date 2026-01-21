<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
  json_response(["ok" => false, "error" => "Unauthorized"], 401);
}

$user_id = (int)$_SESSION["user_id"];

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$prompt = trim((string)($input["prompt"] ?? ""));
$month_key = trim((string)($input["month_key"] ?? ""));

if ($prompt === "") {
  json_response(["ok" => false, "error" => "prompt is required"], 400);
}

// fallback month_key = current month
if ($month_key === "") {
  $month_key = date("Y-m");
}

function money($n) {
  return number_format((float)$n, 2, '.', '');
}

function contains_any($text, $words) {
  $t = mb_strtolower($text);
  foreach ($words as $w) {
    if (mb_strpos($t, mb_strtolower($w)) !== false) return true;
  }
  return false;
}

function extract_number($text) {
  if (preg_match('/(-?\d+(\.\d+)?)/', $text, $m)) return (float)$m[1];
  return null;
}

function fetch_month_summary($pdo, $user_id, $month_key) {
  $stmt = $pdo->prepare("SELECT starting_money FROM months WHERE user_id=? AND month_key=? LIMIT 1");
  $stmt->execute([$user_id, $month_key]);
  $start = (float)($stmt->fetchColumn() ?? 0);

// month_key is "YYYY-MM"
$startDate = $month_key . "-01";
$endDate = date("Y-m-d", strtotime($startDate . " +1 month"));

$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS income,
    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS expense,
    COALESCE(SUM(CASE WHEN type='debt_payment' THEN amount ELSE 0 END),0) AS debt_payment
  FROM transactions
  WHERE user_id=?
    AND tx_date >= ?
    AND tx_date < ?
");
$stmt->execute([$user_id, $startDate, $endDate]);

  $row = $stmt->fetch() ?: ["income"=>0,"expense"=>0,"debt_payment"=>0];

  $income = (float)$row["income"];
  $expense = (float)$row["expense"];
  $debt_payment = (float)$row["debt_payment"];

  $remaining = $start + $income - $expense - $debt_payment;

  return [
    "month_key" => $month_key,
    "start" => $start,
    "income" => $income,
    "expense" => $expense,
    "debt_payment" => $debt_payment,
    "remaining" => $remaining
  ];
}


function fetch_debts($pdo, $user_id) {
  $stmt = $pdo->prepare("
    SELECT id, name, original_amount, remaining_amount, note
    FROM debts
    WHERE user_id=?
    ORDER BY remaining_amount DESC
  ");
  $stmt->execute([$user_id]);
  return $stmt->fetchAll() ?: [];
}

$summary = fetch_month_summary($pdo, $user_id, $month_key);
$debts = fetch_debts($pdo, $user_id);
$debt_count = count($debts);

// 0) How many debts
if (contains_any($prompt, ["how many debt", "how many debts", "number of debts", "count debts", "count of debts"])) {
  json_response([
    "ok" => true,
    "answer" => "✅ You have {$debt_count} debt(s) recorded."
  ]);
}

// 1) Debts list / payoff advice
if (contains_any($prompt, ["debt", "debts", "payoff", "pay off", "remaining"])) {
  if ($debt_count === 0) {
    json_response(["ok" => true, "answer" => "You have no debts recorded yet. Add a debt and I can help plan payoff."]);
  }

  $ans = "📌 You have {$debt_count} debt(s):\n";
  foreach ($debts as $d) {
    $ans .= "• {$d['name']} — original " . money($d["original_amount"]) . ", remaining " . money($d["remaining_amount"]) . "\n";
  }

  $ans .= "\n✅ Strategy (no interest rates stored):\n";
  $ans .= "• Snowball: pay smallest remaining first.\n";
  $ans .= "• Avalanche: pay highest interest first (needs interest rates).\n";

  json_response(["ok" => true, "answer" => $ans]);
}

// 2) Month summary / report / remaining
if (contains_any($prompt, ["summary", "report", "month", "balance", "overview", "totals", "remaining"])) {
  $ans =
    "📅 Month: {$summary['month_key']}\n".
    "• Starting: " . money($summary["start"]) . "\n".
    "• Income: " . money($summary["income"]) . "\n".
    "• Expenses: " . money($summary["expense"]) . "\n".
    "• Debt paid: " . money($summary["debt_payment"]) . "\n".
    "• Remaining: " . money($summary["remaining"]) . "\n";

  if ($debt_count > 0) {
    $ans .= "\n📌 Debts (top 5 by remaining):\n";
    $top = array_slice($debts, 0, 5);
    foreach ($top as $d) {
      $ans .= "• {$d['name']} — remaining " . money($d["remaining_amount"]) . "\n";
    }
  } else {
    $ans .= "\n📌 No debts recorded.\n";
  }

  json_response(["ok" => true, "answer" => $ans]);
}

// 3) Spend per day / per week (uses remaining)
if (contains_any($prompt, ["per day", "daily"])) {
  $days_left = (int)date("t") - (int)date("j");
  if ($days_left <= 0) $days_left = 1;

  $per_day = $summary["remaining"] / $days_left;

  $ans =
    "Based on your remaining balance for {$summary['month_key']} (" . money($summary["remaining"]) . "),\n".
    "you can spend about " . money($per_day) . " per day for the rest of the month (approx {$days_left} day(s) left).";

  json_response(["ok" => true, "answer" => $ans]);
}

if (contains_any($prompt, ["per week", "weekly"])) {
  $weeks = 4.0;
  $per_week = $summary["remaining"] / $weeks;

  $ans =
    "Based on your remaining balance for {$summary['month_key']} (" . money($summary["remaining"]) . "),\n".
    "you can spend about " . money($per_week) . " per week (rough 4-week estimate).";

  json_response(["ok" => true, "answer" => $ans]);
}

// 4) Savings projection: reduce/cut X per week/month
if (contains_any($prompt, ["save", "saving", "reduce", "cut"]) && contains_any($prompt, ["week", "weekly", "month", "monthly", "per week", "per month"])) {
  $x = extract_number($prompt);
  if ($x === null) {
    json_response(["ok" => true, "answer" => "Tell me the number you want to reduce by (example: “reduce 20 per week”)."]);
  }

  if (contains_any($prompt, ["week", "weekly", "per week"])) {
    $monthly_gain = $x * 4;
    $ans = "If you reduce spending by " . money($x) . " per week, you save about " . money($monthly_gain) . " per month (rough estimate).";
    json_response(["ok" => true, "answer" => $ans]);
  } else {
    $ans = "If you reduce spending by " . money($x) . " per month, you save exactly " . money($x) . " per month.";
    json_response(["ok" => true, "answer" => $ans]);
  }
}

// Default fallback
$ans =
  "I can help using your real data.\n\n".
  "Try asking:\n".
  "• \"How many debts do I have?\"\n".
  "• \"List my debts\"\n".
  "• \"Show my month summary\"\n".
  "• \"How much can I spend per day?\"\n".
  "• \"If I reduce 20 per week how much will I save?\"";

json_response(["ok" => true, "answer" => $ans]);
