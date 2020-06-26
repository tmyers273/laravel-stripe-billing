# Laravel Stripe Billing

## Versions

- 1.x - Laravel 5.1 -> 5.7  
- 2.x - Laravel 5.8+

## Installation
Via composer:

`composer require tmyers273/laravel-stripe-billing`

After package is installed via composer, run the following command:
`php artisan vendor:publish`
and then pick ` Provider: TMyers\StripeBilling\StripeBillingServiceProvider` from the displayed list.
This will copy migrations files and config into your app.

Afterwards run the migrations:
```php artisan migrate```
## Usage
Add `Billable` trait to the User model.

### Stripe Secret
In order to use this package you must posses a **Stripe Secret Key**.
It must be stored as an environment variable `STRIPE_SECRET` and/or set in `config/services.php` under `stripe.secret`

### Models
- Product (this is the parent product used to control access rights - e.g. Pro, Gold, Basic, Team etc.)
- Price (this defines price and trial period e.g. pro_monthly_10, gold_yearly_9999 etc.)
- Subscription
- Card

Product model represents product access/privileges parameters
Price model optionally belongs to Product model and represents price parameters   

## Public API

### StripeBilling static helper
```php
StripeBilling::createTestToken();
StripeBilling::setApiKey($apiKey);
StripeBilling::setCurrency($currency);
```

#### Stripe customer
```php
// Create a customer from token (new default card will be created)
$user->retrieveOrCreateStripeCustomer($token);
$user->retrieveStripeCustomer($token);
```

### Subscriptions

By default users can have multiple subscriptions. 
But this can be changed by setting `unique_active_subscription` to `true` in `config/stripe-billing.php`


##### Check subscription
```php
// Check if user is already subscribed to product
// Accepts Price object, Product object, string (name of Product or Price) e.g. basic, basic_yearly_90
$user->isSubscribedTo($product);

// Check if user is subscribed to a specific $price
$user->isSubscribedStrictlyTo($price);

// true or false
$user->hasActiveSubscriptions();

// or for subscription
// accepts Price|Product|string (name of Product or Price)
$subscription->isFor($product);
```

##### Retrieve subscriptions and chain methods
```php
$user->getSubscriptionFor($product)->isActive();
$user->getSubscriptionFor('basic-monthly-10')->cancelNow();

// in the vast majority of cases your users will be only allowed
// to have one active subscription, so use this method when applicable
$user->getFirstActiveSubscription();
```

##### Create products and subscriptions
```php
// Create the plans
$bronzePlan= Plan::create([
    'description' => 'Bronze Plan',
    'name' => 'bronze',
]);

// Create the Price
$bronzeMonthly = Price::create([
    'product_id' => $bronzePlan->id, // parent plan id
    'description' => 'Monthly Bronze Plan',
    'name' => 'bronze_monthly_50.00',
    'interval' => 'month',
    'stripe_product_id' => 'bronze_monthly', // this needs to be created in Stripe first
    'price' => 5000,
    'active' => true,
]);

// Accepts Plan object or string representing Plan name e.g. bronze_monthly_50.00
$user->subscribeTo($bronzeMonthly); // for already existing stripe customer
$user->subscribeTo($bronzeMonthly, $token); // for user without created customer
```

##### List subscriptions
```php
$user->subscriptions;
$user->activeSubscriptions;
```

##### Cancel subscriptions
```php
$subscription->cancelAtPeriodEnd();
$subscription->cancelNow();
```

##### Resuming subscription
Resuming subscription is possible only as long as it is on grace period
```php
$subscription->resume();
```

##### Extend trial
Extending the trial is done in two ways.

```php
$subscription->trialEndAt($unixTimestamp);
```
```php
$days = 10;
$subscription->addDaysToTrial($days);
```

**This will default prorate to false**

#### Changing plan
```php
// Accepts Plan object
$subscription->changeTo($basicMonthlyPlan);
```

#### Subscription scopes
```php
Subscription::active()->get(); // get all active subscriptions
Subscription::canceledAndArchived()->get(); // get all canceled and non active subscriptions
```

## Charges and credit cards

#### Default card
```php
$user->defaultCard;
```

#### Add card from token
```php
$card = $user->addCardFromToken($token);
```
This will create a stripe customer if user does not have one assigned yet. The default source of the
new Stripe customer will be used to create the default credit card for the user, which will be used by 
default for future transactions.

If Stripe customer already exists for the user and the user already has a default card, the new card will be
added as a new source for the users's Stripe Customer and a new Card record will be created in the local database. 

#### Set another card as default
```php
$user->setCardAsDefault($card);
```

That method by default takes `\TMyers\StripeBilling\Models\Card::class` as an argument and makes the card 
the default card for that user. User's corresponding Stripe Customer's default source also gets updated.

#### Check if user already has a default card assigned
```php
$user->hasDefaultCard(); //true or false
```
Or you can pass an instance of `\TMyers\StripeBilling\Models\Card::class` to verify 
if that particular card is the default one for the user:

```php
$user->hasDefaultCard($card); //true or false
```

#### Remove card
```php
$user->removeCard($card);
```

#### Card helper methods
```php
$card->isOwnedBy($user); // true or false
$card->isDefault(); // true or false
```

### Single charges
When user already has a default card assigned:
```php
$user->charge(2500); // Charge 25$
```

#### Charge by token
```php
$user->chargeByToken(1799, 'some token from stripe.js'); // Charge 17.99$
```

#### Charge via non-default card
```php
/**
* @param int $amount
* @param Card $card
* @param array $params
* @return mixed
*/
$user->chargeCard(1799, $card); // Charge 17.99$
```

### Coupons
Coupon can be either a Stripe/Coupon or a string coupon ID of an existing coupon
```php
$user->applyCoupon($coupon);
```

### Middleware
Register in HTTP `Kernel.php`
```php
'subscription' => \TMyers\StripeBilling\Middleware\SubscriptionMiddleware::class,
```

The middleware can take parameters like so: `subscription:basic,pro` - that means that 
users with any of these subscriptions can pass the middleware. When used *without parameters* it will 
just look for any active including `onTrial` or `OnGracePeriod` subscriptions 

### Blade directives
**subscribed** directive determines if user is logged in and subscribed
```blade
@subscribed
// do something
@endsubscribed
```
**unless_subscribed** directive determines if user is logged in and not subscribed
```blade
@unless_subscribed
// do something
@endunless_subscribed
```

### Config
```php
'models' => [
        'owner' => 'App\User',
        'subscription' => \TMyers\StripeBilling\Models\Subscription::class,
        'pricing_plan' => \TMyers\StripeBilling\Models\Price::class,
        'plan' => \TMyers\StripeBilling\Models\Plan::class,
        'card' => \TMyers\StripeBilling\Models\Card::class,
    ],
    
    'tables' => [
        'owner' => 'users',
        'subscriptions' => 'subscriptions',
        'pricing_plans' => 'pricing_plans',
        'plans' => 'plans',
        'cards' => 'cards',
    ],
    
    'unique_active_subscription' => false,
];
```


