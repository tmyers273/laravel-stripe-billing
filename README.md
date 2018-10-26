# Laravel Stripe Billing

## Usage
Add `Billable` trait to the User model.

## Plans
* Plan model represents plan access/priviledges parameters
* PricingPlan model optionally belongs to Plan model and represents price parameters   

## Public API

#### Stripe customer
```php
// Create a customer from token (new default card will be created)
$user->retrieveOrCreateStripeCustomer($token);
```

### Subscriptions
##### Check subscription
```php
// Check if user is already subscribed to plan
// Accepts PricingPlan object, Plan object, string (name of Plan or PricingPlan) e.g. basic, basic_yearly_90
$user->isSubscribedTo($plan);

// true or false
$user->hasActiveSubscriptions();

// or for subscription
// accepts PricingPlan|Plan|string (name of Plan or PricingPlan)
$subscription->isFor($plan);
```

##### Retrieve subscriptions and chain methods
```php
$user->getSubscriptionFor($teamPlan)->isActive();
$user->getSubscriptionFor('basic-monthly-10')->cancelNow();
```

##### Create subscription
```php
// Accepts Plan object or string representing Plan name e.g. pro_monthly_10
$user->subscribeTo($plan); // for already existing stripe customer
$user->subscribeTo($plan, $token); // for user without created customer
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

##### Changing plan
```php
// Accepts Plan object
$subscription->changeTo($basicMonthlyPlan);
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

#### Middleware
Register in HTTP `Kernel.php`
```php
'subscription' => \TMyers\StripeBilling\Middleware\SubscriptionMiddleware::class,
```

The middleware can take parameters like so: `subscription:basic,pro` - that means that 
users with any of these subscriptions can pass the middleware. When used *without parameters* it will 
just look for any active including `onTrial` or `OnGracePeriod` subscriptions 


