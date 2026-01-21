<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Expenses • Dashboard</title>
  <link rel="stylesheet" href="./styles.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="app" id="dashPage">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
     <div class="brand">
  <img src="./assets/MAINPIC.PNG" alt="SaveMe" class="brand-logo" />
  <div>
    <div class="title">SaveMe</div>
    <div class="subtitle">Smart money tracking</div>
  </div>
</div>


      <div class="profile">
        <div class="avatar" id="avatar">I</div>
        <div>
          <div class="name" id="hello">Hi</div>
          <div class="email muted" id="emailText"></div>
        </div>
      </div>

      <nav class="nav" id="nav">
        <div class="section">Menu</div>
        <a href="#" class="active" data-tab="tab-dashboard"><span class="icon">🏠</span> Dashboard</a>
        <button class="nav-parent" type="button" data-parent="transactions">
  <span class="icon">💳</span>
  <span class="label">Transactions</span>
  <span class="chev">▾</span>
</button>

<div class="nav-children" id="nav-transactions">
  <a href="#" data-tab="tab-transactions"><span class="icon">📄</span> All Transactions</a>
  <a href="#" data-tab="tab-add-expense"><span class="icon">➖</span> Add Expense</a>
  <a href="#" data-tab="tab-add-income"><span class="icon">➕</span> Add Income</a>
  <a href="#" data-tab="tab-add-debt"><span class="icon">🆕</span> Create Debt</a>
  <a href="#" data-tab="tab-pay-debt"><span class="icon">💰</span> Debt Payment</a>
</div>

       


        <a href="#" data-tab="tab-debts"><span class="icon">📌</span> Debts</a>
        <a href="#" data-tab="tab-reports"><span class="icon">📊</span> Reports</a>
        <a href="#" data-tab="tab-ai"><span class="icon">🤖</span> AI Assistant</a>
        <div class="section">Account</div>
        <a href="#" id="logoutBtn"><span class="icon">🚪</span> Logout</a>
      </nav>

      <div class="bottom">
        <button id="refreshBtn">Refresh</button>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

      <!-- TOPBAR -->
      <header class="topbar">
        <div class="left">
          <button class="iconbtn" id="menuBtn">≡</button>
          <div class="crumb" id="crumb">Home / Dashboard</div>
        </div>
        <div class="right">
          <div class="pill">💾 Backup</div>
          <div class="pill">🔔</div>
        </div>
      </header>

      <!-- CONTENT -->
      <section class="content">

        <!-- DASHBOARD TAB -->
        <div class="grid" id="tab-dashboard">
          <div class="card">
            <h2>Month Settings</h2>
            <div class="row">
              <div>
                <label>Month (YYYY-MM)</label>
                <input id="monthKey" placeholder="2025-12">
              </div>
              <div>
                <label>Starting money</label>
                <input id="startMoney" type="number" placeholder="900">
              </div>
              <div>
                <button class="btn" id="saveMonthBtn">Save</button>
              </div>
            </div>

            <label>Transaction date</label>
            <input id="txDate" type="date">
            <div class="msg" id="msg"></div>
          </div>

        <div class="kpi-grid">

  <div class="kpi-card">
    <div class="badge">Starting</div>
    <div class="kpi" id="kpiStart">0.00</div>
  </div>

  <div class="kpi-card">
    <div class="badge">Income</div>
    <div class="kpi" id="kpiIncome">0.00</div>
  </div>

  <div class="kpi-card">
    <div class="badge">Expenses</div>
    <div class="kpi" id="kpiExpense">0.00</div>
  </div>

  <div class="kpi-card">
    <div class="badge">Debt paid</div>
    <div class="kpi" id="kpiDebtPaid">0.00</div>
  </div>

  <div class="kpi-card">
    <div class="badge">Remaining</div>
    <div class="kpi" id="kpiRemaining">0.00</div>
  </div>

</div>
</div>

          

       <!-- TRANSACTIONS TAB -->
