<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\CampaignArticleController;
use App\Http\Controllers\MediaProxyController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\WaController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/campaigns/chunk', [HomeController::class, 'chunk'])->name('home.chunk');
// SEO: sitemap and robots
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/program', [ProgramController::class, 'index'])->name('program.index');
Route::get('/programs/chunk', [ProgramController::class, 'chunk'])->name('program.chunk');
// Organization public page
Route::get('/org/{slug}', [OrganizationController::class, 'show'])->name('organization.show');
// Events
Route::get('/event', [EventController::class, 'index'])->name('event.index');
Route::get('/event/{event:slug}', [EventController::class, 'show'])->name('event.show');

// Cart (requires auth)
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{event:slug}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{event:slug}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{event:slug}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Orders (requires auth)
Route::middleware('auth')->group(function(){
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
    Route::post('/checkout', [OrderController::class, 'place'])->name('order.place');
    Route::get('/order/{reference}/pay', [OrderPaymentController::class, 'pay'])->name('order.pay');
    Route::get('/order/{reference}/terima-kasih', [OrderPaymentController::class, 'thanks'])->name('order.thanks');
    Route::get('/order/{reference}/manual', [OrderPaymentController::class, 'manual'])->name('order.manual');
    Route::post('/order/{reference}/manual', [OrderPaymentController::class, 'submitManual'])->name('order.manual.submit');
    Route::get('/order/{reference}/tickets', [TicketController::class, 'index'])->name('order.tickets');
    Route::get('/order/{reference}/tickets/print', [TicketController::class, 'print'])->name('order.tickets.print');
    Route::get('/ticket/{code}/qr', [TicketController::class, 'qr'])->name('ticket.qr');

    // Replay purchase
    Route::get('/event/{event:slug}/beli-replay', [EventController::class, 'replayCheckout'])->name('event.replay.checkout');
    Route::post('/event/{event:slug}/beli-replay', [EventController::class, 'buyReplay'])->name('event.replay.buy');
});

// Midtrans notification for orders
Route::post('/midtrans/order/notify', [OrderPaymentController::class, 'notify'])->name('midtrans.order.notify');
Route::middleware('auth')->group(function(){
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/{event:slug}', [CalendarController::class, 'show'])->name('calendar.show');
    Route::get('/calendar/{event:slug}/print', [CalendarController::class, 'print'])->name('calendar.print');
});
Route::view('/notifications', 'notifications.index')->name('notifications.index');

Route::get('/campaign/{slug}', [CampaignController::class, 'show'])->name('campaign.show');
Route::get('/campaign/{slug}/donasi', [CampaignController::class, 'donateForm'])->name('campaign.donate.form');
Route::post('/campaign/{slug}/donasi', [CampaignController::class, 'donate'])->name('campaign.donate');
Route::get('/laporan/{id}/{slug?}', [CampaignArticleController::class, 'show'])->name('article.show');
Route::get('/media/{disk}', [MediaProxyController::class, 'show'])->name('media.proxy');

Route::get('/donasi/{reference}/terima-kasih', [DonationController::class, 'thanks'])->name('donation.thanks');
Route::get('/donasi/{reference}/terima-kasih', [DonationController::class, 'thanks'])->name('donation.thanks');

// Pembayaran (Midtrans)
Route::get('/donasi/{reference}/bayar', [PaymentController::class, 'pay'])->name('donation.pay');
// Pilih metode Midtrans (VA/QRIS)
Route::get('/donasi/{reference}/metode', [PaymentController::class, 'methods'])->name('donation.methods');
Route::post('/donasi/{reference}/metode', [PaymentController::class, 'choose'])->name('donation.choose.method');
Route::post('/midtrans/notify', [PaymentController::class, 'notify'])->name('midtrans.notify');

// Pembayaran Manual
Route::get('/donasi/{reference}/manual', [PaymentController::class, 'manual'])->name('donation.manual');
Route::post('/donasi/{reference}/manual', [PaymentController::class, 'submitManual'])->name('donation.manual.submit');

// Donor CRM (public simple listing)
Route::get('/donatur', [DonorController::class, 'index'])->name('donor.index');

// Public AJAX endpoint to validate WA number from donation form
Route::post('/wa/validate-number', [WaController::class, 'validateNumber'])
    ->middleware(\App\Http\Middleware\InternalOnly::class)
    ->name('wa.validate');

Route::get('/berita', [NewsController::class, 'index'])->name('news.index');
Route::get('/berita/{slug}', [NewsController::class, 'show'])->name('news.show');

// Static Pages (storefront)
Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');

// Storefront auth
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'submit'])->name('register.submit');
Route::get('/register/otp', [RegisterController::class, 'showOtp'])->name('register.otp');
Route::post('/register/otp', [RegisterController::class, 'verifyOtp'])->name('register.otp.verify');
Route::post('/register/otp/resend', [RegisterController::class, 'resendOtp'])->name('register.otp.resend');

// Profile (requires auth)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.index');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Protected API: replay embed URL (auth + rate limit 10/min)
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::get('/api/event/{event:slug}/replay-url', [EventController::class, 'replayEmbedUrl'])
        ->name('api.event.replay-url');
});
