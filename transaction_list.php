<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";

$user_id = require_auth();

// Query params
$month_key = $_GET["month_key"] ?? null;            // required
$type      = $_GET["type"] ?? "all";                // all|expense|income|debt_payment
$search    = trim($_GET["search"] ?? "");           // category/note
$date_from = $_GET["date_from"] ?? null;            // YYYY-MM-DD
$date_to   = $_GET["date_to"] ?? null;              // YYYY-MM-DD
$min_amount = $_GET["min_amount"] ?? null;          // number
$limit     = (int)($_GET["limit"] ?? 50);            // default 50
$offset    = (int)($_GET["offset"] ?? 0);

if (!$month_key || !preg_match('/^\d{4}-\d{2}$/', $month_key)) {
  json_response(["ok" => false, "error" => "month_key is required (YYYY-MM)"], 400);
}

$allowedTypes = ["all", "expense", "income", "debt_payment"];
if (!in_array($type, $allowedTypes, true)) {
  json_response(["ok" => false, "error" => "Invalid type"], 400);
}

if ($date_from !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
  json_response(["ok" => false, "error" => "date_from must be YYYY-MM-DD"], 400);
}
if ($date_to !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
  json_response(["ok" => false, "error" => "date_to must be YYYY-MM-DD"], 400);
}

if ($limit < 1) $limit = 1;
if ($limit > 200) $limit = 200;
if ($offset < 0) $offset = 0;

// Find month_id
$stmt = $pdo->prepare("SELECT id FROM months WHERE user_id=? AND month_key=?");
$stmt->execute([$user_id, $month_key]);
$month = $stmt->fetch();
if (!$month) {
  json_response(["ok" => false, "error" => "Month not found"], 404);
}
$month_id = (int)$month["id"];

// Build filters
$where = "t.user_id = ? AND t.month_id = ?";
$params = [$user_id, $month_id];

if ($type !== "all") {
  $where .= " AND t.type = ?";
  $params[] = $type;
}

if ($search !== "") {
  $where .= " AND (t.category LIKE ? OR t.note LIKE ?)";
  $like = "%" . $search . "%";
  $params[] = $like;
  $params[] = $like;
}

if ($date_from !== null) {
  $where .= " AND t.tx_date >= ?";
  $params[] = $date_from;
}
if ($date_to !== null) {
  $where .= " AND t.tx_date <= ?";
  $params[] = $date_to;
}

if ($min_amount !== null && $min_amount !== "") {
  $min_amount = (float)$min_amount;
  $where .= " AND t.amount >= ?";
  $params[] = $min_amount;
}

// Count for pagination
$stmtC = $pdo->prepare("SELECT COUNT(*) AS cnt FROM transactions t WHERE $where");
$stmtC->execute($params);
$total = (int)($stmtC->fetch()["cnt"] ?? 0);

// Data query (join debts to show debt name)
$sql = "
  SELECT
    t.id, t.tx_date, t.type, t.category, t.note, t.amount,
    t.debt_id, d.name AS debt_name
  FROM transactions t
  LEFT JOIN debts d ON d.id = t.debt_id AND d.user_id = t.user_id
  WHERE $where
  ORDER BY t.tx_date DESC, t.id DESC
  LIMIT $limit OFFSET $offset
";

$stmtL = $pdo->prepare($sql);
$stmtL->execute($params);
$rows = $stmtL->fetchAll();

json_response([
  "ok" => true,
  "month_key" => $month_key,
  "total" => $total,
  "limit" => $limit,
  "offset" => $offset,
  "transactions" => $rows
]);
