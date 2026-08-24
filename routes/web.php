<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website
|--------------------------------------------------------------------------
*/

Route::get('/', [Site\HomeController::class, 'index'])->name('home');

Route::get('/layanan', [Site\ServiceController::class, 'index'])->name('services.index');
Route::get('/layanan/{service}', [Site\ServiceController::class, 'show'])->name('services.show');

Route::get('/portfolio', [Site\PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{portfolio}', [Site\PortfolioController::class, 'show'])->name('portfolio.show');

Route::get('/galeri', [Site\GalleryController::class, 'index'])->name('gallery.index');

Route::get('/blog', [Site\BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{article}', [Site\BlogController::class, 'show'])->name('blog.show');

Route::get('/tentang-kami', [Site\PageController::class, 'about'])->name('about');
Route::get('/kontak', [Site\PageController::class, 'contact'])->name('contact');

Route::get('/konsultasi', [Site\ConsultationController::class, 'create'])->name('consultation.create');
Route::post('/konsultasi', [Site\ConsultationController::class, 'store'])
    ->middleware('throttle:10,10')
    ->name('consultation.store');

Route::get('/tracking', [Site\TrackingController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('tracking.index');
Route::get('/tracking/foto/{photo}', [Site\TrackingController::class, 'photo'])->name('tracking.photo');

Route::get('/sitemap.xml', Site\SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // CRM
        Route::resource('customers', Admin\CustomerController::class)->except(['destroy']);
        Route::delete('customers/{customer}', [Admin\CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('inquiries', [Admin\InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [Admin\InquiryController::class, 'show'])->name('inquiries.show');
        Route::patch('inquiries/{inquiry}', [Admin\InquiryController::class, 'update'])->name('inquiries.update');
        Route::post('inquiries/{inquiry}/convert', [Admin\InquiryController::class, 'convert'])->name('inquiries.convert');
        Route::get('inquiries/{inquiry}/attachment', [Admin\InquiryController::class, 'attachment'])->name('inquiries.attachment');
        Route::delete('inquiries/{inquiry}', [Admin\InquiryController::class, 'destroy'])->name('inquiries.destroy');

        // Orders
        Route::resource('orders', Admin\OrderController::class);
        Route::patch('orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('orders/{order}/notes', [Admin\OrderController::class, 'updateNotes'])->name('orders.notes');

        // Tracking stages
        Route::post('orders/{order}/stages/{stage}/start', [Admin\OrderStageController::class, 'start'])->name('orders.stages.start');
        Route::post('orders/{order}/stages/{stage}/complete', [Admin\OrderStageController::class, 'complete'])->name('orders.stages.complete');
        Route::post('orders/{order}/stages/{stage}/reopen', [Admin\OrderStageController::class, 'reopen'])->name('orders.stages.reopen');

        // Production photos
        Route::post('orders/{order}/photos', [Admin\ProductionPhotoController::class, 'store'])->name('orders.photos.store');
        Route::patch('photos/{photo}', [Admin\ProductionPhotoController::class, 'update'])->name('photos.update');
        Route::delete('photos/{photo}', [Admin\ProductionPhotoController::class, 'destroy'])->name('photos.destroy');
        Route::get('photos/{photo}/file/{kind?}', [Admin\ProductionPhotoController::class, 'file'])->name('photos.file');

        // Attachments
        Route::post('orders/{order}/attachments', [Admin\OrderAttachmentController::class, 'store'])->name('orders.attachments.store');
        Route::get('attachments/{attachment}/download', [Admin\OrderAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('attachments/{attachment}', [Admin\OrderAttachmentController::class, 'destroy'])->name('attachments.destroy');

        // Invoices
        Route::resource('invoices', Admin\InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [Admin\InvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Payments
        Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('orders/{order}/payments', [Admin\PaymentController::class, 'store'])->name('orders.payments.store');
        Route::get('payments/{payment}/proof', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
        Route::delete('payments/{payment}', [Admin\PaymentController::class, 'destroy'])->name('payments.destroy');

        // CMS
        Route::resource('services', Admin\ServiceController::class)->except(['show']);
        Route::resource('portfolio', Admin\PortfolioController::class)->except(['show'])->parameters(['portfolio' => 'portfolio']);
        Route::delete('portfolio-images/{image}', [Admin\PortfolioController::class, 'destroyImage'])->name('portfolio.images.destroy');
        Route::post('portfolio-categories', [Admin\PortfolioCategoryController::class, 'store'])->name('portfolio-categories.store');
        Route::delete('portfolio-categories/{category}', [Admin\PortfolioCategoryController::class, 'destroy'])->name('portfolio-categories.destroy');

        Route::resource('reviews', Admin\ReviewController::class)->except(['show']);

        Route::get('gallery/picker', [Admin\GalleryController::class, 'picker'])->name('gallery.picker');
        Route::get('gallery', [Admin\GalleryController::class, 'index'])->name('gallery.index');
        Route::post('gallery', [Admin\GalleryController::class, 'store'])->name('gallery.store');
        Route::patch('gallery/{item}/toggle', [Admin\GalleryController::class, 'toggle'])->name('gallery.toggle');
        Route::delete('gallery/{item}', [Admin\GalleryController::class, 'destroy'])->name('gallery.destroy');

        Route::resource('articles', Admin\ArticleController::class)->except(['show']);
        Route::post('article-categories', [Admin\ArticleCategoryController::class, 'store'])->name('article-categories.store');
        Route::delete('article-categories/{category}', [Admin\ArticleCategoryController::class, 'destroy'])->name('article-categories.destroy');

        // Settings
        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::patch('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
