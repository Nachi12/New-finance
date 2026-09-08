document.addEventListener('DOMContentLoaded', function() {
    console.log("Analysis JS initialized.");

    const filterMember = document.getElementById('filter-member');

    // Hook into global fetchDashboardData
    const originalFetchDashboardData = window.fetchDashboardData;
    if (originalFetchDashboardData) {
        window.fetchDashboardData = async function() {
            await originalFetchDashboardData();
            await loadAnalysisData();
        };
    }

    // Load settings on boot
    loadSettings();

    // Setup Settings Form
    const settingsForm = document.getElementById('analyzerSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(settingsForm);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const res = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    alert('Analyzer Settings saved successfully!');
                    document.querySelector('#settingsModal .modal-close').click();
                    loadAnalysisData(); // Reload analysis
                } else {
                    alert('Error saving settings: ' + result.error);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while saving settings.');
            }
        });
    }

    async function loadSettings() {
        try {
            const res = await window.apiFetch('settings.php');
            if (res && res.success && res.data) {
                const s = res.data;
                const form = document.getElementById('analyzerSettingsForm');
                if (form) {
                    if (form.elements['debt_strategy']) form.elements['debt_strategy'].value = s.debt_strategy || 'avalanche';
                    if (form.elements['emergency_fund_target_months']) form.elements['emergency_fund_target_months'].value = s.emergency_fund_target_months || 6;
                    if (form.elements['minimum_savings_allocation']) form.elements['minimum_savings_allocation'].value = s.minimum_savings_allocation || 0;
                    if (form.elements['minimum_investment_allocation']) form.elements['minimum_investment_allocation'].value = s.minimum_investment_allocation || 0;
                }
            }
        } catch(e) {
            console.error("Error loading settings:", e);
        }
    }

    async function loadAnalysisData() {
        try {
            const member_id = filterMember ? filterMember.value : 'ALL';
            const res = await window.apiFetch('analysis.php?member_id=' + encodeURIComponent(member_id));
            
            if (res && res.success && res.data) {
                renderAnalysisDashboard(res.data);
            }
        } catch (e) {
            console.error("Error loading analysis data:", e);
        }
    }

    function renderAnalysisDashboard(data) {
        const { snapshot, ratios, debt, recommendations } = data;

        // 1. Snapshot
        document.getElementById('df-kpi-income').textContent = window.formatINR(snapshot.income);
        document.getElementById('df-kpi-essential').textContent = window.formatINR(snapshot.essential_expenses);
        document.getElementById('df-kpi-discretionary').textContent = window.formatINR(snapshot.discretionary_expenses);
        document.getElementById('df-kpi-fcf').textContent = window.formatINR(snapshot.free_cash_flow);

        // 2. Ratios
        const sr = document.getElementById('df-savings-rate');
        const srStatus = document.getElementById('df-savings-status');
        sr.textContent = ratios.savings_rate.toFixed(1) + '%';
        if (ratios.savings_rate >= 20) { sr.style.color = 'var(--success-color)'; srStatus.textContent = 'Excellent'; }
        else if (ratios.savings_rate >= 10) { sr.style.color = 'var(--warning-color)'; srStatus.textContent = 'Healthy'; }
        else { sr.style.color = 'var(--danger-color)'; srStatus.textContent = 'Needs Improvement'; }

        const er = document.getElementById('df-emi-ratio');
        const erStatus = document.getElementById('df-emi-status');
        er.textContent = ratios.emi_ratio.toFixed(1) + '%';
        if (ratios.emi_ratio <= 30) { er.style.color = 'var(--success-color)'; erStatus.textContent = 'Healthy Burden'; }
        else if (ratios.emi_ratio <= 40) { er.style.color = 'var(--warning-color)'; erStatus.textContent = 'Moderate Burden'; }
        else { er.style.color = 'var(--danger-color)'; erStatus.textContent = 'High Burden'; }

        const efp = document.getElementById('df-emergency-progress');
        const efpStatus = document.getElementById('df-emergency-status');
        efp.textContent = ratios.emergency_fund_progress.toFixed(1) + '%';
        if (ratios.emergency_fund_progress >= 100) { efp.style.color = 'var(--success-color)'; }
        else if (ratios.emergency_fund_progress >= 50) { efp.style.color = 'var(--warning-color)'; }
        else { efp.style.color = 'var(--danger-color)'; }
        efpStatus.textContent = `Target: ${window.formatINR(ratios.emergency_fund_target)}`;

        // 3. Recommendations
        const recContainer = document.getElementById('recommendations-container');
        recContainer.innerHTML = '';
        if (recommendations.length === 0) {
            recContainer.innerHTML = '<div style="padding: 15px; background: var(--light-bg); border-radius: 8px;">No critical recommendations at this time. Keep up the good work!</div>';
        } else {
            recommendations.forEach(r => {
                let borderCol = 'var(--primary-color)';
                let icon = '💡';
                if (r.priority === 'HIGH') { borderCol = 'var(--danger-color)'; icon = '🚨'; }
                else if (r.priority === 'MEDIUM') { borderCol = 'var(--warning-color)'; icon = '⚠️'; }

                const div = document.createElement('div');
                div.style.padding = '15px';
                div.style.background = 'var(--light-bg)';
                div.style.borderLeft = `4px solid ${borderCol}`;
                div.style.borderRadius = '4px';
                div.innerHTML = `
                    <h5 style="margin: 0 0 5px 0;">${icon} ${r.issue}</h5>
                    <p style="margin: 0 0 5px 0; font-size: 0.9em;"><strong>Evidence:</strong> ${r.evidence}</p>
                    <p style="margin: 0 0 5px 0; font-size: 0.9em;"><strong>Impact:</strong> ${r.impact}</p>
                    <p style="margin: 0; font-size: 0.9em; color: ${borderCol};"><strong>Suggested Action:</strong> ${r.action}</p>
                `;
                recContainer.appendChild(div);
            });
        }

        // 4. Simulator Scenarios & Roadmap
        const sim = data.simulator;
        
        document.getElementById('df-strategy-label').textContent = debt.strategy.charAt(0).toUpperCase() + debt.strategy.slice(1);
        document.getElementById('df-extra-capacity').textContent = window.formatINR(debt.extra_capacity);
        
        // Find highest interest rate for investment comparison
        let highestRate = 0;
        debt.active_loans.forEach(l => { if (l.interest_rate > highestRate) highestRate = l.interest_rate; });
        const hrSpan = document.getElementById('df-highest-debt-rate');
        if (hrSpan) hrSpan.textContent = highestRate;

        // Render Scenarios
        const scenBody = document.getElementById('df-scenariosBody');
        if (scenBody && sim && sim.scenarios) {
            scenBody.innerHTML = '';
            sim.scenarios.forEach(s => {
                let extraAmt = 0;
                if (s.name === 'Conservative') extraAmt = debt.extra_capacity * 0.5;
                if (s.name === 'Balanced') extraAmt = debt.extra_capacity * 1.0;
                if (s.name === 'Aggressive') extraAmt = debt.extra_capacity * 1.5;
                
                let highlight = s.name === 'Balanced' ? 'background: #f0f7ff; font-weight: bold;' : '';
                
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid var(--border-color)';
                tr.style.cssText += highlight;
                
                const years = Math.floor(s.months / 12);
                const rMonths = s.months % 12;
                const timeStr = years > 0 ? `${years}y ${rMonths}m` : `${s.months}m`;
                
                tr.innerHTML = `
                    <td style="padding: 10px;">${s.name}</td>
                    <td style="padding: 10px;">${window.formatINR(extraAmt)}</td>
                    <td style="padding: 10px;">${timeStr}</td>
                    <td style="padding: 10px; color: var(--danger-color);">${window.formatINR(s.interest)}</td>
                    <td style="padding: 10px; color: var(--success-color);">${window.formatINR(s.saved)}</td>
                `;
                scenBody.appendChild(tr);
            });
        }
        
        // Render Roadmap
        const tbody = document.getElementById('df-roadmapBody');
        if (tbody && sim && sim.roadmap) {
            tbody.innerHTML = '';
            if (sim.roadmap.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 10px;">You are completely debt-free! 🎉</td></tr>';
            } else {
                sim.roadmap.forEach(r => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-color)';
                    tr.innerHTML = `
                        <td style="padding: 10px; font-weight: bold;">${r.month}</td>
                        <td style="padding: 10px; color: var(--primary-color);">${r.target_loan || '-'}</td>
                        <td style="padding: 10px;">${window.formatINR(r.opening_debt)}</td>
                        <td style="padding: 10px;">${window.formatINR(r.regular_emi)}</td>
                        <td style="padding: 10px; color: var(--success-color);">${window.formatINR(r.extra_payment)}</td>
                        <td style="padding: 10px; color: var(--danger-color);">${window.formatINR(r.interest)}</td>
                        <td style="padding: 10px; color: var(--success-color);">${window.formatINR(r.principal)}</td>
                        <td style="padding: 10px; font-weight: bold;">${window.formatINR(r.closing_debt)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }
    }

    if (!originalFetchDashboardData) {
        loadAnalysisData();
    }
});
