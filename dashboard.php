<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Family Financial Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Finance Dashboard</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="#" data-modal-target="addIncomeModal">Income</a></li>
                <li><a href="#" data-modal-target="addExpenseModal">Expenses</a></li>
                <li><a href="#" data-modal-target="addLoanModal">Loans & EMI</a></li>
                <li><a href="#" data-modal-target="addAccountModal">Bank Accounts</a></li>
                <li><a href="#" data-modal-target="addInvestmentModal">Investments</a></li>
                <li><a href="#" data-modal-target="addAssetModal">Assets</a></li>
                <li><a href="#" data-modal-target="addBudgetModal">Budget</a></li>
                <li><a href="#" data-modal-target="addGoalModal">Goals</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn">☰</button>
                <div class="search-box">
                    <input type="text" placeholder="Search..." class="form-control" style="width: 250px;">
                </div>
            </div>
            <div class="topbar-right">
                <span>Month: Sep 2026</span>
                <span>Profile</span>
            </div>
        </header>

        <!-- Dashboard Body -->
        <div class="dashboard-body">
            <!-- Top Filters -->
            <div class="dashboard-filters" style="display: flex; gap: 15px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); align-items: center;">
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Family Member</label>
                    <select id="filter-member" class="form-control">
                        <option value="ALL">All Family</option>
                        <!-- Dynamic options here -->
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Month</label>
                    <select id="filter-month" class="form-control">
                        <option value="01">January</option>
                        <option value="02">February</option>
                        <option value="03">March</option>
                        <option value="04">April</option>
                        <option value="05">May</option>
                        <option value="06">June</option>
                        <option value="07">July</option>
                        <option value="08">August</option>
                        <option value="09" selected>September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Year</label>
                    <select id="filter-year" class="form-control">
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                    </select>
                </div>
                <div style="flex: 1; align-self: flex-end;">
                    <button class="btn btn-primary" id="btn-apply-filters" style="width: 100%;">Apply</button>
                </div>
            </div>

            <div class="page-header">
                <div>
                    <h1>Family Financial Dashboard</h1>
                    <p class="text-muted">Currently Viewing: <strong id="viewing-label">All Family (Sep 2026)</strong></p>
                </div>
                <div>
                    <button class="btn btn-primary" data-modal-target="addMemberModal">+ Add Member</button>
                    <button class="btn btn-primary" data-modal-target="addIncomeModal">+ Add Income</button>
                    <button class="btn btn-primary bg-danger" data-modal-target="addExpenseModal">+ Add Expense</button>
                    <button class="btn btn-primary bg-warning" data-modal-target="addLoanModal">+ Add Loan</button>
                    <button class="btn btn-primary bg-success" data-modal-target="addEmiModal">Record EMI</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addAccountModal">+ Add Account</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addTransactionModal">Record Txn</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addInvestmentModal">+ Investment</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addAssetModal">+ Asset</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addBudgetModal">Set Budget</button>
                    <button class="btn btn-primary bg-secondary text-primary" data-modal-target="addGoalModal">+ Goal</button>
                </div>
            </div>

            <!-- KPI Grid (Dynamic) -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-card-title">Income</div>
                    <div class="kpi-card-value" id="kpi-income">Loading...</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card-title">Expenses</div>
                    <div class="kpi-card-value" id="kpi-expenses">Loading...</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card-title">EMI Paid</div>
                    <div class="kpi-card-value" id="kpi-emi">Loading...</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card-title">Savings</div>
                    <div class="kpi-card-value" id="kpi-savings">Loading...</div>
                    <div class="kpi-card-trend text-muted" id="kpi-savings-rate">Rate: -</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card-title">Outstanding Debt</div>
                    <div class="kpi-card-value" id="kpi-debt">Loading...</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-card-title">Net Worth</div>
                    <div class="kpi-card-value" id="kpi-net-worth">Loading...</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div class="section-header">
                        <h3>Income vs Expenses (This Month)</h3>
                    </div>
                    <canvas id="cashflowChart"></canvas>
                </div>
                
                <div class="chart-container" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <div class="section-header">
                        <h3>Expense Breakdown</h3>
                    </div>
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="dashboard-section" style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="section-header">
                    <h3>Recent Activity</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Type</th>
                                <th style="padding: 10px;">Description</th>
                                <th style="padding: 10px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivityTableBody">
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;" class="text-muted">Loading activity...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Member Comparison Table -->
            <div class="dashboard-section member-comparison-section" style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="section-header">
                    <h3>Member Comparison</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 10px;">Member</th>
                                <th style="padding: 10px;">Income</th>
                                <th style="padding: 10px;">Expenses</th>
                                <th style="padding: 10px;">EMI</th>
                                <th style="padding: 10px;">Savings</th>
                                <th style="padding: 10px;">Debt</th>
                                <th style="padding: 10px;">Net Worth</th>
                            </tr>
                        </thead>
                        <tbody id="memberComparisonTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;" class="text-muted">Loading comparison...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Loan & EMI Dashboard Section -->
            <div class="dashboard-section loan-dashboard-section" style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Loan & EMI Management</h3>
                    <div>
                        <button class="btn btn-primary" data-modal-target="addLoanModal">+ Add Loan</button>
                        <button class="btn btn-secondary" data-modal-target="addEmiModal">+ Record EMI</button>
                    </div>
                </div>
                
                <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--warning-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em; color: var(--text-color);">Total Outstanding</h4>
                        <div class="value" id="loan-kpi-outstanding" style="font-size: 1.4em; font-weight: bold; color: var(--danger-color);">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--danger-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em; color: var(--text-color);">Total Monthly EMI</h4>
                        <div class="value" id="loan-kpi-monthly-emi" style="font-size: 1.4em; font-weight: bold; color: var(--warning-color);">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--success-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em; color: var(--text-color);">Principal Repaid</h4>
                        <div class="value" id="loan-kpi-principal-paid" style="font-size: 1.4em; font-weight: bold; color: var(--success-color);">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--secondary-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em; color: var(--text-color);">Interest Paid</h4>
                        <div class="value" id="loan-kpi-interest-paid" style="font-size: 1.4em; font-weight: bold; color: var(--text-color);">₹0</div>
                    </div>
                </div>

                <h4 style="margin-bottom: 10px;">Active Loans</h4>
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--border-color); background: var(--light-bg);">
                                <th style="padding: 10px;">Loan</th>
                                <th style="padding: 10px;">Member</th>
                                <th style="padding: 10px;">EMI</th>
                                <th style="padding: 10px;">Outstanding</th>
                                <th style="padding: 10px;">Progress</th>
                                <th style="padding: 10px;">Next EMI</th>
                            </tr>
                        </thead>
                        <tbody id="activeLoansTableBody">
                            <tr><td colspan="6" style="text-align: center; padding: 20px;" class="text-muted">Loading active loans...</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4 style="margin-bottom: 10px;">Upcoming & Recent EMIs</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--border-color); background: var(--light-bg);">
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Loan</th>
                                <th style="padding: 10px;">EMI Amount</th>
                                <th style="padding: 10px;">Principal</th>
                                <th style="padding: 10px;">Interest</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="emiHistoryTableBody">
                            <tr><td colspan="6" style="text-align: center; padding: 20px;" class="text-muted">Loading EMI history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Become Debt-Free Section -->
            <div class="dashboard-section debt-free-section" style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 2px solid var(--primary-color);">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="color: var(--primary-color);">🌟 Become Debt-Free Analyzer</h3>
                    <button class="btn btn-secondary" data-modal-target="settingsModal">⚙️ Analyzer Settings</button>
                </div>
                
                <!-- Financial Snapshot -->
                <h4>Financial Snapshot</h4>
                <div class="kpi-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--success-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em;">Monthly Income</h4>
                        <div class="value" id="df-kpi-income" style="font-size: 1.4em; font-weight: bold;">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--warning-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em;">Essential Expenses</h4>
                        <div class="value" id="df-kpi-essential" style="font-size: 1.4em; font-weight: bold;">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--danger-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em;">Discretionary Expenses</h4>
                        <div class="value" id="df-kpi-discretionary" style="font-size: 1.4em; font-weight: bold;">₹0</div>
                    </div>
                    <div class="kpi-card" style="background: var(--light-bg); border-left: 4px solid var(--primary-color); padding: 15px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 0.9em;">Free Cash Flow</h4>
                        <div class="value" id="df-kpi-fcf" style="font-size: 1.4em; font-weight: bold;">₹0</div>
                    </div>
                </div>

                <!-- Ratios & Progress -->
                <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                    <div style="flex: 1; padding: 15px; background: var(--light-bg); border-radius: 8px;">
                        <h4>Savings Rate</h4>
                        <div id="df-savings-rate" style="font-size: 2em; font-weight: bold;">0%</div>
                        <div id="df-savings-status" style="font-size: 0.9em; margin-top: 5px;">Analyzing...</div>
                    </div>
                    <div style="flex: 1; padding: 15px; background: var(--light-bg); border-radius: 8px;">
                        <h4>EMI-to-Income Ratio</h4>
                        <div id="df-emi-ratio" style="font-size: 2em; font-weight: bold;">0%</div>
                        <div id="df-emi-status" style="font-size: 0.9em; margin-top: 5px;">Analyzing...</div>
                    </div>
                    <div style="flex: 1; padding: 15px; background: var(--light-bg); border-radius: 8px;">
                        <h4>Emergency Fund Progress</h4>
                        <div id="df-emergency-progress" style="font-size: 2em; font-weight: bold;">0%</div>
                        <div id="df-emergency-status" style="font-size: 0.9em; margin-top: 5px;">Target: ₹0</div>
                    </div>
                </div>

                <!-- Recommendations Engine -->
                <h4>Insights & Recommendations</h4>
                <div id="recommendations-container" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
                    <div style="padding: 15px; background: var(--light-bg); border-radius: 8px; color: var(--text-muted);">
                        Loading AI recommendations based on your data...
                    </div>
                </div>

                <!-- Investment vs Debt Analysis -->
                <h4>Investment vs Debt Analysis</h4>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                    <p style="margin: 0 0 10px 0;"><strong>Should I invest or pay off debt?</strong></p>
                    <p style="margin: 0 0 10px 0; color: var(--text-muted); font-size: 0.9em;">
                        This is a neutral comparison. Mathematically, if your highest debt interest rate (e.g., <span id="df-highest-debt-rate">0</span>%) is greater than your expected investment return after taxes, paying off debt offers a guaranteed higher return. However, maintaining investments provides liquidity and potential long-term compounding. This is a personal decision based on your risk tolerance.
                    </p>
                </div>

                <!-- Debt-Free Simulator & Scenarios -->
                <h4>Debt-Free Simulator</h4>
                <div style="background: var(--light-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <p><strong>Selected Strategy:</strong> <span id="df-strategy-label">Avalanche</span></p>
                    <p><strong>Base Extra Capacity:</strong> <span id="df-extra-capacity" style="color: var(--success-color); font-weight: bold;">₹0</span>/mo</p>
                    
                    <div style="margin-top: 20px;">
                        <h5>Scenario Analysis</h5>
                        <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 10px;">Scenario</th>
                                    <th style="padding: 10px;">Extra / Mo</th>
                                    <th style="padding: 10px;">Months to Freedom</th>
                                    <th style="padding: 10px;">Total Interest</th>
                                    <th style="padding: 10px;">Interest Saved</th>
                                </tr>
                            </thead>
                            <tbody id="df-scenariosBody">
                                <tr><td colspan="5" style="text-align: center; padding: 10px;">Loading scenarios...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 30px; overflow-x: auto;">
                        <h5>Monthly Roadmap (Balanced Scenario - Next 24 Months)</h5>
                        <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em;">
                            <thead>
                                <tr style="text-align: left; border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 10px;">Mth</th>
                                    <th style="padding: 10px;">Target Loan</th>
                                    <th style="padding: 10px;">Opening Debt</th>
                                    <th style="padding: 10px;">Regular EMI</th>
                                    <th style="padding: 10px;">Extra Payment</th>
                                    <th style="padding: 10px;">Interest</th>
                                    <th style="padding: 10px;">Principal Repaid</th>
                                    <th style="padding: 10px;">Closing Debt</th>
                                </tr>
                            </thead>
                            <tbody id="df-roadmapBody">
                                <tr><td colspan="8" style="text-align: center; padding: 10px;">Loading roadmap...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Income Modal -->