<div class="card" id="tab-transactions" style="display:none;">

  <div class="row" style="align-items:center; margin-bottom:16px;">
    <div>
      <h2 style="margin:0;">Transactions</h2>
      <p class="muted" style="margin:4px 0 0;">Manage your financial activity.</p>
    </div>
    <div style="flex:1;"></div>
    <div style="display:flex;gap:10px;">
      <button class="btn secondary" id="txExportBtn"><span class="icon">⬇️</span> Export</button>
      <button class="btn" id="txAddNewBtn"><span class="icon">＋</span> Add New</button>
    </div>
  </div>
  
  <!-- Modern Filters -->
  <div class="filter-bar">
    <div class="filter-group">
      <label>Search</label>
      <input id="txSearch" placeholder="Search...">
    </div>
    <div class="filter-group">
      <label>Type</label>
      <select id="txType">
        <option value="all">All Types</option>
        <option value="expense">Expense</option>
        <option value="income">Income</option>
        <option value="debt_payment">Debt Payment</option>
      </select>
    </div>
    <div class="filter-group">
      <label>From</label>
      <input id="txFrom" type="date">
    </div>
    <div class="filter-group">
      <label>To</label>
      <input id="txTo" type="date">
    </div>
    <div class="filter-group" style="flex:0 0 auto; display:flex; align-items:end;">
      <button class="btn" id="txApplyBtn" style="height:42px;">Apply Filter</button>
    </div>
  </div>
  
  <div style="height:16px"></div>

  <!-- Table -->
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width:120px;">Date</th>
          <th style="width:140px;">Type</th>
          <th>Category</th>
          <th>Note</th>
          <th style="text-align:right; width:120px;">Amount</th>
          <th style="text-align:right; width:140px;">Actions</th>
        </tr>
      </thead>
      <tbody id="txBody">
        <tr><td colspan="6" class="muted" style="text-align:center; py:20px;">Loading transactions...</td></tr>
      </tbody>
    </table>
  </div>
  
  <!-- Pagination/Meta -->
  <div class="row" style="align-items:center; margin-top:16px;">
    <div class="muted" id="txMeta" style="font-size:13px;"></div>
    <div></div>
    <div style="display:flex; gap:10px;">
      <button class="btn secondary small" id="txPrevBtn" disabled>← Prev</button>
      <button class="btn secondary small" id="txNextBtn" disabled>Next →</button>
    </div>
  </div>

</div>
<!-- TRANSACTIONS: ADD EXPENSE -->
<!-- TRANSACTIONS: ADD EXPENSE -->
<div class="card" id="tab-add-expense" style="display:none; max-width:720px; margin:0 auto;">
  <div style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:16px;">
    <h2 style="margin:0;">Add Expense</h2>
    <p class="muted" style="margin:4px 0 0;">Record a new expense.</p>
  </div>

  <div class="grid grid-2">
    <!-- Month is auto-calculated from date -->
    <div style="grid-column: span 2;">
      <label>Date</label>
      <input id="txDate_exp" type="date" style="width:100%;">
    </div>
  </div>

  <div class="grid grid-2" style="margin-top:12px;">
    <div>
      <label>Category</label>
      <input id="expCategory" placeholder="e.g. Food, Rent">
    </div>
    <div>
      <label>Amount</label>
      <input id="expAmount" type="number" step="0.01" placeholder="0.00">
    </div>
  </div>

  <div style="margin-top:12px;">
    <label>Note</label>
    <input id="expNote" placeholder="Optional description">
  </div>

  <div class="form-actions" style="margin-top:20px;">
    <button class="btn" id="addExpenseBtn" style="width:100%; height:44px; display:flex; justify-content:center; gap:8px;">
      <span class="icon">💾</span> Save Expense
    </button>
  </div>
  <div class="msg" id="msg"></div>
</div>

<!-- TRANSACTIONS: ADD INCOME -->
<!-- TRANSACTIONS: ADD INCOME -->
<div class="card" id="tab-add-income" style="display:none; max-width:720px; margin:0 auto;">
  <div style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:16px;">
    <h2 style="margin:0;">Add Income</h2>
    <p class="muted" style="margin:4px 0 0;">Record new earnings.</p>
  </div>

  <div class="grid grid-2">
     <!-- Month is auto-calculated from date -->
    <div style="grid-column: span 2;">
      <label>Date</label>
      <input id="txDate_inc" type="date" style="width:100%;">
    </div>
  </div>

  <div class="grid grid-2" style="margin-top:12px;">
    <div>
      <label>Category</label>
      <input id="incCategory" placeholder="e.g. Salary">
    </div>
    <div>
      <label>Amount</label>
      <input id="incAmount" type="number" step="0.01" placeholder="0.00">
    </div>
  </div>

  <div style="margin-top:12px;">
    <label>Note</label>
    <input id="incNote" placeholder="Optional description">
  </div>

  <div class="form-actions" style="margin-top:20px;">
    <button class="btn" id="addIncomeBtn" style="width:100%; height:44px; display:flex; justify-content:center; gap:8px;">
      <span class="icon">💾</span> Save Income
    </button>
  </div>
  <div class="msg" id="msg"></div>
