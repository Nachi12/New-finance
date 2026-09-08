/**
 * Dashboard specific scripts (Charts, data hydration, filters)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS initialized.");

    let cashflowChartInstance = null;
    let expenseChartInstance = null;
    
    // Bind filters
    const filterMember = document.getElementById('filter-member');
    const filterMonth = document.getElementById('filter-month');
    const filterYear = document.getElementById('filter-year');
    const btnApplyFilters = document.getElementById('btn-apply-filters');

    // Make fetch function available globally for forms.js to trigger refresh
    window.fetchDashboardData = async function() {
        const member_id = filterMember ? filterMember.value : 'ALL';
        const month = filterMonth ? filterMonth.value : '09';
        const year = filterYear ? filterYear.value : '2026';

        // Update viewing label
        const memberText = filterMember && filterMember.options[filterMember.selectedIndex] ? filterMember.options[filterMember.selectedIndex].text : 'All Family';
        const monthText = filterMonth && filterMonth.options[filterMonth.selectedIndex] ? filterMonth.options[filterMonth.selectedIndex].text : '';
        const viewingLabel = document.getElementById('viewing-label');
        if (viewingLabel) {
            viewingLabel.textContent = `${memberText} (${monthText} ${year})`;
        }

        try {
            const result = await window.apiFetch(`dashboard.php?member_id=${member_id}&month=${month}&year=${year}`);
            if (result && result.success) {
                updateSummaryCards(result.data.summary);
                initCharts(result.data);
                renderRecentActivity(result.data.recent_activity);
                renderMemberComparison(result.data.member_stats);
            } else {
                console.error("Failed to fetch dashboard data:", result?.error);
            }
        } catch (e) {
            console.error("Error fetching dashboard data:", e);
        }
    };

    window.populateDashboardMembers = async function() {
        if (!filterMember) return;
        try {
            const result = await window.apiFetch('members.php');
            if (result && result.success) {
                const members = result.data;
                const currentVal = filterMember.value;
                filterMember.innerHTML = '<option value="ALL">All Family</option>';
                members.forEach(m => {
                    if (m.status !== 'Inactive') {
                        const opt = document.createElement('option');
                        opt.value = m.family_member_id;
                        opt.textContent = `${m.name} (${m.relationship})`;
                        filterMember.appendChild(opt);
                    }
                });
                if (currentVal) filterMember.value = currentVal;
            }
        } catch (e) {
            console.error("Error fetching family members for filter:", e);
        }
    };

    if (btnApplyFilters) {
        btnApplyFilters.addEventListener('click', () => {
            window.fetchDashboardData();
        });
    }

    function updateSummaryCards(summary) {
        const incomeEl = document.getElementById('kpi-income');
        if (incomeEl) incomeEl.textContent = window.formatINR(summary.monthly_income);

        const expensesEl = document.getElementById('kpi-expenses');
        if (expensesEl) expensesEl.textContent = window.formatINR(summary.monthly_expenses);
        
        const emiEl = document.getElementById('kpi-emi');
        if (emiEl) emiEl.textContent = window.formatINR(summary.monthly_emi);

        const savingsEl = document.getElementById('kpi-savings');
        if (savingsEl) {
            savingsEl.textContent = window.formatINR(summary.monthly_savings);
            if (summary.monthly_savings < 0) {
                savingsEl.style.color = 'var(--danger-color)';
            } else {
                savingsEl.style.color = 'var(--success-color)';
            }
        }
        
        const savingsRateEl = document.getElementById('kpi-savings-rate');
        if (savingsRateEl) {
            savingsRateEl.textContent = `Rate: ${summary.savings_rate !== 'N/A' ? summary.savings_rate + '%' : 'N/A'}`;
        }

        const debtEl = document.getElementById('kpi-debt');
        if (debtEl) debtEl.textContent = window.formatINR(summary.total_debt);

        const netWorthEl = document.getElementById('kpi-net-worth');
        if (netWorthEl) netWorthEl.textContent = window.formatINR(summary.net_worth);
    }

    function initCharts(data) {
        if (typeof Chart === 'undefined') {
            console.warn("Chart.js not loaded.");
            return;
        }

        // 1. Income vs Expenses Bar Chart
        const ctxCashflow = document.getElementById('cashflowChart');
        if (ctxCashflow) {
            if (cashflowChartInstance) cashflowChartInstance.destroy();
            
            cashflowChartInstance = new Chart(ctxCashflow, {
                type: 'bar',
                data: {
                    labels: ['Income', 'Expenses', 'EMI'],
                    datasets: [{
                        label: 'Amount (₹)',
                        data: [data.summary.monthly_income, data.summary.monthly_expenses, data.summary.monthly_emi],
                        backgroundColor: [
                            'rgba(39, 174, 96, 0.7)', // Success Green
                            'rgba(231, 76, 60, 0.7)',  // Danger Red
                            'rgba(241, 196, 15, 0.7)' // Warning Yellow
                        ],
                        borderColor: [
                            'rgba(39, 174, 96, 1)',
                            'rgba(231, 76, 60, 1)',
                            'rgba(241, 196, 15, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // 2. Expense Breakdown Doughnut Chart
        const ctxExpense = document.getElementById('expenseChart');
        if (ctxExpense) {
            if (expenseChartInstance) expenseChartInstance.destroy();
            
            const expenseData = data.breakdowns.expenses_by_category || {};
            const labels = Object.keys(expenseData);
            const values = Object.values(expenseData);

            if (labels.length === 0) {
                // Dummy data if empty
                labels.push("No Data");
                values.push(1);
            }

            expenseChartInstance = new Chart(ctxExpense, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: [
                            '#3498db', '#9b59b6', '#f1c40f', '#e67e22', 
                            '#e74c3c', '#1abc9c', '#34495e', '#7f8c8d'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        }
    }

    function renderRecentActivity(activities) {
        const tbody = document.getElementById('recentActivityTableBody');
        if (!tbody) return;

        if (!activities || activities.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;" class="text-muted">No recent activity found.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        activities.forEach(item => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            
            // Color formatting for amount
            let amountColor = '';
            if (item.type === 'Income' || item.type === 'Deposit') amountColor = 'color: var(--success-color);';
            else if (item.type === 'Expense' || item.type === 'Withdrawal') amountColor = 'color: var(--danger-color);';
            
            tr.innerHTML = `
                <td style="padding: 10px;">${item.date}</td>
                <td style="padding: 10px;">
                    <span class="badge" style="background: var(--light-bg); padding: 4px 8px; border-radius: 12px; font-size: 0.85em;">
                        ${item.type}
                    </span>
                </td>
                <td style="padding: 10px;">${item.description || '-'}</td>
                <td style="padding: 10px; font-weight: bold; ${amountColor}">
                    ${window.formatINR(Math.abs(item.amount))}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    function renderMemberComparison(stats) {
        const tbody = document.getElementById('memberComparisonTableBody');
        if (!tbody) return;
        
        if (!stats || stats.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;" class="text-muted">No member comparison data available. Add family members first.</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        stats.forEach(s => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            tr.innerHTML = `
                <td style="padding: 10px; font-weight: bold;">${s.name}</td>
                <td style="padding: 10px; color: var(--success-color);">${window.formatINR(s.income)}</td>
                <td style="padding: 10px; color: var(--danger-color);">${window.formatINR(s.expenses)}</td>
                <td style="padding: 10px; color: var(--warning-color);">${window.formatINR(s.emi)}</td>
                <td style="padding: 10px; font-weight: 500;">${window.formatINR(s.savings)}</td>
                <td style="padding: 10px; color: var(--danger-color);">${window.formatINR(s.debt)}</td>
                <td style="padding: 10px; font-weight: bold;">${window.formatINR(s.net_worth)}</td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    // Initial fetch on page load
    window.populateDashboardMembers().then(() => {
        window.fetchDashboardData();
    });
});