<div class="modal-overlay" id="addIncomeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Income</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addIncomeForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Income Source</label>
                    <input type="text" name="income_source" class="form-control" placeholder="e.g. TechCorp Inc." required>
                </div>
                <div class="form-group">
                    <label>Income Type</label>
                    <select name="income_type" class="form-control" required>
                        <option value="Salary">Salary</option>
                        <option value="Business">Business</option>
                        <option value="Pension">Pension</option>
                        <option value="Freelance">Freelance</option>
                        <option value="Interest">Interest</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Recurring?</label>
                    <select name="recurring" class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Income</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal-overlay" id="addExpenseModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Expense</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addExpenseForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select-expense" required>
                        <option value="">Select Member</option>
                        <option value="FAMILY_SHARED">Shared Family Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control" required>
                        <option value="Rent">Rent</option>
                        <option value="Food">Food</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Education">Education</option>
                        <option value="Medical">Medical</option>
                        <option value="Transportation">Transportation</option>
                        <option value="EMI">EMI</option>
                        <option value="Insurance">Insurance</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Family">Family</option>
                        <option value="Business">Business</option>
                        <option value="Subscriptions">Subscriptions</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="What was this for?">
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <input type="text" name="payment_method" class="form-control" placeholder="e.g. Credit Card">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Loan Modal -->
<div class="modal-overlay" id="addLoanModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Add New Loan</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addLoanForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loan Name</label>
                    <input type="text" name="loan_name" class="form-control" placeholder="e.g. Home Loan" required>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Lender (Bank Name)</label>
                        <input type="text" name="lender" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Loan Type</label>
                        <select name="loan_type" class="form-control" required>
                            <option value="Personal Loan">Personal Loan</option>
                            <option value="Home Loan">Home Loan</option>
                            <option value="Vehicle Loan">Vehicle Loan</option>
                            <option value="Gold Loan">Gold Loan</option>
                            <option value="Education Loan">Education Loan</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Principal Amount (₹)</label>
                        <input type="number" step="0.01" name="principal_amount" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>EMI Amount (₹)</label>
                        <input type="number" step="0.01" name="emi_amount" class="form-control" required>
                    </div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Interest Rate (%)</label>
                        <input type="number" step="0.01" name="interest_rate" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Interest Type</label>
                        <select name="interest_type" class="form-control" required>
                            <option value="Reducing">Reducing</option>
                            <option value="Flat">Flat</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Tenure (Months)</label>
                        <input type="number" name="tenure_months" class="form-control" required>
                    </div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>First EMI Date</label>
                        <input type="date" name="next_emi_date" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Calculation Method</label>
                    <select name="balance_calculation_method" class="form-control" required>
                        <option value="AMORTIZATION">Amortization (Auto-calculate Interest)</option>
                        <option value="MANUAL">Manual (I will enter Principal/Interest splits)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Loan</button>
            </div>
        </form>
    </div>
