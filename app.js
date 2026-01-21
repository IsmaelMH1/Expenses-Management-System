// web/app.js
const API_BASE = "../api";
let TX_CACHE = [];
let DEBTS_CACHE = [];
let EDITING_ID = null;


async function api(path, { method = "GET", body } = {}) {
  const opts = {
    method,
    headers: { "Content-Type": "application/json" },
    credentials: "include", // IMPORTANT: sends session cookie
  };
  if (body !== undefined) opts.body = JSON.stringify(body);

  const res = await fetch(`${API_BASE}${path}`, opts);
  const data = await res.json().catch(() => ({}));
  if (!res.ok || data.ok === false) {
    const msg = data?.error || `Request failed (${res.status})`;
    throw new Error(msg);
  }
  return data;
}

function $(id) { return document.getElementById(id); }
function showAuthMessage(text, type = "error") {
  const msgEl = $("loginMsg");
  if (!msgEl) return;
  msgEl.textContent = text;
  msgEl.className = type === "success" ? "auth-msg success" : "auth-msg";
}

function setFieldError(inputEl, message) {
  if (!inputEl) return;
  inputEl.classList.add("field-error");
  inputEl.setAttribute("aria-invalid", "true");
  if (message) inputEl.setAttribute("title", message);
}

function clearFieldError(inputEl) {
  if (!inputEl) return;
  inputEl.classList.remove("field-error");
  inputEl.removeAttribute("aria-invalid");
  inputEl.removeAttribute("title");
}

function shake(el) {
  if (!el) return;
  el.classList.remove("shake");
  // restart animation
  void el.offsetWidth;
  el.classList.add("shake");
}

