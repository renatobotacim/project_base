<?php

use App\Http\Controllers\SocialController;

use App\Http\Controllers\api\v1\Auth\AuthController;
use App\Http\Controllers\api\v1\CategoryController;
use App\Http\Controllers\api\v1\EventController;
use App\Http\Controllers\api\v1\OwnerController;
use App\Http\Controllers\api\v1\AddressController;
use App\Http\Controllers\api\v1\ProducerController;
use App\Http\Controllers\api\v1\CityController;
use App\Http\Controllers\api\v1\MapsController;
use App\Http\Controllers\api\v1\SectorController;
use App\Http\Controllers\api\v1\TicketEventController;
use App\Http\Controllers\api\v1\BatchController;
use App\Http\Controllers\api\v1\TicketController;
use App\Http\Controllers\api\v1\BagController;
use App\Http\Controllers\api\v1\SaleController;
use App\Http\Controllers\api\v1\CouponController;
use App\Http\Controllers\api\v1\CommonController;
use App\Http\Controllers\api\v1\HitController;
use App\Http\Controllers\api\v1\WithdrawalController;
use App\Http\Controllers\api\v1\AdmMasterController;

use App\Http\Controllers\api\v1\Auth\LoginController;
use App\Http\Controllers\api\v1\Auth\ForgotPasswordController;
use App\Http\Controllers\api\v1\Auth\ResetPasswordController;
use App\Http\Controllers\api\v1\Auth\ValidateAcount;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [LoginController::class, 'authenticate']);
Route::middleware('auth:sanctum')->get('/logout', [LoginController::class, 'logout']);

//register user
Route::post('/register', [AuthController::class, 'register']);

//validade acount
Route::get('/validate-acount/{token}', [ValidateAcount::class, 'validadeAcount'])->name('validate.acount');

//recover password
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->name('forgot.password');
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('reset.password');

//social login
Route::group(['middleware' => ['web']], function () {
    Route::get('auth/{provider}/redirect', [SocialController::class, 'RedirectToProvider'])
        ->whereIn('provider', ['github', 'google', 'facebook'])
        ->name('social.login');

    Route::get('auth/{provider}/callback', [SocialController::class, 'hadleProviderCallback'])
        ->whereIn('provider', ['github', 'google', 'facebook'])
        ->name('social.callback');
});

