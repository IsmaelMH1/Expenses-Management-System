<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Create Debt</title>
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
          <div class="crumb">Debts / Create</div>
        </div>
      </header>

      <section class="content">
        <div class="card" style="max-width:720px;">
          <h2>Create Debt</h2>

          <label>Debt name</label>
          <input id="debtName" placeholder="Credit Card">

          <label>Original amount</label>
          <input id="debtAmount" type="number" step="0.01" placeholder="500.00">

          <label>Note</label>
          <input id="debtNote" placeholder="Visa / Bank">

          <div class="row" style="margin-top:12px;">
            <button class="btn" id="addDebtBtn">Create debt</button>
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
