# Changelog

v2.1.2
- Modified tools to include a button to UserBundle dashboard if installed (14/07/2019)
- Modified creditsInformation template to use buttons instead of texts (14/07/2019)

v2.1.1.1
--------
- Changed Github's author reference url (08/04/2019)

v2.1.1
------
- Made use of Twig namespace (11/03/2019)
- Added declaration of formFactory (11/03/2019)
- Renamed declaration of servicePdf (11/03/2019)
- Removed declaration of container as not used (11/03/2019)

v2.1
----
- Modified Entity to specify lengths for strings (15/02/2019)
- Modified Entity to use typehint (15/02/2019)
- Documented the possibility to use `php bin/console make:migration` (15/02/2019)

v2.0.4
------
- Removed deprecations for @Method (13/02/2019)
- Implemented AstractController instead of Controller (13/02/2019)
- Modified Dependencyinjection rootNode to be not empty (13/02/2019)

v2.0.3
------
- Modified required versions in `composer.json` (25/12/2018)

v2.0.2
------
- Added missing use (25/12/2018)

v2.0.1
------
- Corrected `UPGRADE.md` for `php bin/console config:create` (03/12/2018)
- Added rector to composer dev part (23/12/2018)
- Modified required versions in composer (23/12/2018)

v2.0
----
**Upgrading from v1.x? Check UPGRADE.md**
- Created branch 1.x (31/08/2018)
- Updated composer.json (01/09/2018)
- Updated `README.md` (01/09/2018)
- Added `bundle.yaml` (01/09/2018)
- Added `UPGRADE.md` (01/09/2018)
- Removed declaration of parameters in Configuration class as they are end-user parameters and defined in c975L/ConfigBundle (01/09/2018)
- Added Route `purchasecredits_config` (01/09/2018)
- Removed calls of `$container->getParameter()` (01/09/2018)


v1.x
====

v1.6.2
------
- Fixed Voter constants (31/08/2018)

v1.6.1
------
- Used a `switch()` for the FormFactory more readable (27/08/2018)
- Renamed "purchaseCreditsConfig" to "config" in `PurchaseCreditsType` (27/08/2018)
- Changed the FormFactory to the right version and made use of it (27/08/2018)

v1.6
----
- Replaced links in dashboard (transactions) by buttons (25/08/2018)
- Removed left 'Action' in Controllers method (25/08/2018)
- Made controller skinny (25/08/2018)
- Added documentation (25/08/2018)
- Added link to BuyMeCoffee (25/08/2018)
- Added link to apidoc (25/08/2018)
- Removed FQCN (25/08/2018)
- Split service in multiples files + Interface (25/08/2018)
- Updated `README.md` (25/08/2018)
- Made use of @ParamConverter for Payment (25/08/2018)
- Added IP Address to purchase form to be GDPR compliant
- Added GDPR checkbox to purchase form (25/08/2018)
- Added `PaymentController` (25/08/2018)
- Removed 'true ===' as not needed (25/08/2018)
- Added dependency on "c975l/config-bundle" and "c975l/services-bundle" (26/08/2018)
- Deleted un-needed translations (26/08/2018)

v1.5.1
------
- Added missing breaks (03/08/2018)

v1.5
----
- Made use of Voters for access rights (01/08/2018)

v1.4.5.1
--------
- Corrected `TransactionService` (30/07/2018)

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