</div>

<!-- TRANSACTIONS: CREATE DEBT -->
<!-- TRANSACTIONS: CREATE DEBT -->
<div class="card" id="tab-add-debt" style="display:none; max-width:720px; margin:0 auto;">
  <div style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:16px;">
    <h2 style="margin:0;">Create Debt</h2>
    <p class="muted" style="margin:4px 0 0;">Track a new liability.</p>
  </div>

  <div class="grid grid-2">
    <div>
      <label>Debt Name</label>
      <input id="debtName" placeholder="e.g. Credit Card">
    </div>
    <div>
      <label>Original Amount</label>
      <input id="debtAmount" type="number" step="0.01" placeholder="0.00">
    </div>
  </div>

  <div style="margin-top:12px;">
    <label>Note</label>
    <input id="debtNote" placeholder="Optional details">
  </div>

  <div class="form-actions" style="margin-top:20px;">
    <button class="btn" id="addDebtBtn" style="width:100%; height:44px; display:flex; justify-content:center; gap:8px;">
      <span class="icon">💾</span> Create Debt
    </button>
  </div>
  <div class="msg" id="msg"></div>
</div>

<!-- TRANSACTIONS: DEBT PAYMENT -->
<!-- TRANSACTIONS: DEBT PAYMENT -->
<div class="card" id="tab-pay-debt" style="display:none; max-width:720px; margin:0 auto;">
  <div style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:16px;">
    <h2 style="margin:0;">Record Debt Payment</h2>
    <p class="muted" style="margin:4px 0 0;">Pay down an existing debt.</p>
  </div>

  <div class="grid grid-2">
     <!-- Month is auto-calculated from date -->
    <div style="grid-column: span 2;">
      <label>Date</label>
      <input id="txDate_pay" type="date" style="width:100%;">
    </div>
  </div>

  <div class="grid grid-2" style="margin-top:12px;">
    <div>
      <label>Select Debt</label>
      <select id="debtSelect" style="height:44px;"></select>
    </div>
    <div>
      <label>Amount</label>
      <input id="payAmount" type="number" step="0.01" placeholder="0.00">
    </div>
  </div>

  <div style="margin-top:12px;">
    <label>Note</label>
    <input id="payNote" placeholder="Optional note">
  </div>

  <div class="form-actions" style="margin-top:20px;">
    <button class="btn" id="addPayBtn" style="width:100%; height:44px; display:flex; justify-content:center; gap:8px;">
      <span class="icon">💾</span> Save Payment
    </button>
  </div>
  <div class="msg" id="msg"></div>
</div>

        <!-- DEBTS TAB (table grid) -->
        <div class="card" id="tab-debts" style="display:none;">
          <h2>Debts</h2>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th style="text-align:right;">Original</th>
                  <th style="text-align:right;">Remaining</th>
                  <th>Note</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody id="debtsBody">
                <!-- we already load this in report; later we’ll load directly -->
              </tbody>
            </table>
          </div>
        </div>

