# Laravel Stripe Billing

### Usage
Add `Billable` trait to the User model.

### Plans
* Plan model represents plan access/priviledges parameters
* PricingPlan model optionally belongs to Plan model and represents price parameters   

### API

#### Stripe customer
```php
// Create a customer from token (new default card will be created)
$user->retrieveOrCreateStripeCustomer($token);
```
#### Default card
```php
$user->defaultCard;
```

#### Subscriptions
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

#### Middleware
Register in HTTP `Kernel.php`
```php
'subscription' => \TMyers\StripeBilling\Middleware\SubscriptionMiddleware::class,
```

The middleware can take parameters like so: `subscription:basic,pro` - that means that 
users with any of these subscriptions can pass the middleware. When used *without parameters* it will 
just look for any active including `onTrial` or `OnGracePeriod` subscriptions 