Route::middleware('auth:sanctum')->prefix('/users')->group(function () {
    Route::get('/index', [AuthController::class, 'index']);
    Route::get('/logged', [AuthController::class, 'logged']);
    Route::get('/permissions', [AuthController::class, 'permissions']);
    Route::get('/adm-permissions', [AuthController::class, 'permissionsAdmMaster']);
    Route::get('/show/{id}', [AuthController::class, 'show']);

    Route::get('/history/{id}', [AuthController::class, 'history']);

    Route::post('/create', [AuthController::class, 'store']);

    Route::get('/settings', [AuthController::class, 'showSettings']);
    Route::patch('/settings', [AuthController::class, 'settings']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::patch('/update/{id}', [AuthController::class, 'update']);
    Route::delete('/delete/{id}', [AuthController::class, 'delete']);

    Route::get('/events', [TicketController::class, 'showEventsTicketsByUsers']);
    Route::get('/event/{event}', [TicketController::class, 'showTicketsEventByUsers']);
    Route::patch('/update-ticket', [TicketController::class, 'updateUsers']);
    Route::get('/auth-tickets', [TicketController::class, 'showTicketsUser']);
});

Route::prefix('/categories')->group(function () {
    Route::get('/index', [CategoryController::class, 'index']);
});

Route::middleware('auth:sanctum')->prefix('/categories')->group(function () {
    Route::get('/show/{id}', [CategoryController::class, 'show']);
    Route::post('/register', [CategoryController::class, 'store']);
    Route::put('/update/{id}', [CategoryController::class, 'update']);
    Route::delete('/delete/{id}', [CategoryController::class, 'delete']);
});

Route::prefix('/events')->group(function () {
    Route::get('/panel', [EventController::class, 'panel']);
    Route::get('/details/{slug}', [EventController::class, 'details']);
    Route::get('/search', [EventController::class, 'search']);
});

Route::middleware('auth:sanctum')->prefix('/events')->group(function () {

    Route::get('/index', [EventController::class, 'index']);    //ADM MASTER
    Route::get('/show/{id}', [EventController::class, 'show']);
    Route::get('/list', [EventController::class, 'list']);
    Route::get('/list-active', [EventController::class, 'listActive']);
    Route::get('/dashboard/{event}', [EventController::class, 'dashboard']);
    Route::get('/adm-dashboard/{event}', [EventController::class, 'dashboardAdm']);
    Route::get('/adm-dashboard/{event}/all', [EventController::class, 'dashboardAdmList']);

    Route::get('/donwload-sales/{event}', [EventController::class, 'donwloadSales']);
    Route::post('/register', [EventController::class, 'store']);
    Route::post('/update/{id}', [EventController::class, 'update']);

    Route::delete('/delete/{id}', [EventController::class, 'delete']);
    Route::get('/map-tickets/{mapId?}', [EventController::class, 'mapTickets']);

    Route::patch('/canceled/{id}', [EventController::class, 'canceled']);
    Route::patch('/promote/{id}', [EventController::class, 'promote']);
    Route::get('/emphasis', [EventController::class, 'emphasis']);

    Route::get('/best/{event}', [EventController::class, 'best']);

});

Route::prefix('/hits')->group(function () {
    Route::post('/create', [HitController::class, 'store']);
});

Route::prefix('/common')->group(function () {
    Route::get('/cep-address/{zipcode}', [CommonController::class, 'getAddressCep']);
    Route::get('/version', [CommonController::class, 'versionApp']);
});

Route::middleware('auth:sanctum')->prefix('/common')->group(function () {
    Route::get('/options', [CommonController::class, 'showOptions']);
});

Route::prefix('/common')->group(function () {
    Route::get('/generate-qr-code', [CommonController::class, 'generateQrCode']);
});

Route::middleware('auth:sanctum')->prefix('/producers')->group(function () {
    Route::get('/index', [ProducerController::class, 'index']);
    Route::get('/show/{id}', [ProducerController::class, 'show']);
    Route::get('/account', [ProducerController::class, 'account']);
    Route::get('/dashboard', [ProducerController::class, 'dashboard']);
    Route::get('/profile/{id}', [ProducerController::class, 'profile']);
    Route::get('/{id}/documents/{hash}', [ProducerController::class, 'document']);
    Route::post('/register', [ProducerController::class, 'store']);
    Route::patch('/account-update', [ProducerController::class, 'accountUpdate']);
    Route::post('/update/{id}', [ProducerController::class, 'update']);
    Route::patch('/suspended/{id}', [ProducerController::class, 'suspended']);
    Route::delete('/delete/{id}', [ProducerController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/owners')->group(function () {
    Route::get('/index', [OwnerController::class, 'index']);
    Route::get('/show/{id}', [OwnerController::class, 'show']);
    Route::post('/register', [OwnerController::class, 'store']);
    Route::patch('/update/{id}', [OwnerController::class, 'update']);
    Route::delete('/delete/{id}', [OwnerController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/address')->group(function () {
    Route::get('/index', [AddressController::class, 'index']);
    Route::get('/show/{id}', [AddressController::class, 'show']);
    Route::post('/register', [AddressController::class, 'store']);
    Route::patch('/update/{id}', [AddressController::class, 'update']);
    Route::delete('/delete/{id}', [AddressController::class, 'delete']);
});

Route::prefix('/cities')->group(function () {
    Route::get('/index', [CityController::class, 'index']);
    Route::get('/show/{id}', [CityController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('/cities')->group(function () {
    Route::get('/states/{state}', [CityController::class, 'states']);
});

Route::middleware('auth:sanctum')->prefix('/maps')->group(function () {
    Route::get('/index', [MapsController::class, 'index']);
    Route::get('/show/{id}', [MapsController::class, 'show']);
    Route::post('/register', [MapsController::class, 'store']);
    Route::patch('/update/{id}', [MapsController::class, 'update']);
    Route::delete('/delete/{id}', [MapsController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/sectors')->group(function () {
    Route::get('/index', [SectorController::class, 'index']);
    Route::get('/show/{id}', [SectorController::class, 'show']);
    Route::post('/register', [SectorController::class, 'store']);
    Route::patch('/update/{id}', [SectorController::class, 'update']);
    Route::delete('/delete/{id}', [SectorController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/ticket-events')->group(function () {
    Route::get('/index', [TicketEventController::class, 'index']);
    Route::get('/show/{id}', [TicketEventController::class, 'show']);
    Route::post('/register', [TicketEventController::class, 'store']);
    Route::patch('/update/{id}', [TicketEventController::class, 'update']);
    Route::delete('/delete/{id}', [TicketEventController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/batchs')->group(function () {
    Route::get('/index', [BatchController::class, 'index']);
    Route::get('/show/{id}', [BatchController::class, 'show']);
    Route::post('/register', [BatchController::class, 'store']);
    Route::patch('/update/{id}', [BatchController::class, 'update']);
    Route::delete('/delete/{id}', [BatchController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/tickets')->group(function () {
    Route::get('/index', [TicketController::class, 'index']);
    Route::get('/show/{id}', [TicketController::class, 'show']);
    Route::post('/register', [TicketController::class, 'store']);
    Route::patch('/update/{id}', [TicketController::class, 'update']);
    Route::delete('/delete/{id}', [TicketController::class, 'delete']);

    Route::get('/user/{id}', [TicketController::class, 'showByUsers']);

    Route::post('/courtesies', [TicketController::class, 'courtesiesStore']);

    Route::get('/ticket-pdf/{ticket}', [TicketController::class, 'ticketPdf']);
    Route::get('/checkin/{event}/{ticket}', [TicketController::class, 'checkin']);
    Route::post('/validate-checkin', [TicketController::class, 'validateCheckin']);

});

Route::prefix('/checkout')->group(function () {
    Route::get('/bag/{bag}', [BagController::class, 'checkout']);
    Route::get('/coupon/{cupon}/{event}', [CouponController::class, 'apply']);
    Route::get('/checkPayment/{sale}', [SaleController::class, 'checkPayment']);
    Route::get('/payment/{sales}', [SaleController::class, 'verify']);
    Route::post('/payment', [SaleController::class, 'payment']);
});

Route::prefix('/webhook')->group(function () {
    Route::post('/cobranca', [SaleController::class, 'receivedPayment']);
    Route::post('/authenticate', [SaleController::class, 'authenticateRefund']);
    Route::post('/log-sendgrid', [CommonController::class, 'genereateLogSendgrid']);
});

Route::prefix('/bags')->group(function () {
    Route::post('/start', [BagController::class, 'start']);
});

Route::middleware('auth:sanctum')->prefix('/bags')->group(function () {
    Route::get('/index', [BagController::class, 'index']);
    Route::get('/show/{id}', [BagController::class, 'show']);
    Route::post('/register', [BagController::class, 'store']);
    Route::patch('/update/{id}', [BagController::class, 'update']);
    Route::delete('/delete/{id}', [BagController::class, 'delete']);
});

Route::prefix('/sales')->group(function () {
    Route::post('/payment', [SaleController::class, 'payment']);
});

Route::middleware('auth:sanctum')->prefix('/sales')->group(function () {
    Route::get('/index', [SaleController::class, 'index']);
    Route::get('/show/{id}', [SaleController::class, 'show']);
    Route::get('/moviment', [SaleController::class, 'moviment']);
    Route::get('/moviment/donwload', [SaleController::class, 'movimentDonwload']);
    Route::patch('/update/{id}', [SaleController::class, 'update']);
    Route::delete('/delete/{id}', [SaleController::class, 'delete']);

    Route::post('/refund', [SaleController::class, 'refundSales']);
});

Route::middleware('auth:sanctum')->prefix('/coupons')->group(function () {
    Route::get('/index', [CouponController::class, 'index']);
    Route::get('/show/{id}', [CouponController::class, 'show']);
    Route::post('/register', [CouponController::class, 'store']);
    Route::patch('/update/{id}', [CouponController::class, 'update']);
    Route::delete('/delete/{id}', [CouponController::class, 'delete']);
});

Route::middleware('auth:sanctum')->prefix('/withdrawal')->group(function () {

    Route::get('/index', [WithdrawalController::class, 'index']);

    Route::get('/history', [WithdrawalController::class, 'history']);

    Route::get('/generatecode', [WithdrawalController::class, 'generateCode']);
    Route::post('/register', [WithdrawalController::class, 'store']);

    Route::get('/show/{id}', [WithdrawalController::class, 'show']);
    Route::patch('/update/{id}', [WithdrawalController::class, 'update']);

    Route::delete('/delete/{id}', [WithdrawalController::class, 'delete']);
});


Route::middleware('auth:sanctum')->prefix('/adm-master')->group(function () {
    Route::get('/dashboard', [AdmMasterController::class, 'dashboard']);
});


Route::prefix('/jobs-execute')->group(function () {

    Route::get('/start-queue', function () {
        shell_exec("cd .. && /www/server/php/82/bin/php artisan queue:work --stop-when-empty");
    });

    Route::get('/clear-tickets', [TicketController::class, 'clearTickets']);
    Route::get('/libered-value', [WithdrawalController::class, 'liberedValues']);

});

