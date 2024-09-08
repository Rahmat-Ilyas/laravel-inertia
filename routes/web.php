<?php

use Illuminate\Support\Facades\Route;

//route home
Route::get('/', function () {
    return \Inertia\Inertia::render('Auth/Login');
})->middleware('guest');

Route::group(['namespace' => 'App\Http\Controllers'], function () {
    Route::prefix('apps')->group(function () {
        Route::group(['middleware' => ['auth']], function () {
            Route::get('dashboard', 'Apps\DashboardController')->name('apps.dashboard');
        });

        Route::get('permissions', 'Apps\PermissionController')->name('apps.permissions.index')->middleware('permission:permissions.index');

        Route::resource('roles', 'Apps\RoleController', ['as' => 'apps'])->middleware('permission:roles.index|roles.create|roles.edit|roles.delete');

        Route::resource('users', 'Apps\UserController', ['as' => 'apps'])->middleware('permission:users.index|users.create|users.edit|users.delete');

        Route::resource('categories', 'Apps\CategoryController', ['as' => 'apps'])->middleware('permission:categories.index|categories.create|categories.edit|categories.delete');

        Route::resource('/products', 'Apps\ProductController', ['as' => 'apps'])->middleware('permission:products.index|products.create|products.edit|products.delete');

        Route::resource('/customers', 'Apps\CustomerController', ['as' => 'apps'])->middleware('permission:customers.index|customers.create|customers.edit|customers.delete');

        Route::prefix('transactions')->group(function () {
            Route::get('/', 'Apps\TransactionController@index')->name('apps.transactions.index');
            Route::post('searchProduct', 'Apps\TransactionController@searchProduct')->name('apps.transactions.searchProduct');
            Route::post('addToCart', 'Apps\TransactionController@addToCart')->name('apps.transactions.addToCart');
            Route::post('destroyCart', 'Apps\TransactionController@destroyCart')->name('apps.transactions.destroyCart');
            Route::post('store', 'Apps\TransactionController@store')->name('apps.transactions.store');
            Route::get('print', 'Apps\TransactionController@print')->name('apps.transactions.print');
        });
        
        Route::prefix('sales')->group(function () {
            Route::get('/', 'Apps\SaleController@index')->middleware('permission:sales.index')->name('apps.sales.index');
            Route::get('filter', 'Apps\SaleController@filter')->name('apps.sales.filter');
            Route::get('export', 'Apps\SaleController@export')->name('apps.sales.export');
        });

        Route::prefix('profits')->group(function () {
            Route::get('/', 'Apps\ProfitController@index')->middleware('permission:profits.index')->name('apps.profits.index');
            Route::get('filter', 'Apps\ProfitController@filter')->name('apps.profits.filter');
            Route::get('export', 'Apps\ProfitController@export')->name('apps.profits.export');
        });
    });
});
