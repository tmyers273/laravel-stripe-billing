<?php

use Illuminate\Support\Facades\Route;
use TMyers\StripeBilling\Http\Controllers\CardsController;
use TMyers\StripeBilling\Http\Controllers\ProductsController;
use TMyers\StripeBilling\Http\Controllers\SubscriptionsController;

Route::group(['prefix' => 'api/v1'], function() {

    Route::group(['prefix' => 'billing', 'namespace' => 'Billing'], function() {
        Route::post('/{subscription}/coupon', [SubscriptionsController::class, 'applyCoupon']);
        Route::post('/', [SubscriptionsController::class, 'store']);
        Route::patch('/{subscription}', 'SubscriptionsCsontroller@update');
        Route::delete('/{subscription}', [SubscriptionsController::class, 'destroy'])->name('subscription.cancel');
        Route::post('/{subscription}/resume', [SubscriptionsController::class, 'resume']);

        Route::get('/nextInvoice', [SubscriptionsController::class, 'nextInvoice']);
        Route::get('/invoices', [SubscriptionsController::class, 'invoices'])->name('subscription.invoices');
        Route::get('/products', [ProductsController::class, 'index']);

        Route::group(['prefix' => 'cards', 'middleware' => 'subscribed'], function() {
            Route::get('/', [CardsController::class, 'index']);
            Route::post('/', [CardsController::class, 'store']);
            Route::patch('/{card}', [CardsController::class, 'default']);
            Route::delete('/{card}', [CardsController::class, 'destroy']);
        });
    });

});
