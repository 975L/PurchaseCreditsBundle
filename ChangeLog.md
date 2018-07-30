# Changelog

v1.4.5
------
- Injected `AuthorizationCheckerInterface` in Controllers to avoid use of `$this->get()` (30/07/2018)
- Made use of ParamConverter (30/07/2018)
- Moved `PurchaseCreditsService` > `addTransaction()` to `TransactionService` > `add()` (30/07/2018)
- Corrected `README.md` (30/07/2018)
- Added test to check if `addCredits()` method exists in User Class (30/07/2018)

v1.4.4
------
- Removed required in composer.json (22/05/2018)
- Removed 'Action' in Controllers method as not requested anymore (29/07/2018)
- Use of Yoda-style (29/07/2018)
- Split of Controller files (29/07/2018)
- Created a Twig extension to display credits in place of doing so in Twig templates (29/07/2018)
- Added sentence about choosing number of credits on the purchase form (29/07/2018)
- Made Controller more SOLID compliant (29/07/2018)
- Added `_locale` variable in sendMail (29/07/2018)

v1.4.3
------
- Corrected missing `$transaction` return when created (17/05/2018)

v1.4.2
------
- Modified toolbars calls due to modification of c975LToolbarBundle (13/05/2018)
- Modified display for credit amount in transactions (13/05/2018)

v1.4.1
------
- Added missing `createAccessDeniedException()` for transactions view (14/04/2018)

v1.4
----
- Added TransactionService to simplify creation of Transaction [BC-Break] (04/04/2018)

v1.3.3
------
- Changed "you" to 'I' in translations (01/04/2018)

v1.3.2
------
- Added color display for remaining credits (27/03/2018)
- Added info about Twig access in `README.md` (28/03/2018)

v1.3.1.2
--------
- Corrected credits type in sql file (26/03/2018)
- Corrected french translation (26/03/2018)

v1.3.1.1
--------
- Corrected `README.md` (26/03/2018)

v1.3.1
------
- Added `Resources/views/fragments/divData.html.twig` to display user's credits to be use by javascript (26/03/2018)

v1.3
----
- Added Route to display transaction + example in `README.md` (26/03/2018)
- Added field payment order in transactions list (26/03/2018)

v1.2
----
- Added send email when purchasing credits (21/03/2018)
- Added VAT config value (21/03/2018)
- Replaced text `not_enough_credits` by `no_credits` because having 0 credits may be enough on websites to have free services (26/03/2018)

v1.1.1
------
- Modified label for Terms of Sales acceptance (21/03/2018)
- Added mandatory field (21/03/2018)

v1.1
----
- Added core system files (21/03/2018)

v1.0
----
- Creation of bundle (16/03/2018)