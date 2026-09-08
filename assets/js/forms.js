document.addEventListener('DOMContentLoaded', function() {
    console.log("Forms JS initialized.");

    // Fetch and populate family members
    async function populateFamilyMembers() {
        try {
            const result = await window.apiFetch('members.php');
            if (result && result.success) {
                const members = result.data;
                const selects = document.querySelectorAll('.fm-select, .fm-select-expense');
                
                selects.forEach(select => {
                    // Keep existing options (like Select Member or FAMILY_SHARED)
                    const isExpense = select.classList.contains('fm-select-expense');
                    select.innerHTML = '<option value="">Select Member</option>';
                    if (isExpense) {
                        select.innerHTML += '<option value="FAMILY_SHARED">Shared Family Expense</option>';
                    }
                    
                    members.forEach(m => {
                        if (m.status !== 'Inactive') {
                            const opt = document.createElement('option');
                            opt.value = m.family_member_id;
                            opt.textContent = `${m.name} (${m.relationship})`;
                            select.appendChild(opt);
                        }
                    });
                });
            }
        } catch (e) {
            console.error("Error fetching family members:", e);
        }
    }

    populateFamilyMembers();

    // Modal Handling
    const modals = document.querySelectorAll('.modal-overlay');
    const closeBtns = document.querySelectorAll('.modal-close, .btn-cancel');
    const openBtns = document.querySelectorAll('[data-modal-target]');

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add('active');
    }

    function closeModal(modal) {
        if (modal) modal.classList.remove('active');
    }

    openBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(btn.getAttribute('data-modal-target'));
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal(btn.closest('.modal-overlay'));
        });
    });

    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // Form Submission Handling
    async function handleFormSubmit(e, apiEndpoint) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`/api/${apiEndpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            
            if (result.success) {
                alert(result.message); // In a real app, use a nicer toast notification
                closeModal(form.closest('.modal-overlay'));
                form.reset();
                
                // Trigger dashboard refresh
                if (window.fetchDashboardData) {
                    window.fetchDashboardData();
                }
                
                // If we added a member, refresh the lists
                if (apiEndpoint === 'members.php') {
                    populateFamilyMembers();
                    if (window.populateDashboardMembers) {
                        window.populateDashboardMembers();
                    }
                }
            } else {
                throw new Error(result.error || 'Failed to save');
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('Error: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    const formsToBind = [
        { id: 'addIncomeForm', endpoint: 'income.php' },
        { id: 'addExpenseForm', endpoint: 'expenses.php' },
        { id: 'addLoanForm', endpoint: 'loans.php' },
        { id: 'addEmiForm', endpoint: 'emi.php' },
        { id: 'addAccountForm', endpoint: 'accounts.php' },
        { id: 'addTransactionForm', endpoint: 'transactions.php' },
        { id: 'addInvestmentForm', endpoint: 'investments.php' },
        { id: 'addAssetForm', endpoint: 'assets.php' },
        { id: 'addBudgetForm', endpoint: 'budget.php' },
        { id: 'addGoalForm', endpoint: 'goals.php' },
        { id: 'addMemberForm', endpoint: 'members.php' }
    ];

    formsToBind.forEach(f => {
        const form = document.getElementById(f.id);
        if (form) {
            form.addEventListener('submit', (e) => handleFormSubmit(e, f.endpoint));
        }
    });
});