function isValidEmail(email) {
  // Simple, practical validation
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function toast(type, title, message, timeoutMs = 3000) {
  const c = document.getElementById("toastContainer");
  if (!c) return;

  const el = document.createElement("div");
  el.className = `toast ${type}`;

  const icon = type === "success" ? "✅" : "⚠️";

  el.innerHTML = `
    <div class="ticon">${icon}</div>
    <div class="tmain">
      <p class="ttitle">${title}</p>
      <p class="tmsg">${message}</p>
    </div>
    <button title="Close">✕</button>
  `;

  el.querySelector("button").addEventListener("click", () => el.remove());
  c.appendChild(el);

  setTimeout(() => {
    if (el.isConnected) el.remove();
  }, timeoutMs);
}

let CHAT_ANIM_DELAY = 0;

function addChatMessage(role, text) {
  const wrap = document.getElementById("chatWrap");
  if (!wrap) return;

  // row container
  const row = document.createElement("div");
  row.className = `chat-row ${role}`;

  // bot avatar
  if (role === "bot") {
    const avatar = document.createElement("div");
    avatar.className = "chat-avatar bot";
    avatar.textContent = "AI"; // or 🤖
    row.appendChild(avatar);
  }

  // bubble
  const msg = document.createElement("div");
  msg.className = `chat-msg ${role}`;
  msg.textContent = text;

  row.appendChild(msg);
  wrap.appendChild(row);

  wrap.scrollTop = wrap.scrollHeight;
}

function openLogoutModal() {
  document.getElementById("logoutBackdrop").style.display = "";
  document.getElementById("logoutModal").style.display = "";
}

function closeLogoutModal() {
  document.getElementById("logoutBackdrop").style.display = "none";
  document.getElementById("logoutModal").style.display = "none";
}


async function loadAiHistory() {
  const wrap = document.getElementById("chatWrap");
  if (!wrap) return;

  const month_key = (document.getElementById("monthKey")?.value || "").trim() || monthKeyNow();

  wrap.innerHTML = `<div class="chat-hint">Loading previous chat…</div>`;

  try {
    const data = await api(`/ai_history.php?month_key=${encodeURIComponent(month_key)}&limit=50`);

    const msgs = data.messages || [];
    if (msgs.length === 0) {
      wrap.innerHTML = `<div class="chat-hint">No chat history yet. Ask me anything about your expenses, income, or debts.</div>`;
      return;
    }

    wrap.innerHTML = "";
    msgs.forEach(m => {
      const role = (m.role === "bot") ? "bot" : "user";
      addChatMessage(role, m.message);
    });


    wrap.scrollTop = wrap.scrollHeight;
  } catch (e) {
    wrap.innerHTML = `<div class="chat-hint">⚠️ Couldn’t load history: ${e.message}</div>`;
  }
}



function monthKeyNow() {
  const d = new Date();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  return `${d.getFullYear()}-${m}`;
}

function dateToday() {
  const d = new Date();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${d.getFullYear()}-${m}-${day}`;
}

// ---------- LOGIN ----------
async function handleLogin() {
  const btn = $("loginBtn");
  const emailEl = $("email");
  const passEl = $("password");

  // clear previous UI
  showAuthMessage("");
  clearFieldError(emailEl);
  clearFieldError(passEl);

  const email = (emailEl?.value || "").trim();
  const password = passEl?.value || "";

  // --- REQUIRED field messages (specific) ---
  const missing = [];
  if (!email) {
    missing.push("email");
    setFieldError(emailEl, "Email is required.");
  }
  if (!password) {
    missing.push("password");
    setFieldError(passEl, "Password is required.");
  }

  if (missing.length > 0) {
    shake(document.querySelector(".auth-card"));

    if (missing.length === 2) showAuthMessage("Please enter your email and password.");
    else if (missing[0] === "email") showAuthMessage("Please enter your email.");
    else showAuthMessage("Please enter your password.");

    return; // (button not disabled yet)
  }

  // --- Format rules ---
  if (!isValidEmail(email)) {
    setFieldError(emailEl, "Invalid email format.");
    shake(document.querySelector(".auth-card"));
    showAuthMessage("Please enter a valid email.");
    return;
  }

  if (password.length < 8) {
    setFieldError(passEl, "Password too short.");
    shake(document.querySelector(".auth-card"));
    showAuthMessage("Password must be at least 8 characters.");
    return;
  }

  // --- Server call ---
  try {
    if (btn) btn.disabled = true;

    await api("/login.php", {
      method: "POST",
      body: { email, password }
    });

    window.location.href = "/expenses-app/web/dashboard.php";

  } catch (e) {
    // This should show "Invalid credentials" if backend returns it
    showAuthMessage(e.message || "Login failed.");
  } finally {
    if (btn) btn.disabled = false;
  }
}



async function handleRegister() {
  const btn = $("loginBtn");
  const nameEl = $("name");
  const emailEl = $("email");
  const passEl = $("password");

  // clear previous UI
  showAuthMessage("");
  clearFieldError(nameEl);
  clearFieldError(emailEl);
  clearFieldError(passEl);

  const name = (nameEl?.value || "").trim();
  const email = (emailEl?.value || "").trim();
  const password = passEl?.value || "";

  // --- REQUIRED field messages (specific) ---
  const missing = [];
  if (!name) {
    missing.push("name");
    setFieldError(nameEl, "Name is required.");
  }
  if (!email) {
    missing.push("email");
    setFieldError(emailEl, "Email is required.");
  }
  if (!password) {
    missing.push("password");
    setFieldError(passEl, "Password is required.");
  }

  if (missing.length > 0) {
    shake(document.querySelector(".auth-card"));

    // Professional combined message
    if (missing.length === 3) showAuthMessage("Please enter your name, email, and password.");
    else if (missing.length === 2) {
      const a = missing[0], b = missing[1];
      showAuthMessage(`Please enter your ${a} and ${b}.`);
    } else {
      showAuthMessage(`Please enter your ${missing[0]}.`);
    }

    return;
  }

  // --- Format rules ---
  if (!isValidEmail(email)) {
    setFieldError(emailEl, "Invalid email format.");
    shake(document.querySelector(".auth-card"));
    showAuthMessage("Please enter a valid email.");
    return;
  }

  if (password.length < 8) {
    setFieldError(passEl, "Password too short.");
    shake(document.querySelector(".auth-card"));
    showAuthMessage("Password must be at least 8 characters.");
    return;
  }

  // --- Server call ---
  try {
    if (btn) btn.disabled = true;

    await api("/register.php", {
      method: "POST",
      body: { name, email, password }
    });

    showAuthMessage("Account created. You can login now.", "success");
  } catch (e) {
    // Backend should return "Email already exists" etc.
    showAuthMessage(e.message || "Registration failed.");
  } finally {
    if (btn) btn.disabled = false;
  }
}


// ---------- DASHBOARD ----------
async function ensureAuthOrRedirect() {
  try {
    const me = await api("/me.php");

    // Only set UI if elements exist on the page
    const hello = document.getElementById("hello");
    const emailText = document.getElementById("emailText");
    const avatar = document.getElementById("avatar");

    if (hello) hello.textContent = me.user.name;
    if (emailText) emailText.textContent = me.user.email;

    if (avatar) {
      const first = (me.user.name || "U").trim()[0].toUpperCase();
      avatar.textContent = first;
    }

    return me;
  } catch (e) {
    window.location.href = "/expenses-app/web/login.html";
  }
}



// Cache for current debts (already declared at top)
// let DEBTS_CACHE = [];

async function loadDebtsIntoSelect() {
  const dRes = await api("/debt_list.php");
  // dRes is { ok: true, debts: [...] }
  const debts = dRes.debts || [];
  DEBTS_CACHE = debts;

  // 1. Populate Select (for debt payment form)
  const sel = $("debtSelect");
  if (sel) {
    sel.innerHTML = "";
    debts.forEach(d => {
      const opt = document.createElement("option");
      opt.value = d.id;
      opt.textContent = `${d.name} (rem: ${Number(d.remaining_amount).toFixed(2)})`;
      sel.appendChild(opt);
    });
  }

  // 2. Populate Modal Select (for editing transactions)
  const mSel = document.getElementById("mDebtSelect");
  if (mSel) {
    mSel.innerHTML = '<option value="">-- No Debt --</option>';
    debts.forEach(d => {
      const opt = document.createElement("option");
      opt.value = d.id;
      opt.textContent = `${d.name} (rem: ${Number(d.remaining_amount).toFixed(2)})`;
      mSel.appendChild(opt);
    });
  }

  // 3. Populate Table (Debts Tab)
  const tbody = $("debtsBody");
  if (tbody) {
    tbody.innerHTML = "";
    if (debts.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="muted" style="text-align:center;">No debts found.</td></tr>`;
    } else {
      debts.forEach(d => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
           <td style="font-weight:600;">${d.name}</td>
           <td style="text-align:right;">${Number(d.original_amount).toFixed(2)}</td>
           <td style="text-align:right; color:${Number(d.remaining_amount) > 0 ? '#d97706' : '#059669'};">
             ${Number(d.remaining_amount).toFixed(2)}
           </td>
           <td class="muted">${d.note || ""}</td>
           <td style="text-align:right;">
             <button class="btn secondary small act-edit-debt" data-id="${d.id}">Edit</button>
             <button class="btn small error-text act-del-debt" style="color:#ef4444; border-color:#fecaca; background:#fff;" data-id="${d.id}">Del</button>
           </td>
         `;
        tbody.appendChild(tr);
      });

      // Wire up buttons
      tbody.querySelectorAll(".act-edit-debt").forEach(b => b.addEventListener("click", () => openEditDebtModal(b.dataset.id)));
      tbody.querySelectorAll(".act-del-debt").forEach(b => b.addEventListener("click", () => deleteDebt(b.dataset.id)));
    }
  }
}

// --- DEBT MANAGEMENT FUNCTIONS ---
let EDIT_DEBT_ID = null;

function openEditDebtModal(id) {
  const debt = DEBTS_CACHE.find(d => Number(d.id) === Number(id));
  if (!debt) return;

  EDIT_DEBT_ID = Number(id);
  $("editDebtId").value = debt.id;
  $("editDebtName").value = debt.name;
  $("editDebtOriginal").value = debt.original_amount;
  $("editDebtRemaining").value = debt.remaining_amount;
  $("editDebtNote").value = debt.note;

  $("debtEditBackdrop").style.display = "";
}

function closeEditDebtModal() {
  EDIT_DEBT_ID = null;
  $("debtEditBackdrop").style.display = "none";
}

async function saveDebtEdit() {
  const id = EDIT_DEBT_ID;
  if (!id) return;

  const body = {
    id: id,
    name: $("editDebtName").value.trim(),
    original_amount: Number($("editDebtOriginal").value),
    remaining_amount: Number($("editDebtRemaining").value),
    note: $("editDebtNote").value.trim()
  };

  if (!body.name) return toast("error", "Error", "Name is required");

  try {
    setBtnLoading("debtEditSaveBtn", true);
    await api("/debt_update.php", { method: "POST", body });
    toast("success", "Saved", "Debt updated.");
    closeEditDebtModal();
    await loadDebtsIntoSelect();
    await loadReport();
  } catch (e) {
    toast("error", "Error", e.message);
  } finally {
    setBtnLoading("debtEditSaveBtn", false);
  }
}

function deleteDebt(id) {
  if (!confirm("Are you sure you want to delete this debt?")) return;

  (async () => {
    try {
      await api("/debt_delete.php", { method: "POST", body: { id: Number(id) } });
      toast("success", "Deleted", "Debt removed.");
      await loadDebtsIntoSelect();
      await loadReport();
    } catch (e) {
      toast("error", "Error", e.message);
    }
  })();
}

// Wire up Debt Modal Buttons
document.addEventListener("DOMContentLoaded", () => {
  const cancel = $("debtEditCancelBtn");
  if (cancel) cancel.addEventListener("click", closeEditDebtModal);

  const close = $("debtEditCloseBtn");
  if (close) close.addEventListener("click", closeEditDebtModal);

  const save = $("debtEditSaveBtn");
  if (save) save.addEventListener("click", saveDebtEdit);

  // Close on backdrop click
  const back = $("debtEditBackdrop");
  if (back) back.addEventListener("click", (e) => {
    if (e.target === back) closeEditDebtModal();
  });
});

let chartCatInst = null;
let chartBarInst = null;

async function loadReport() {
  $("msg").textContent = "";
  const month_key = $("monthKey").value.trim();
  const report = await api(`/report_month.php?month_key=${encodeURIComponent(month_key)}`);

  // --- 1. Top Summary Cards ---
  // Using the totals from report
  const inc = Number(report.totals.income);
  const exp = Number(report.totals.expense);
  const net = inc - exp;

  if ($("rptIncome")) $("rptIncome").textContent = inc.toFixed(2);
  if ($("rptExpense")) $("rptExpense").textContent = exp.toFixed(2);

  const netEl = $("rptNet");
  if (netEl) {
    netEl.textContent = (net >= 0 ? "+" : "") + net.toFixed(2);
    netEl.className = "kpi " + (net >= 0 ? "success" : "error");
  }

  // --- 2. Chart: Expenses by Category (Doughnut) ---
  const cats = report.breakdown.expenses_by_category || [];
  const catLabels = cats.map(c => c.category);
  const catData = cats.map(c => Number(c.total));
  const catColors = [
    "#facc15", "#f87171", "#60a5fa", "#4ade80", "#a78bfa",
    "#fb923c", "#f472b6", "#22d3ee", "#94a3b8", "#fbbf24"
  ];

  const ctxCat = document.getElementById("chartCat");
  if (ctxCat) {
    if (chartCatInst) chartCatInst.destroy();
    chartCatInst = new Chart(ctxCat, {
      type: "doughnut",
      data: {
        labels: catLabels,
        datasets: [{
          data: catData,
          backgroundColor: catColors,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right' }
        }
      }
    });
  }

  // --- 3. Chart: Income vs Expense (Bar) ---
  const ctxBar = document.getElementById("chartBar");
  if (ctxBar) {
    if (chartBarInst) chartBarInst.destroy();

    // Simple bar chart: Income vs Expense
    // If you had daily data we could do a line chart, but report_month.php usually returns totals.
    // We'll show just two big bars for now to visualize the gap.

    chartBarInst = new Chart(ctxBar, {
      type: "bar",
      data: {
        labels: ["Income", "Expenses"],
        datasets: [{
          label: 'Amount',
          data: [inc, exp],
          backgroundColor: ["#4ade80", "#f87171"],
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }
}

async function sendAiMessage() {
  const input = document.getElementById("aiPrompt");
  const btn = document.getElementById("aiSendBtn");
  const typing = document.getElementById("aiTyping");
  const wrap = document.getElementById("chatWrap");

  if (!input) return;

  const prompt = input.value.trim();
  if (!prompt) return;

  // Add user bubble
  addChatMessage("user", prompt);
  input.value = "";

  try {
    if (btn) btn.disabled = true;
    if (typing) typing.style.display = "";

    const res = await api("/ai_chat_groq.php", {
      method: "POST",
      body: {
        message: prompt,
        month_key: (document.getElementById("monthKey")?.value || "").trim() || monthKeyNow()
      }
    });

    addChatMessage("bot", res.answer || "No answer returned.");

  } catch (e) {
    addChatMessage("bot", "⚠️ " + (e.message || "Failed"));
  } finally {
    if (typing) typing.style.display = "none";
    if (btn) btn.disabled = false;
    if (wrap) wrap.scrollTop = wrap.scrollHeight;
  }
}


function openModal() {
  document.getElementById("modalBackdrop").style.display = "";
  document.getElementById("editModal").style.display = "";
}
function closeModal() {
  document.getElementById("modalBackdrop").style.display = "none";
  document.getElementById("editModal").style.display = "none";
  document.getElementById("mError").style.display = "none";
  document.getElementById("mError").textContent = "";
  EDITING_ID = null;
}
let PENDING_DELETE_ID = null;

function openDeleteModal(id, subtitleText = "") {
  PENDING_DELETE_ID = Number(id);
  document.getElementById("delSubtitle").textContent = subtitleText || `Transaction #${PENDING_DELETE_ID}`;
  document.getElementById("deleteBackdrop").style.display = "";
  document.getElementById("deleteModal").style.display = "";
}

function closeDeleteModal() {
  PENDING_DELETE_ID = null;
  document.getElementById("deleteBackdrop").style.display = "none";
  document.getElementById("deleteModal").style.display = "none";
}


function setDebtBoxVisibility() {
  const type = document.getElementById("mType").value;
  document.getElementById("mDebtBox").style.display = (type === "debt_payment") ? "" : "none";
}

function startEditTransaction(id) {
  const tx = TX_CACHE.find(x => Number(x.id) === Number(id));
  if (!tx) return alert("Transaction not found. Click Apply and try again.");

  EDITING_ID = Number(id);
  document.getElementById("editTxIdText").textContent = `Transaction #${EDITING_ID}`;

  document.getElementById("mDate").value = tx.tx_date;
  document.getElementById("mType").value = tx.type;
  document.getElementById("mCategory").value = tx.category || "";
  document.getElementById("mNote").value = tx.note || "";
  document.getElementById("mAmount").value = Number(tx.amount);

  setDebtBoxVisibility();

  const mDebt = document.getElementById("mDebtSelect");
  if (mDebt) mDebt.value = tx.debt_id ?? "";

  openModal();
}

async function saveModalEdit() {
  const mError = document.getElementById("mError");
  mError.style.display = "none";
  mError.textContent = "";

  const id = EDITING_ID;
  if (!id) return;

  const tx_date = document.getElementById("mDate").value;
  const type = document.getElementById("mType").value;
  const category = document.getElementById("mCategory").value.trim();
  const note = document.getElementById("mNote").value.trim();
  const amount = Number(document.getElementById("mAmount").value);

  let debt_id = null;
  if (type === "debt_payment") {
    const v = document.getElementById("mDebtSelect").value;
    debt_id = v ? Number(v) : null;
    if (!debt_id) {
      mError.textContent = "Please choose a debt for debt payments.";
      mError.style.display = "";
      return;
    }
  }

  if (!tx_date || !category || !amount || amount <= 0) {
    mError.textContent = "Please fill date, category and a valid amount.";
    mError.style.display = "";
    return;
  }

  try {
    document.getElementById("modalSaveBtn").disabled = true;

    await api("/transaction_update.php", {
      method: "POST",
      body: { id, tx_date, type, category, note, amount, debt_id }
    });

    closeModal();


    toast("success", "Updated", "Transaction updated successfully.");

    await loadDebtsIntoSelect();
    await loadReport();
    await loadTransactions();
  } catch (e) {
    mError.textContent = e.message;
    mError.style.display = "";
  } finally {
    document.getElementById("modalSaveBtn").disabled = false;
  }
}

async function loadTransactions() {
  const month_key = $("monthKey").value.trim();

  const qs = new URLSearchParams({
    month_key,
    type: $("txType")?.value || "all",
    search: $("txSearch")?.value || "",
    min_amount: $("txMinAmount")?.value || "",
    date_from: $("txFrom")?.value || "",
    date_to: $("txTo")?.value || "",
    limit: "50",
    offset: "0",
  });

  const data = await api(`/transaction_list.php?${qs.toString()}`);
  TX_CACHE = data.transactions || [];


  const body = $("txBody");
  if (!body) return;

  /* New loadTransactions with badges */
  body.innerHTML = "";
  data.transactions.forEach(t => {
    const amountVal = Number(t.amount);
    const amountStr = amountVal.toFixed(2);

    // Determine badge class and label
    let badgeClass = "";
    let typeLabel = "";
    let amountClass = "";
    let sign = "";

    switch (t.type) {
      case "income":
        badgeClass = "income";
        typeLabel = "Income";
        amountClass = "pos";
        sign = "+";
        break;
      case "expense":
        badgeClass = "expense";
        typeLabel = "Expense";
        amountClass = "neg";
        sign = "-";
        break;
      case "debt_payment":
        badgeClass = "debt_payment";
        typeLabel = "Debt Pay";
        amountClass = "neg";
        sign = "-";
        break;
      default:
        badgeClass = "gray";
        typeLabel = t.type;
    }

    // show debt name inside note for debt payments
    const note = (t.type === "debt_payment" && t.debt_name)
      ? `${t.note || ""} (Debt: ${t.debt_name})`
      : (t.note || "");

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${t.tx_date}</td>
      <td><span class="badge-tx ${badgeClass}">${typeLabel}</span></td>
      <td style="font-weight:600;">${t.category}</td>
      <td class="muted" style="font-size:13px;">${note}</td>
      <td style="text-align:right;" class="tx-amount ${amountClass}">${sign}${amountStr}</td>
      <td style="text-align:right;">
        <button class="btn secondary small" data-action="edit" data-id="${t.id}">Edit</button>
        <button class="btn small error-text" style="color:#ef4444; border-color:#fecaca; background:#fff;" data-action="delete" data-id="${t.id}">Del</button>
      </td>
    `;
    body.appendChild(tr);
  });

  if (data.transactions.length === 0) {
    body.innerHTML = `<tr><td colspan="6" class="muted" style="text-align:center; padding:20px;">No transactions found.</td></tr>`;
  }
}


async function saveMonthStart() {
  $("msg").textContent = "";
  const month_key = $("monthKey").value.trim();
  const starting_money = Number($("startMoney").value);

  await api("/month_start.php", {
    method: "POST",
    body: { month_key, starting_money }
  });

  $("msg").textContent = "Month starting money saved.";
  toast("success", "Saved", "Month starting money updated.");
  $("msg").className = "success";
  await loadReport();
}
// Helper to extract YYYY-MM from YYYY-MM-DD
function getMonthFromDate(dateStr) {
  if (!dateStr) return null;
  return dateStr.substring(0, 7); // "2025-01-18" -> "2025-01"
}

// Helper for button loading state
function setBtnLoading(btnId, isLoading, originalText = "") {
  const btn = document.getElementById(btnId);
  if (!btn) return;

  if (isLoading) {
    btn.dataset.originalText = btn.innerHTML; // save full HTML (icon + text)
    btn.disabled = true;
    btn.innerHTML = `<span class="btn-spinner"></span> Processing…`;
    btn.classList.add("loading");
  } else {
    btn.disabled = false;
    btn.classList.remove("loading");
    if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText;
  }
}

async function addIncome() {
  $("msg").textContent = "";

  const dateVal = $("txDate_inc").value;
  if (!dateVal) return toast("error", "Missing Date", "Please select a date.");

  const month_key = getMonthFromDate(dateVal);

  const body = {
    month_key,
    tx_date: dateVal,
    type: "income",
    category: $("incCategory").value.trim(),
    note: $("incNote").value.trim(),
    amount: Number($("incAmount").value)
  };

  try {
    setBtnLoading("addIncomeBtn", true);
    await api("/transaction_add.php", { method: "POST", body });

    $("msg").textContent = "Income added.";
    toast("success", "Added", "Income added successfully.");
    $("msg").className = "success";

    $("incAmount").value = "";
    $("incNote").value = "";

    await loadReport();
    await loadTransactions();
  } catch (e) {
    toast("error", "Error", e.message);
  } finally {
    setBtnLoading("addIncomeBtn", false);
  }
}

async function addExpense() {
  $("msg").textContent = "";

  const dateVal = $("txDate_exp").value;
  if (!dateVal) return toast("error", "Missing Date", "Please select a date.");

  const month_key = getMonthFromDate(dateVal);

  const body = {
    month_key,
    tx_date: dateVal,
    type: "expense",
    category: $("expCategory").value.trim(),
    note: $("expNote").value.trim(),
    amount: Number($("expAmount").value)
  };

  try {
    setBtnLoading("addExpenseBtn", true);
    await api("/transaction_add.php", { method: "POST", body });

    $("msg").textContent = "Expense added.";
    toast("success", "Added", "Expense added successfully.");
    $("msg").className = "success";

    $("expAmount").value = "";
    $("expNote").value = "";

    await loadReport();
    await loadTransactions();
  } catch (e) {
    toast("error", "Error", e.message);
  } finally {
    setBtnLoading("addExpenseBtn", false);
  }
}

async function addDebt() {
  $("msg").textContent = "";

  const body = {
    name: $("debtName").value.trim(),
    original_amount: Number($("debtAmount").value),
    note: $("debtNote").value.trim()
  };

  try {
    setBtnLoading("addDebtBtn", true);
    await api("/debt_create.php", { method: "POST", body });

    $("msg").textContent = "Debt created.";
    $("msg").className = "success";
    toast("success", "Created", "Debt created successfully.");

    $("debtName").value = "";
    $("debtAmount").value = "";
    $("debtNote").value = "";

    await loadDebtsIntoSelect();
    await loadReport();
  } catch (e) {
    toast("error", "Error", e.message);
  } finally {
    setBtnLoading("addDebtBtn", false);
  }
}

async function addDebtPayment() {
  $("msg").textContent = "";

  const dateVal = $("txDate_pay").value;
  if (!dateVal) return toast("error", "Missing Date", "Please select a date.");

  const month_key = getMonthFromDate(dateVal);

  const body = {
    month_key,
    tx_date: dateVal,
    type: "debt_payment",
    category: "Debt",
    note: $("payNote").value.trim(),
    amount: Number($("payAmount").value),
    debt_id: Number($("debtSelect").value)
  };

  try {
    setBtnLoading("addPayBtn", true);
    await api("/transaction_add.php", { method: "POST", body });

    $("msg").textContent = "Debt payment added.";
    toast("success", "Added", "Debt payment added successfully.");
    $("msg").className = "success";

    $("payAmount").value = "";
    $("payNote").value = "";

    await loadDebtsIntoSelect();
    await loadReport();
    await loadTransactions();
  } catch (e) {
    toast("error", "Error", e.message);
  } finally {
    setBtnLoading("addPayBtn", false);
  }
}

async function logout() {
  await api("/logout.php", { method: "POST" });
  window.location.href = "./login.html";
}

// Page wiring
window.addEventListener("DOMContentLoaded", async () => {
  if ($("loginPage")) {
    // Professional login/register UI wiring (single button, tab switch)
    const tabLogin = document.getElementById("tabLogin");
    const tabRegister = document.getElementById("tabRegister");
    const nameField = document.getElementById("nameField");
    const loginBtn = document.getElementById("loginBtn");
    const loginMsg = document.getElementById("loginMsg");
    const togglePass = document.getElementById("togglePass");
    const yearEl = document.getElementById("year");

    if (yearEl) yearEl.textContent = new Date().getFullYear();

    let mode = "login"; // login | register

    function setMode(next) {
      mode = next;
      if (loginMsg) loginMsg.textContent = "";
      tabLogin?.classList.toggle("active", mode === "login");
      tabRegister?.classList.toggle("active", mode === "register");
      if (nameField) nameField.style.display = mode === "register" ? "" : "none";
      loginBtn?.querySelector(".btn-text") && (loginBtn.querySelector(".btn-text").textContent =
        mode === "login" ? "Login" : "Create account");
    }

    tabLogin?.addEventListener("click", () => setMode("login"));
    tabRegister?.addEventListener("click", () => setMode("register"));
    // --- Google Login ---
    const googleBtn = document.getElementById("google-login");

    googleBtn?.addEventListener("click", async () => {
      try {
        showAuthMessage("");

        const result = await window.firebaseAuth.signInWithPopup(window.googleProvider);
        const user = result.user;

        // Get Firebase ID token

        const id_token = await user.getIdToken(true);
        console.log("ID TOKEN (first 30 chars):", id_token.slice(0, 30));

        // Send token to PHP to create session
        await api("/google_login.php", {
          method: "POST",
          body: { id_token }
        });

        // Now session exists -> dashboard APIs work
        window.location.href = "/expenses-app/web/dashboard.php";

      } catch (e) {
        console.error(e);
        showAuthMessage(e.message || "Google login failed.");
      }
    });


    togglePass?.addEventListener("click", () => {
      const p = document.getElementById("password");
      if (!p) return;

      const isHidden = p.type === "password";
      p.type = isHidden ? "text" : "password";
      togglePass.textContent = isHidden ? "Hide" : "Show";

      const mascot = document.getElementById("mascot");
      if (mascot) {
        if (isHidden) {
          // SHOW password -> show hide-eyes.png
          mascot.classList.add("show-pass");
        } else {
          // HIDE password -> show money.png
          mascot.classList.remove("show-pass");

          // reset pupils
          mascot.querySelectorAll(".pupil").forEach(pu => {
            pu.style.transform = "translate(0,0)";
          });
        }
      }

    });



    loginBtn?.addEventListener("click", async () => {
      if (!loginBtn) return;

      loginBtn.classList.add("loading");
      try {
        if (mode === "login") {
          await handleLogin();
        } else {
          await handleRegister();
        }
      } catch (e) {
        // handleLogin/handleRegister already write to loginMsg via api() errors
        // but keep this as safety:
        const msg = e?.message || "Action failed";
        if (loginMsg) {
          loginMsg.textContent = msg;
          loginMsg.className = "auth-msg";
        }
      } finally {
        loginBtn.classList.remove("loading");
      }
    });
    // Mascot eyes follow mouse
    const mascot = document.getElementById("mascot");
    const pupils = mascot ? mascot.querySelectorAll(".pupil") : [];

    function movePupils(clientX, clientY) {
      if (!mascot || mascot.classList.contains("closed")) return;

      const rect = mascot.getBoundingClientRect();
      const mx = clientX - (rect.left + rect.width / 2);
      const my = clientY - (rect.top + rect.height / 2);

      // Limit movement
      const max = 8;
      const dx = Math.max(-max, Math.min(max, mx / 25));
      const dy = Math.max(-max, Math.min(max, my / 25));

      pupils.forEach(p => {
        // If peeking (password visible), keep the look-away transform
        if (mascot.classList.contains("peek")) return;
        p.style.transform = `translate(${dx}px, ${dy}px)`;
      });
    }

    window.addEventListener("mousemove", (e) => movePupils(e.clientX, e.clientY));

    // default
    setMode("login");
    return;
  }


  if ($("dashPage")) {
    await ensureAuthOrRedirect();

    $("monthKey").value = monthKeyNow();
    $("txDate").value = dateToday();
    // If a page has monthKey/txDate fields, set defaults safely
    if ($("monthKey")) $("monthKey").value = monthKeyNow();
    if ($("txDate")) $("txDate").value = dateToday();


    $("logoutBtn").addEventListener("click", (e) => {
      e.preventDefault();
      openLogoutModal();
    });

    $("refreshBtn").addEventListener("click", loadReport);
    $("saveMonthBtn").addEventListener("click", saveMonthStart);
    if ($("addExpenseBtn")) $("addExpenseBtn").addEventListener("click", addExpense);
    if ($("addIncomeBtn")) $("addIncomeBtn").addEventListener("click", addIncome);
    if ($("addDebtBtn")) $("addDebtBtn").addEventListener("click", addDebt);
    if ($("addPayBtn")) $("addPayBtn").addEventListener("click", addDebtPayment);
    if (document.getElementById("aiSendBtn")) {
      document.getElementById("aiSendBtn").addEventListener("click", sendAiMessage);
    }

    // AI tools
    document.getElementById("aiReloadBtn")?.addEventListener("click", loadAiHistory);

    document.getElementById("aiClearBtn")?.addEventListener("click", async () => {
      const month_key = (document.getElementById("monthKey")?.value || "").trim() || monthKeyNow();

      await api("/ai_clear.php", { method: "POST", body: { month_key } });

      const wrap = document.getElementById("chatWrap");
      if (wrap) wrap.innerHTML = `<div class="chat-hint">Chat cleared. Ask me anything!</div>`;
    });


    if (document.getElementById("aiPrompt")) {
      document.getElementById("aiPrompt").addEventListener("keydown", (e) => {
        if (e.key === "Enter") sendAiMessage();
      });
    }



    if ($("txApplyBtn")) $("txApplyBtn").addEventListener("click", loadTransactions);

    // Wire up Export button
    if ($("txExportBtn")) {
      $("txExportBtn").addEventListener("click", () => {
        if (!TX_CACHE || TX_CACHE.length === 0) return toast("error", "No Data", "Nothing to export.");

        let csv = "Date,Type,Category,Note,Amount\n";
        TX_CACHE.forEach(t => {
          const safeNote = (t.note || "").replace(/,/g, " ");
          const type = t.type === "debt_payment" ? "debt_payment" : t.type;
          csv += `${t.tx_date},${type},${t.category},${safeNote},${t.amount}\n`;
        });

        const blob = new Blob([csv], { type: "text/csv" });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `transactions_${monthKeyNow()}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
      });
    }

    // Wire up Add New button (switches to Add Expense tab)
    if ($("txAddNewBtn")) {
      $("txAddNewBtn").addEventListener("click", () => {
        // Simulate click on the "Add Expense" sidebar link
        const link = document.querySelector('a[data-tab="tab-add-expense"]');
        if (link) link.click();
        // Also open the submenu if needed
        const parent = document.querySelector('.nav-parent[data-parent="transactions"]');
        const children = document.getElementById("nav-transactions");
        if (parent && children && !children.classList.contains("open")) {
          parent.classList.add("open");
          children.classList.add("open");
        }
      });
    }

    // Modal wiring
    document.getElementById("modalCloseBtn")?.addEventListener("click", closeModal);
    document.getElementById("modalCancelBtn")?.addEventListener("click", closeModal);
    document.getElementById("modalBackdrop")?.addEventListener("click", closeModal);
    document.getElementById("mType")?.addEventListener("change", setDebtBoxVisibility);
    document.getElementById("modalSaveBtn")?.addEventListener("click", saveModalEdit);


    // Delete modal wiring
    document.getElementById("delCloseBtn")?.addEventListener("click", closeDeleteModal);
    document.getElementById("delCancelBtn")?.addEventListener("click", closeDeleteModal);
    document.getElementById("deleteBackdrop")?.addEventListener("click", closeDeleteModal);
    // Logout modal wiring
    document.getElementById("logoutCloseBtn")?.addEventListener("click", closeLogoutModal);
    document.getElementById("logoutCancelBtn")?.addEventListener("click", closeLogoutModal);
    document.getElementById("logoutBackdrop")?.addEventListener("click", closeLogoutModal);

    document.getElementById("logoutConfirmBtn")?.addEventListener("click", async () => {
      const btn = document.getElementById("logoutConfirmBtn");
      const textEl = btn?.querySelector(".btn-text");

      try {
        if (btn) {
          btn.classList.add("is-loading");
          btn.disabled = true;
        }
        if (textEl) textEl.textContent = "Logging out…";

        await logout(); // your existing function -> calls API then redirects
      } catch (e) {
        // If something fails, restore button
        if (textEl) textEl.textContent = "Yes, log out";
        if (btn) {
          btn.classList.remove("is-loading");
          btn.disabled = false;
        }
        // Optional toast
        toast?.("error", "Logout failed", e.message || "Please try again.");
      }
    });



    document.getElementById("delConfirmBtn")?.addEventListener("click", async () => {
      if (!PENDING_DELETE_ID) return;

      try {
        document.getElementById("delConfirmBtn").disabled = true;

        await api("/transaction_delete.php", {
          method: "POST",
          body: { id: PENDING_DELETE_ID }
        });

        closeDeleteModal();
        toast("success", "Deleted", "Transaction removed successfully.");
        await loadDebtsIntoSelect();
        await loadReport();
        await loadTransactions();
      } catch (e) {
        toast("error", "Delete failed", e.message);
      } finally {
        document.getElementById("delConfirmBtn").disabled = false;
      }
    });


    // Edit / Delete actions (event delegation)
    if ($("txBody")) {
      $("txBody").addEventListener("click", async (e) => {
        const btn = e.target.closest("button[data-action]");
        if (!btn) return;

        const id = Number(btn.dataset.id);
        const action = btn.dataset.action;

        if (action === "delete") {
          // open custom modal instead of browser confirm
          openDeleteModal(id, "Delete this transaction?");
        }


        if (action === "edit") {
          startEditTransaction(id);
        }

      });
    }

    await loadDebtsIntoSelect();
    await loadReport();
    await loadTransactions();
    if (document.getElementById("tab-ai")) {
      loadAiHistory();
    }
  }
});