</div>

<!-- Record EMI Modal -->
<div class="modal-overlay" id="addEmiModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Record EMI Payment</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addEmiForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Loan</label>
                    <select name="loan_id" class="form-control" required>
                        <option value="">Loading loans...</option>
                    </select>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Total EMI Amount (₹)</label>
                        <input type="number" step="0.01" name="emi_amount" class="form-control" required>
                    </div>
                </div>
                
                <div id="manualEmiFields" style="display: none; background: var(--light-bg); padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                    <p style="font-size: 0.9em; margin-top: 0; color: var(--text-muted);">Manual Calculation Mode</p>
                    <div style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Principal Component (₹)</label>
                            <input type="number" step="0.01" name="principal_component" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Interest Component (₹)</label>
                            <input type="number" step="0.01" name="interest_component" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                        <option value="Missed">Missed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash">Cash</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <input type="text" name="notes" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Record EMI</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal-overlay" id="settingsModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Analyzer Settings</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="analyzerSettingsForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Debt Payoff Strategy</label>
                    <select name="debt_strategy" class="form-control" required>
                        <option value="avalanche">Avalanche (Highest Interest First)</option>
                        <option value="snowball">Snowball (Smallest Balance First)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Emergency Fund Target (Months)</label>
                    <input type="number" step="0.5" name="emergency_fund_target_months" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Minimum Savings Allocation (₹/mo)</label>
                    <input type="number" step="1" name="minimum_savings_allocation" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Minimum Investment Allocation (₹/mo)</label>
                    <input type="number" step="1" name="minimum_investment_allocation" class="form-control" required>
                </div>
                <!-- Note: Expense classification mapping could be a complex dynamic UI, keeping it simple here for the backend -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal-overlay" id="addAccountModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Bank Account</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addAccountForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" placeholder="e.g. HDFC Bank" required>
                </div>
                <div class="form-group">
                    <label>Account Name / Alias</label>
                    <input type="text" name="account_name" class="form-control" placeholder="e.g. Primary Savings" required>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type" class="form-control" required>
                        <option value="Savings">Savings</option>
                        <option value="Current">Current</option>
                        <option value="Salary">Salary</option>
                        <option value="FD">Fixed Deposit</option>
                        <option value="RD">Recurring Deposit</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Opening Balance (₹)</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Record Transaction Modal -->
