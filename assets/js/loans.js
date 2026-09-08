document.addEventListener('DOMContentLoaded', function() {
    console.log("Loans JS initialized.");

    // Hook into the global filter change event if it exists, or just load data on boot
    const filterMember = document.getElementById('filter-member');
    
    // Override the dashboard's fetchDashboardData to also fetch Loan data
    const originalFetchDashboardData = window.fetchDashboardData;
    if (originalFetchDashboardData) {
        window.fetchDashboardData = async function() {
            await originalFetchDashboardData();
            await loadLoanDashboardData();
        };
    }

    async function loadLoanDashboardData() {
        try {
            const member_id = filterMember ? filterMember.value : 'ALL';
            
            // 1. Fetch Loans
            const loanResult = await window.apiFetch('loans.php');
            let loans = [];
            if (loanResult && loanResult.success && loanResult.data) {
                loans = loanResult.data.filter(l => member_id === 'ALL' || l.family_member_id === member_id);
            }

            // 2. Fetch EMIs
            const emiResult = await window.apiFetch('emi.php');
            let emis = [];
            if (emiResult && emiResult.success && emiResult.data) {
                emis = emiResult.data.filter(e => member_id === 'ALL' || e.family_member_id === member_id);
            }

            renderLoanDashboard(loans, emis);
            populateLoanDropdown(loans);
            
        } catch (e) {
            console.error("Error loading loan dashboard data:", e);
        }
    }

    function renderLoanDashboard(loans, emis) {
        // Calculate KPIs
        let totalOutstanding = 0;
        let totalMonthlyEmi = 0;
        let totalPrincipalPaid = 0;
        let totalInterestPaid = 0;

        const activeLoans = loans.filter(l => l.status === 'Active');

        activeLoans.forEach(l => {
            totalOutstanding += parseFloat(l.outstanding_principal || 0);
            totalMonthlyEmi += parseFloat(l.emi_amount || 0);
            totalPrincipalPaid += parseFloat(l.principal_paid || 0);
            totalInterestPaid += parseFloat(l.interest_paid || 0);
        });

        // Update KPI UI
        const kpiOutstanding = document.getElementById('loan-kpi-outstanding');
        if (kpiOutstanding) kpiOutstanding.textContent = window.formatINR(totalOutstanding);

        const kpiMonthlyEmi = document.getElementById('loan-kpi-monthly-emi');
        if (kpiMonthlyEmi) kpiMonthlyEmi.textContent = window.formatINR(totalMonthlyEmi);

        const kpiPrincipal = document.getElementById('loan-kpi-principal-paid');
        if (kpiPrincipal) kpiPrincipal.textContent = window.formatINR(totalPrincipalPaid);

        const kpiInterest = document.getElementById('loan-kpi-interest-paid');
        if (kpiInterest) kpiInterest.textContent = window.formatINR(totalInterestPaid);

        // Render Active Loans Table
        const activeTbody = document.getElementById('activeLoansTableBody');
        if (activeTbody) {
            if (activeLoans.length === 0) {
                activeTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;" class="text-muted">No active loans found.</td></tr>';
            } else {
                activeTbody.innerHTML = '';
                activeLoans.forEach(l => {
                    const originalPrincipal = parseFloat(l.principal_amount || 0);
                    const principalPaid = parseFloat(l.principal_paid || 0);
                    const progress = originalPrincipal > 0 ? (principalPaid / originalPrincipal) * 100 : 0;
                    
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-color)';
                    tr.innerHTML = `
                        <td style="padding: 10px;">
                            <strong>${l.loan_name}</strong><br>
                            <small class="text-muted">${l.lender}</small>
                        </td>
                        <td style="padding: 10px;">${l.family_member_id}</td>
                        <td style="padding: 10px; font-weight: bold;">${window.formatINR(l.emi_amount)}</td>
                        <td style="padding: 10px; color: var(--danger-color);">${window.formatINR(l.outstanding_principal)}</td>
                        <td style="padding: 10px;">
                            <div style="background: var(--border-color); border-radius: 4px; height: 10px; width: 100%; overflow: hidden; margin-bottom: 5px;">
                                <div style="background: var(--success-color); height: 100%; width: ${progress}%;"></div>
                            </div>
                            <small class="text-muted">${progress.toFixed(1)}% repaid</small>
                        </td>
                        <td style="padding: 10px;">${l.next_emi_date || '-'}</td>
                    `;
                    activeTbody.appendChild(tr);
                });
            }
        }

        // Render Recent/Upcoming EMIs
        const emiTbody = document.getElementById('emiHistoryTableBody');
        if (emiTbody) {
            if (emis.length === 0) {
                emiTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;" class="text-muted">No EMI history found.</td></tr>';
            } else {
                // Sort by most recent
                emis.sort((a, b) => new Date(b.payment_date) - new Date(a.payment_date));
                const recentEmis = emis.slice(0, 10);
                
                emiTbody.innerHTML = '';
                recentEmis.forEach(e => {
                    // Find loan name
                    const loan = loans.find(l => l.loan_id === e.loan_id);
                    const loanName = loan ? loan.loan_name : e.loan_id;
                    
                    let statusColor = 'var(--text-muted)';
                    if (e.status === 'Paid') statusColor = 'var(--success-color)';
                    else if (e.status === 'Missed') statusColor = 'var(--danger-color)';
                    else if (e.status === 'Pending') statusColor = 'var(--warning-color)';

                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-color)';
                    tr.innerHTML = `
                        <td style="padding: 10px;">${e.payment_date}</td>
                        <td style="padding: 10px;">${loanName}</td>
                        <td style="padding: 10px; font-weight: bold;">${window.formatINR(e.emi_amount)}</td>
                        <td style="padding: 10px; color: var(--success-color);">${window.formatINR(e.principal_component || 0)}</td>
                        <td style="padding: 10px; color: var(--secondary-color);">${window.formatINR(e.interest_component || 0)}</td>
                        <td style="padding: 10px;">
                            <span style="color: white; background: ${statusColor}; padding: 3px 8px; border-radius: 12px; font-size: 0.8em;">
                                ${e.status}
                            </span>
                        </td>
                    `;
                    emiTbody.appendChild(tr);
                });
            }
        }
    }

    function populateLoanDropdown(loans) {
        const select = document.querySelector('select[name="loan_id"]');
        if (select) {
            const currentVal = select.value;
            select.innerHTML = '<option value="">Select a Loan</option>';
            
            const activeLoans = loans.filter(l => l.status === 'Active');
            activeLoans.forEach(loan => {
                const opt = document.createElement('option');
                opt.value = loan.loan_id;
                opt.setAttribute('data-calc-method', loan.balance_calculation_method);
                opt.setAttribute('data-emi-amount', loan.emi_amount);
                opt.textContent = `${loan.loan_name} (${loan.family_member_id}) - ${window.formatINR(loan.emi_amount)}`;
                select.appendChild(opt);
            });
            if (currentVal) select.value = currentVal;
        }
    }

    // Dynamic Form Behavior for EMI recording
    const loanSelect = document.querySelector('select[name="loan_id"]');
    const manualFields = document.getElementById('manualEmiFields');
    const emiAmountInput = document.querySelector('input[name="emi_amount"]');

    if (loanSelect) {
        loanSelect.addEventListener('change', function() {
            const selectedOption = loanSelect.options[loanSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const method = selectedOption.getAttribute('data-calc-method');
                const amount = selectedOption.getAttribute('data-emi-amount');
                
                // Set default EMI amount
                if (emiAmountInput) emiAmountInput.value = amount;
                
                // Show/Hide manual fields
                if (method === 'MANUAL') {
                    if (manualFields) manualFields.style.display = 'block';
                } else {
                    if (manualFields) manualFields.style.display = 'none';
                }
            } else {
                if (manualFields) manualFields.style.display = 'none';
                if (emiAmountInput) emiAmountInput.value = '';
            }
        });
    }

    // Boot loader if standalone (fallback if fetchDashboardData is not called)
    if (!originalFetchDashboardData) {
        loadLoanDashboardData();
    }
});
