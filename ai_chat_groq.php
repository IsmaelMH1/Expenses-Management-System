<?php
require_once __DIR__ . "/db.php";     // has $pdo + session + json_response
require_once __DIR__ . "/config.php"; // where GROQ_API_KEY is defined

// must be logged in
$user_id = (int)($_SESSION["user_id"] ?? 0);
if (!$user_id) {
  json_response(["ok" => false, "error" => "Not authenticated"], 401);
}

$input = json_decode(file_get_contents("php://input"), true) ?? [];

$message = trim((string)($input["message"] ?? ""));
$month_key = trim((string)($input["month_key"] ?? ""));
if ($month_key === "") $month_key = date("Y-m");

if ($message === "") {
  json_response(["ok" => false, "error" => "message is required"], 400);
}

// Save USER message (NOW month_key exists ✅)
$stmt = $pdo->prepare("INSERT INTO ai_messages (user_id, month_key, role, message) VALUES (?,?,?,?)");
$stmt->execute([$user_id, $month_key, "user", $message]);


if ($message === "") {
  json_response(["ok" => false, "error" => "message is required"], 400);
}

// ---- Groq key ----
$GROQ_KEY = defined("GROQ_API_KEY") ? GROQ_API_KEY : "";
if (!$GROQ_KEY) {
  json_response(["ok" => false, "error" => "Missing GROQ_API_KEY in api/config.php"], 500);
}

// ---- DB context ----
function fetch_debts($pdo, $user_id) {
  $stmt = $pdo->prepare("SELECT name, original_amount, remaining_amount FROM debts WHERE user_id=? ORDER BY id DESC LIMIT 20");
  $stmt->execute([$user_id]);
  return $stmt->fetchAll();
}

function fetch_month_summary($pdo, $user_id, $month_key) {
  $stmt = $pdo->prepare("SELECT starting_money FROM months WHERE user_id=? AND month_key=? LIMIT 1");
  $stmt->execute([$user_id, $month_key]);
  $start = (float)($stmt->fetchColumn() ?? 0);

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
    "starting" => $start,
    "income" => $income,
    "expense" => $expense,
    "debt_payment" => $debt_payment,
    "remaining" => $remaining
  ];
}

$debts = fetch_debts($pdo, $user_id);
$summary = fetch_month_summary($pdo, $user_id, $month_key);

$system = "You are a finance assistant inside an Expenses & Debts app.
Answer in a practical, friendly way.
Use the provided user data when relevant.
If the user asks for something not in the data, explain what is missing and suggest what to do.";

$context = [
  "month_summary" => $summary,
  "debts" => $debts
];

$userPrompt = "User message: {$message}\n\nUser data (JSON): " . json_encode($context);

// ---- Groq Chat Completions (OpenAI-compatible) ----
$url = "https://api.groq.com/openai/v1/chat/completions";

$payload = [
  "model" => "llama-3.3-70b-versatile",

  "messages" => [
    ["role" => "system", "content" => $system],
    ["role" => "user", "content" => $userPrompt],
  ],
  "temperature" => 0.3
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . $GROQ_KEY,
    "Content-Type: application/json"
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
]);

$out = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
  json_response(["ok" => false, "error" => "cURL error: ".$err], 500);
}

$data = json_decode($out, true) ?? [];

if ($code >= 400) {
  $msg = $data["error"]["message"] ?? ("Groq error HTTP " . $code);
  json_response(["ok" => false, "error" => $msg], 500);
}

$text = $data["choices"][0]["message"]["content"] ?? "";
if (!$text) $text = "I couldn't generate a response.";
// Save BOT message
$stmt = $pdo->prepare("INSERT INTO ai_messages (user_id, month_key, role, message) VALUES (?,?,?,?)");
$stmt->execute([$user_id, $month_key, "bot", $text]);

json_response(["ok" => true, "answer" => $text]);