<div class="modal-overlay" id="addTransactionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Record Transaction</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addTransactionForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Account ID</label>
                    <input type="text" name="account_id" class="form-control" placeholder="e.g. ACC-202609-XYZ" required>
                </div>
                <div class="form-group">
                    <label>Transaction Type</label>
                    <select name="type" class="form-control" required>
                        <option value="Transfer">Transfer</option>
                        <option value="Withdrawal">Withdrawal</option>
                        <option value="Deposit">Deposit</option>
                        <option value="Adjustment">Adjustment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. ATM Withdrawal">
                </div>
                <div class="form-group">
                    <label>Reference No. / UTR</label>
                    <input type="text" name="reference" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Record Transaction</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Investment Modal -->
<div class="modal-overlay" id="addInvestmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Investment</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addInvestmentForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Investment Type</label>
                    <select name="investment_type" class="form-control" required>
                        <option value="Stocks">Stocks</option>
                        <option value="Mutual Funds">Mutual Funds</option>
                        <option value="FD">Fixed Deposit (FD)</option>
                        <option value="RD">Recurring Deposit (RD)</option>
                        <option value="Gold">Gold (Paper/Digital)</option>
                        <option value="PPF">PPF</option>
                        <option value="EPF">EPF</option>
                        <option value="NPS">NPS</option>
                        <option value="Crypto">Crypto</option>
                        <option value="Business">Business</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Investment Name</label>
                    <input type="text" name="investment_name" class="form-control" placeholder="e.g. NIFTY 50 Index Fund" required>
                </div>
                <div class="form-group">
                    <label>Invested Amount (₹)</label>
                    <input type="number" step="0.01" name="invested_amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Current Value (₹) [Optional]</label>
                    <input type="number" step="0.01" name="current_value" class="form-control" placeholder="Leave blank if same as invested">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Investment</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal-overlay" id="addAssetModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Asset</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addAssetForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Asset Type</label>
                    <select name="asset_type" class="form-control" required>
                        <option value="Property">Property</option>
                        <option value="Vehicle">Vehicle</option>
                        <option value="Gold">Physical Gold</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Business">Business Asset</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Asset Name</label>
                    <input type="text" name="asset_name" class="form-control" placeholder="e.g. 2BHK Apartment" required>
                </div>
                <div class="form-group">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>Purchase Value (₹)</label>
                    <input type="number" step="0.01" name="purchase_value" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Current Estimated Value (₹) [Optional]</label>
                    <input type="number" step="0.01" name="current_value" class="form-control" placeholder="Leave blank if unknown">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Set Budget Modal -->
