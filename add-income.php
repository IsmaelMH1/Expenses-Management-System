<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Add Income</title>
  <link rel="stylesheet" href="./styles.css">
</head>
<body>
  <div class="app" id="dashPage">
    <aside class="sidebar" id="sidebar">
  <?php include __DIR__ . "/partials/sidebar.php"; ?>
</aside>


    <main class="main">
      <header class="topbar">
        <div class="left">
          <button class="iconbtn" id="menuBtn">≡</button>
          <div class="crumb">Transactions / Add Income</div>
        </div>
      </header>

      <section class="content">
        <div class="card" style="max-width:720px;">
          <h2>Add Income</h2>

          <label>Month (YYYY-MM)</label>
          <input id="monthKey" placeholder="2025-12">

          <label>Date</label>
          <input id="txDate" type="date">

          <label>Category</label>
          <input id="incCategory" placeholder="Salary">

          <label>Note</label>
          <input id="incNote" placeholder="December salary">

          <label>Amount</label>
          <input id="incAmount" type="number" step="0.01" placeholder="1200.00">

          <div class="row" style="margin-top:12px;">
            <button class="btn" id="addIncomeBtn">Save income</button>
            <button class="btn secondary" id="backBtn" type="button">Back</button>
          </div>

          <div class="msg" id="msg"></div>
        </div>
      </section>
    </main>
  </div>

  <div id="toastContainer" class="toast-container"></div>

  <script src="./app.js"></script>
  <script>
    const sidebar = document.getElementById("sidebar");
    document.getElementById("menuBtn")?.addEventListener("click", () => sidebar.classList.toggle("open"));
    document.getElementById("backBtn")?.addEventListener("click", () => window.location.href = "./dashboard.php");
  </script>
</body>
</html>
