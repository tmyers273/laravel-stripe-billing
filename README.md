# Laravel Stripe Billing

### Usage
Add `Billable` trait to the User model.

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
// Accepts Plan object, PlanType object, string (code_name of plan_type or plan) e.g. basic, basic_yearly_90
$user->isSubscribedTo($plan);

// or for subscription
// accepts Plan|PlanType|string (code_name of plan_type or plan)
$subscription->isFor($plan);
```

##### Retrieve subscriptions and chain methods
```php
$user->getSubscriptionFor($teamPlan)->isActive();
$user->getSubscriptionFor('basic-monthly-10')->cancelNow();
```

##### Create subscription
```php
// Accepts Plan object or string representing Plan code_name e.g. pro_monthly_10
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


