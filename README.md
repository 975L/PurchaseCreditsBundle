# PurchaseCreditsBundle

PurchaseCreditsBundle does the following:

- Allows to purchase and use credits within your website,
- Interfaces with Stripe via [c975LPaymentBundle](https://github.com/975L/PaymentBundle) for its payment,
- Integrates with [c975LToolbarBundle](https://github.com/975L/ToolbarBundle),
- Emails the user about purchased credits and joins your Terms of sales as PDF to the email,

This Bundle relies on the use of [c975LPaymentBundle](https://github.com/975L/PaymentBundle), [Stripe](https://stripe.com/) and its [PHP Library](https://github.com/stripe/stripe-php).
**So you MUST have a Stripe account.**

It is also recomended to use this with a SSL certificat to reassure the user.

As the Terms of sales MUST be sent to the user with the Gift-Voucher, you MUST provide a Route or url for this PDF file. If you don't have such, you may consider using [c975LSiteBundle](https://github.com/975L/SiteBundle) for its pre-defined models and [c975LPageEditBundle](https://github.com/975L/PageEditBundle) for its ability to create a PDF.

[PurchaseCreditsBundle dedicated web page](https://975l.com/en/pages/purchase-credits-bundle).

[PurchaseCreditsBundle API documentation](https://975l.com/apidoc/c975L/PurchaseCreditsBundle.html).

## Bundle installation

### Step 1: Download the Bundle

**v3.x works with Symfony 4.x. Use v2.x for Symfony 3.x**
Use [Composer](https://getcomposer.org) to install the library

```bash
    composer require c975l/purchasecredits-bundle
```

### Step 2: Configure the Bundle

Check dependencies for their configuration:

- [Doctrine](https://github.com/doctrine/DoctrineBundle)
- [KnpPaginatorBundle](https://github.com/KnpLabs/KnpPaginatorBundle)
- [c975LPaymentBundle](https://github.com/975L/PaymentBundle)
- [c975LEmailBundle](https://github.com/975L/EmailBundle)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)

c975LPurchaseCreditsBundle uses [c975L/ConfigBundle](https://github.com/975L/ConfigBundle) to manage configuration parameters. Use the Route "/purchase-credits/config" with the proper user role to modify them.

### Step 3: Enable the Routes

Then, enable the routes by adding them to the `/config/routes.yaml` file of your project:

```yml
c975_l_purchase_credits:
    resource: "@c975LPurchaseCreditsBundle/Controller/"
    type: annotation
    prefix: /
    #Multilingual website use the following
    #prefix: /{_locale}
    #defaults:   { _locale: '%locale%' }
    #requirements:
    #    _locale: en|fr|es
```

### Step 4: User entity

Your User entity **MUST** have a property `credits` with proper and getter and setter, plus a `addCredits()` one, notice the `+=`, this method is used to add and subtract credits:

```php
//Your entity file
namespace App\Entity;

//Example is made using Doctrine, as the common one, but you can use any entity manager
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity
 */
class User
{
//...
    /**
     * Number of credits for User
     * @var int
     *
     * @ORM\Column(name="credits", type="integer", nullable=true)
     */
    protected $credits;

//...
    /**
     * Set credits
     * @param int
     * @return User
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits
     * @return int
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * Add credits (or subtracts if $credits is negative)
     * @param int
     * @return User
     */
    public function addCredits($credits)
    {
        $this->credits += $credits;

        return $this;
    }
```

### Step 5: Create MySql tables

You can use `php bin/console make:migration` to create the migration file as documented in [Symfony's Doctrine docs](https://symfony.com/doc/current/doctrine.html) OR use Use `/Resources/sql/purchase-credits.sql` to create the table `user_transactions`. The `DROP TABLE` is commented to avoid dropping by mistake.

### Step 6: Override templates

It is strongly recommended to use the [Override Templates from Third-Party Bundles feature](http://symfony.com/doc/current/templating/overriding.html) to integrate fully with your site.

For this, simply, create the following structure `/templates/bundles/c975LPurchaseCreditsBundle/` in your app and then duplicate the file `layout.html.twig` in it, to override the existing Bundle file.

In `layout.html.twig`, it will mainly consist to extend your layout and define specific variables, i.e. :

```twig
{% extends 'layout.html.twig' %}

{% block content %}
    {% block purchaseCredits_content %}
    {% endblock %}
{% endblock %}
```

### How to use

All the process for purchase and payment is managed via the bundle. All you have to implement on your side is the use of credits. You can do so with the following code:

```php
<?php
//In your controller file
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use c975L\PurchaseCreditsBundle\Service\TransactionServiceInterface;

class PaymentController extends AbstractController
{
    /**
     * @Route("/YOUR_ROUTE",
     *    name="YOUR_ROUTE_NAME",
     *    methods={"HEAD", "GET"})
     */
    public function YOUR_METHOD_NAME(Request $request, TransactionServiceInterface $transactionService)
    {
        //Your stuff...

        //Gets the manager
        $em = $this->getDoctrine()->getManager();

        //Adds transaction, to keep trace of it and for user to see it in its list of transactions
        //You can call create() without argument, TransactionService will add an orderId built on the same scheme as Payment's one
        //The only restriction is that your orderId MUST NOT start with 'pmt' as this string is added to the Payment orderId, to provide a link to the payment
        $transaction = $this->transactionService->add('YOUR_OWN_ORDER_ID_OR_NULL', +-CREDITS, $description(), $this->getUser());

        //You need to flush DB as $transaction and $user are persisted but not flushed
        $em->flush();
    }
}
```

### Routes

The different Routes (naming self-explanatory) available are:

- purchasecredits_dashboard
- purchasecredits_purchase
- purchasecredits_transactions

### Twig access

You can access user's credits in Twig via

```twig
{{ app.user.credits }}
```

### Credits information

If you want to display information about credits to user, you can add, in your Twig template, the following code. It will display, on one line, the number of credits, a link to transactions, a link to purchase and a warning when credits are <= 0.

```twig
{% include('@c975LPurchaseCredits/fragments/creditsInformation.html.twig') %}
```

### Transaction display

The display of the list of transactions is done via the bundle, but in case you want to link to a specific transaction, you can do so with the following:

```twig
{{ path('purchasecredits_transaction_display', {'orderId': 'TRANSACTION_ORDER_ID'}) }}
```

### Credits Div data for javascript use

If you want to insert a div containing the user's credits, to be used by javascript, you can do it via the Twig extension:

```twig
{# Credits DivData #}
{{ purchasecredits_divData() }}
```

Then you can access it via

```javascript
$(document).ready(function() {
    var credits = $('#userCredits').data('credits');
});
```

Have a look at it to see the properties covered.

If this project **help you to reduce time to develop**, you can sponsor me via the "Sponsor" button at the top :)
