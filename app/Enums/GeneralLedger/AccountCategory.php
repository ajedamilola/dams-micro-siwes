<?php

namespace App\Enums\GeneralLedger;

enum AccountCategory: string
{
    // Assets, current
    case CashAndCashEquivalents = 'CashAndCashEquivalents';
    case AccountsReceivable = 'AccountsReceivable';
    case Inventory = 'Inventory';
    case PrepaidExpenses = 'PrepaidExpenses';
    case OtherCurrentAssets = 'OtherCurrentAssets';

    // Assets, non-current
    case PropertyPlantAndEquipment = 'PropertyPlantAndEquipment';
    case IntangibleAssets = 'IntangibleAssets';
    case LongTermInvestments = 'LongTermInvestments';
    case OtherNonCurrentAssets = 'OtherNonCurrentAssets';

    // Liabilities, current
    case AccountsPayable = 'AccountsPayable';
    case AccruedLiabilities = 'AccruedLiabilities';
    case ShortTermDebt = 'ShortTermDebt';
    case DeferredRevenue = 'DeferredRevenue';
    case OtherCurrentLiabilities = 'OtherCurrentLiabilities';

    // Liabilities, non-current
    case LongTermDebt = 'LongTermDebt';
    case LeaseLiabilities = 'LeaseLiabilities';
    case OtherNonCurrentLiabilities = 'OtherNonCurrentLiabilities';

    // Equity
    case ShareCapital = 'ShareCapital';
    case RetainedEarnings = 'RetainedEarnings';
    case Dividends = 'Dividends';
    case OtherEquity = 'OtherEquity';

    // Revenue
    case OperatingRevenue = 'OperatingRevenue';
    case OtherIncome = 'OtherIncome';

    // Expenses
    case CostOfGoodsSold = 'CostOfGoodsSold';
    case OperatingExpenses = 'OperatingExpenses';
    case DepreciationAndAmortization = 'DepreciationAndAmortization';
    case FinanceCosts = 'FinanceCosts';
    case IncomeTaxExpense = 'IncomeTaxExpense';
    case OtherExpenses = 'OtherExpenses';

    // Fallback, matches the accounts.category column default
    case Uncategorized = 'Uncategorized';
}
