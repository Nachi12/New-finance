# Google Sheets Database Schema

This document outlines the required schema for the Personal & Family Financial Management Dashboard. 
You must create a Google Spreadsheet with the following exactly named sheets and columns. 
The row 1 of every sheet should contain the column names as listed below.

## Sheet 1: SETTINGS
| A | B | C |
|---|---|---|
| id | setting_name | setting_value |

## Sheet 2: FAMILY
| A | B | C | D | E |
|---|---|---|---|---|
| family_member_id | name | relationship | status | notes |

## Sheet 3: INCOME
| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| income_id | date | month | family_member_id | income_source | income_type | amount | recurring | notes |

## Sheet 4: EXPENSES
| A | B | C | D | E | F | G | H | I | J | K |
|---|---|---|---|---|---|---|---|---|---|---|
| expense_id | date | month | family_member_id | category | subcategory | description | amount | payment_method | recurring | notes |

## Sheet 5: LOANS
| A | B | C | D | E | F | G | H | I | J | K | L | M | N | O |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| loan_id | loan_name | borrower | lender | loan_type | principal_amount | interest_rate | tenure_months | start_date | emi_amount | total_paid | outstanding_principal | next_emi_date | status | notes |

## Sheet 6: EMI_PAYMENTS
| A | B | C | D | E | F | G | H | I | J |
|---|---|---|---|---|---|---|---|---|---|
| payment_id | loan_id | payment_date | month | emi_amount | principal_component | interest_component | status | payment_method | notes |

## Sheet 7: BANK_ACCOUNTS
| A | B | C | D | E | F | G | H |
|---|---|---|---|---|---|---|---|
| account_id | family_member_id | bank_name | account_name | account_type | opening_balance | current_balance | notes |

## Sheet 8: TRANSACTIONS
| A | B | C | D | E | F | G | H | I | J | K | L |
|---|---|---|---|---|---|---|---|---|---|---|---|
| transaction_id | date | month | family_member_id | account_id | type | category | description | amount | payment_method | reference | notes |

## Sheet 9: INVESTMENTS
| A | B | C | D | E | F | G | H | I | J |
|---|---|---|---|---|---|---|---|---|---|
| investment_id | date | family_member_id | investment_type | investment_name | invested_amount | current_value | returns | return_percentage | notes |

## Sheet 10: ASSETS
| A | B | C | D | E | F | G | H |
|---|---|---|---|---|---|---|---|
| asset_id | family_member_id | asset_name | asset_type | purchase_value | current_value | purchase_date | notes |

## Sheet 11: FINANCIAL_GOALS
| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| goal_id | family_member_id | goal_name | target_amount | current_amount | target_date | monthly_contribution | status | notes |

## Sheet 12: BUDGET
| A | B | C | D |
|---|---|---|---|
| budget_id | month | category | budget_amount |

## Sheet 13: MONTHLY_SUMMARY
| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| month | total_income | total_expenses | total_emi | total_investment | total_savings | net_cash_flow | outstanding_debt | net_worth |
