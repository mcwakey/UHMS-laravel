# Finance Reference Mapping

## Account categories

`acc_titles` has 31 rows but 13 distinct nonblank normalized title/type keys, one blank title and three duplicate groups/20 rows. TI_ID is protected identity; Title maps only to reviewed `account_categories.name`; INC/EXP maps to income/expense. Do not map Classic `accounts` to target chart accounts and never default to Miscellaneous.

## Transaction/balance boundary

Classic `accounts` has one transaction row; it is not a chart. It creates zero target accounts, entries, journals or postings. Its title relation matches; `BANK_ACC_ID=0` is a relationship-specific sentinel. `acc_petty` has one row and no approved target; it is deferred evidence.

## Bank configuration

Classic `bank` has one row. BankName/AccName and a normalized in-memory SHA-256/last-four mask of AccNum are configuration candidates. Clear account numbers must never be logged or documented. `Bal` is deferred opening-balance evidence; RegDate is not an opening date. Target requires GL account and currency; neither may be taken silently from defaults.

Natural key: unique account-number hash plus approved currency and compatible bank name. The target hash index is not unique, so conflicts block. `AccountingChartSeeder`, `AccountingPostingTemplateSeeder` and `DatabaseSeeder` are prohibited. Conditional AccountCategorySeeder/CashAndCarry baseline data may be reviewed separately but is not a migration command.