<!-- EDIT DEBT MODAL -->
<div class="modal-backdrop" id="debtEditBackdrop" style="display:none;">
  <div class="modal">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title">Edit Debt</div>
        <button class="iconbtn" id="debtEditCloseBtn" title="Close">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editDebtId">
        
        <label>Name</label>
        <input id="editDebtName" placeholder="Credit Card">

        <div class="grid grid-2">
          <div>
             <label>Original Amount</label>
             <input id="editDebtOriginal" type="number" step="0.01">
          </div>
          <div>
             <label>Remaining Amount</label>
             <input id="editDebtRemaining" type="number" step="0.01">
          </div>
        </div>

        <div style="margin-top:10px;">
          <label>Note</label>
          <input id="editDebtNote" placeholder="Notes">
        </div>
      </div>
      <div class="modal-actions">
        <div class="loading-spinner" id="debtEditSpinner" style="display:none;"></div>
        <button class="btn secondary" id="debtEditCancelBtn">Cancel</button>
        <button class="btn" id="debtEditSaveBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

        <!-- REPORTS TAB -->
        <div class="grid" id="tab-reports" style="display:none;">
          
          <!-- Summary Cards -->
           <div class="quick-grid">
             <div class="kpi-card">
               <div class="badge">Income</div>
               <div class="kpi success" id="rptIncome">0.00</div>
             </div>
             <div class="kpi-card">
               <div class="badge">Expenses</div>
               <div class="kpi error" id="rptExpense">0.00</div>
             </div>
             <div class="kpi-card">
               <div class="badge">Net</div>
               <div class="kpi" id="rptNet">0.00</div>
             </div>
           </div>

           <!-- Charts Row -->
           <div class="grid-2">
             <div class="card" style="height:400px; position:relative;">
               <h2>Expenses by Category</h2>
               <div style="height:320px; display:flex; justify-content:center;">
                 <canvas id="chartCat"></canvas>
               </div>
             </div>
             <div class="card" style="height:400px; position:relative;">
               <h2>Income vs Expense</h2>
               <div style="height:320px;">
                 <canvas id="chartBar"></canvas>
               </div>
             </div>
           </div>

           <div class="card" id="debtsBox">
             <h2>Debts Link</h2>
             <p class="muted">See detailed breakdown in the Debts tab.</p>
             <button class="btn secondary" onclick="document.querySelector('[data-tab=tab-debts]').click()">Go to Debts</button>
           </div>
        </div>
<!-- AI TAB -->
<!-- AI TAB -->
<div class="card chat-card" id="tab-ai" style="display:none;">


  <div class="chat-head">
    <div>
      <h2 style="margin:0;">AI Assistant</h2>
      <p class="muted" style="margin:6px 0 0;">
        Ask about budgeting, debts, savings, and monthly planning.
      </p>
    </div>

    <div class="chat-tools">
      <button class="btn secondary" id="aiReloadBtn" type="button">Reload</button>
      <button class="btn secondary" id="aiClearBtn" type="button">Clear</button>
    </div>
  </div>

  <div class="chat-wrap" id="chatWrap">
    <div class="chat-hint">Loading previous chat…</div>
  </div>

  <div class="chat-typing" id="aiTyping" style="display:none;">Assistant is typing…</div>

  <div class="chat-inputbar">
    <input id="aiPrompt" placeholder="Example: How much can I save if I spend 20 less per week?" />
    <button class="btn" id="aiSendBtn">Send</button>
  </div>

</div>


      </section>
    </main>
  </div>
<!-- EDIT MODAL -->
<div id="modalBackdrop" class="modal-backdrop" style="display:none;"></div>
<!-- TOASTS -->
<div id="toastContainer" class="toast-container"></div>
<!-- TOASTS -->
<div id="toastContainer" class="toast-container"></div>

<div id="editModal" class="modal" style="display:none;">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <div class="modal-title">Edit Transaction</div>
        <div class="modal-subtitle" id="editTxIdText"></div>
      </div>
      <button class="iconbtn" id="modalCloseBtn" title="Close">✕</button>
    </div>

    <div class="grid grid-2" style="margin-top:12px;">
      <div>
        <label>Date</label>
        <input id="mDate" type="date">
      </div>
      <div>
        <label>Type</label>
        <select id="mType">
          <option value="expense">Expense</option>
          <option value="income">Income</option>
          <option value="debt_payment">Debt payment</option>
        </select>
      </div>
    </div>

    <div class="grid grid-2" style="margin-top:10px;">
      <div>
        <label>Category</label>
        <input id="mCategory" placeholder="Food">
      </div>
      <div>
        <label>Amount</label>
        <input id="mAmount" type="number" step="0.01" placeholder="0.00">
      </div>
    </div>

    <div style="margin-top:10px;">
      <label>Note</label>
      <input id="mNote" placeholder="Optional note">
    </div>

    <div id="mDebtBox" style="margin-top:10px; display:none;">
      <label>Debt</label>
      <select id="mDebtSelect"></select>
      <p class="muted" style="margin:6px 0 0;">Required only for debt payments.</p>
    </div>

    <div class="msg error" id="mError" style="display:none;"></div>

    <div class="modal-actions">
      <button class="btn secondary" id="modalCancelBtn">Cancel</button>
      <button class="btn" id="modalSaveBtn">Save changes</button>
    </div>
  </div>