<div class="modal-overlay" id="addBudgetModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Set Monthly Budget</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addBudgetForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Budget Month (e.g. 2026-09)</label>
                    <input type="month" name="month" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control" required>
                        <option value="Rent">Rent</option>
                        <option value="Food">Food</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Education">Education</option>
                        <option value="Medical">Medical</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Insurance">Insurance</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Family">Family</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Budget Limit Amount (₹)</label>
                    <input type="number" step="0.01" name="budget_amount" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Budget</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Goal Modal -->
<div class="modal-overlay" id="addGoalModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Financial Goal</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addGoalForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Family Member</label>
                    <select name="family_member_id" class="form-control fm-select" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Goal Name</label>
                    <input type="text" name="goal_name" class="form-control" placeholder="e.g. Europe Vacation, New Car" required>
                </div>
                <div class="form-group">
                    <label>Target Amount (₹)</label>
                    <input type="number" step="0.01" name="target_amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Currently Saved (₹) [Optional]</label>
                    <input type="number" step="0.01" name="current_amount" class="form-control" placeholder="Default is 0">
                </div>
                <div class="form-group">
                    <label>Target Date [Optional]</label>
                    <input type="date" name="target_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Goal</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Family Member Modal -->
<div class="modal-overlay" id="addMemberModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Family Member</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="addMemberForm">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label>Relationship</label>
                    <select name="relationship" class="form-control" required>
                        <option value="Self">Self</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Child">Child</option>
                        <option value="Parent">Parent</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Member</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/dashboard.js"></script>
<script src="assets/js/loans.js"></script>
<script src="assets/js/analysis.js"></script>
<script src="assets/js/forms.js"></script>
</body>
</html>