</div>
<!-- DELETE MODAL -->
<div id="deleteBackdrop" class="modal-backdrop" style="display:none;"></div>

<div id="deleteModal" class="modal" style="display:none;">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <div class="modal-title">Delete Transaction</div>
        <div class="modal-subtitle" id="delSubtitle">Are you sure?</div>
      </div>
      <button class="iconbtn" id="delCloseBtn" title="Close">✕</button>
    </div>

    <p style="margin:12px 0 0; color:var(--muted);">
      This action cannot be undone.
    </p>

    <div class="modal-actions">
      <button class="btn secondary" id="delCancelBtn">Cancel</button>
      <button class="btn" id="delConfirmBtn">Delete</button>
    </div>
  </div>
</div>
<!-- DELETE MODAL -->
<div id="deleteBackdrop" class="modal-backdrop" style="display:none;"></div>

<div id="deleteModal" class="modal" style="display:none;">
  <div class="modal-card">
    <div class="modal-head">
      <div>
        <div class="modal-title">Delete Transaction</div>
        <div class="modal-subtitle" id="delSubtitle">Are you sure?</div>
      </div>
      <button class="iconbtn" id="delCloseBtn" title="Close">✕</button>
    </div>

    <p style="margin:12px 0 0; color:var(--muted);">
      This action cannot be undone.
    </p>

    <div class="modal-actions">
      <button class="btn secondary" id="delCancelBtn">Cancel</button>
      <button class="btn" id="delConfirmBtn">Delete</button>
    </div>
  </div>
</div>

  <script src="./app.js"></script>

  <!-- small UI script for menu tabs -->
  <script>
    // Sidebar toggle (mobile)
    const sidebar = document.getElementById("sidebar");
    document.getElementById("menuBtn").addEventListener("click", () => {
      sidebar.classList.toggle("open");
    });

    // Tab switching
    const nav = document.getElementById("nav");
    // Collapsible Transactions submenu
const parentBtn = nav.querySelector('.nav-parent[data-parent="transactions"]');
const children = document.getElementById("nav-transactions");

parentBtn?.addEventListener("click", () => {
  parentBtn.classList.toggle("open");
  children.classList.toggle("open");
});

    const crumb = document.getElementById("crumb");
 const tabs = [
  "tab-dashboard",
  "tab-transactions",
  "tab-add-expense",
  "tab-add-income",
  "tab-add-debt",
  "tab-pay-debt",
  "tab-debts",
  "tab-reports",
  "tab-ai"
];

// Collapsible Transactions submenu



    nav.querySelectorAll("a[data-tab]").forEach(a => {
      a.addEventListener("click", (e) => {
        e.preventDefault();
        const tab = a.dataset.tab;

        // active link
        nav.querySelectorAll("a[data-tab]").forEach(x => x.classList.remove("active"));
        a.classList.add("active");

        // show/hide tabs
        tabs.forEach(id => document.getElementById(id).style.display = (id === tab ? "" : "none"));
        // If AI tab opened, load history
if (tab === "tab-ai") {
  if (typeof loadAiHistory === "function") loadAiHistory();
}


        // breadcrumb
        const label = a.textContent.trim();
        crumb.textContent = `Home / ${label}`;

        // close sidebar on mobile after click
        sidebar.classList.remove("open");
      });
    });
  </script>
  <!-- LOGOUT MODAL -->
<!-- LOGOUT MODAL -->
<div id="logoutBackdrop" class="modal-backdrop" style="display:none;"></div>

<div id="logoutModal" class="modal" style="display:none;">
  <div class="modal-card logout-card">
    <div class="logout-brand">
      <img src="./assets/MAINPIC.PNG" alt="SaveMe" class="logout-logo">
      <div>
        <div class="logout-title">SaveMe</div>
        <div class="logout-subtitle">Are you sure you want to log out?</div>
      </div>
    </div>

    <div class="modal-actions" style="margin-top:16px;">
      <button class="btn secondary" id="logoutCancelBtn" type="button">No, stay</button>

      <button class="btn" id="logoutConfirmBtn" type="button">
        <span class="btn-text">Yes, log out</span>
        <span class="btn-spinner" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</div>


</body>
</html>
