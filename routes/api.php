<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| DEBUG / TESTING ENDPOINTS (Remove or protect in production!)
|--------------------------------------------------------------------------
*/
Route::prefix('debug')->group(function () {
    
    // Health check / ping
    Route::get('/ping', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is running',
            'timestamp' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ]);
    });

    // Database connection test
    Route::get('/db', function () {
        try {
            DB::connection()->getPdo();
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            return response()->json([
                'status' => 'ok',
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'tables' => array_map(fn($t) => $t->name, $tables),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    // Environment info
    Route::get('/env', function () {
        return response()->json([
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'sanctum_stateful' => config('sanctum.stateful'),
            'cors_allowed_origins' => config('cors.allowed_origins'),
        ]);
    });

    // List all registered routes
    Route::get('/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->middleware(),
            ];
        })->filter(fn($r) => str_starts_with($r['uri'], 'api/'))->values();
        
        return response()->json(['routes' => $routes]);
    });

    // Database stats
    Route::get('/stats', function () {
        return response()->json([
            'users' => User::count(),
            'users_by_role' => User::selectRaw('role, count(*) as count')->groupBy('role')->pluck('count', 'role'),
            'users_by_status' => User::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'products' => Product::count(),
            'products_by_status' => Product::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'orders' => Order::count(),
            'orders_by_status' => Order::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
        ]);
    });

    // Get all users (for testing login)
    Route::get('/users', function () {
        return response()->json([
            'users' => User::select('id', 'name', 'email', 'role', 'status', 'created_at')->get(),
            'note' => 'Default password for seeded users: "password"',
        ]);
    });

    // Get all products
    Route::get('/products', function () {
        return response()->json(Product::with('seller:id,name')->get());
    });

    // Get all orders
    Route::get('/orders', function () {
        return response()->json(Order::with(['client:id,name', 'product:id,title,price'])->get());
    });

    // Quick login (returns token without password for testing)
    Route::post('/quick-login', function (Request $request) {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $token = $user->createToken('debug')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'usage' => 'Add header: Authorization: Bearer ' . $token,
        ]);
    });

    // Test auth - check if token is valid
    Route::get('/auth-check', function (Request $request) {
        if (!$request->bearerToken()) {
            return response()->json([
                'authenticated' => false,
                'message' => 'No bearer token provided',
                'hint' => 'Add header: Authorization: Bearer YOUR_TOKEN',
            ], 401);
        }
        
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }
        
        return response()->json([
            'authenticated' => true,
            'user' => $user,
            'token_abilities' => $request->user('sanctum')?->currentAccessToken()?->abilities ?? [],
        ]);
    });

    // Reset database (fresh migrate + seed)
    Route::post('/reset-db', function () {
        if (config('app.env') === 'production') {
            return response()->json(['error' => 'Cannot reset in production'], 403);
        }
        
        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        
        return response()->json([
            'status' => 'ok',
            'message' => 'Database reset complete',
            'output' => Artisan::output(),
        ]);
    });

    // Create test user on the fly
    Route::post('/create-user', function (Request $request) {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:client,farmer,store_owner,admin',
            'status' => 'sometimes|in:pending,active,rejected',
        ]);
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);
        
        $token = $user->createToken('debug')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'password' => 'password',
        ], 201);
    });

    // Test CORS
    Route::options('/cors-test', function () {
        return response()->json(['cors' => 'ok']);
    });
    
    Route::get('/cors-test', function (Request $request) {
        return response()->json([
            'cors' => 'ok',
            'origin' => $request->header('Origin'),
            'allowed_origins' => config('cors.allowed_origins'),
        ]);
    });

    // Echo request (for debugging what's being sent)
    Route::match(['get', 'post', 'put', 'delete'], '/echo', function (Request $request) {
        return response()->json([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => collect($request->headers->all())->map(fn($v) => $v[0] ?? $v),
            'query' => $request->query(),
            'body' => $request->all(),
            'bearer_token' => $request->bearerToken() ? 'present (hidden)' : null,
        ]);
    });

});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Protected routes (require Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // Products (seller endpoints)
    Route::post('/products', [ProductController::class, 'store'])->middleware('role:farmer,store_owner');
    Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('role:farmer,store_owner');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('role:farmer,store_owner');
    Route::get('/products/stats', [ProductController::class, 'stats'])->middleware('role:farmer,store_owner');

    // Orders - client
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Orders - seller
    Route::get('/orders/seller', [OrderController::class, 'sellerOrders'])->middleware('role:farmer,store_owner');
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus'])->middleware('role:farmer,store_owner,admin');

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Payment stub
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/verify', [PaymentController::class, 'verify']);

    // Admin only
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/sellers', [AdminController::class, 'pendingSellers']);
        Route::post('/sellers/{id}/approve', [AdminController::class, 'approveSeller']);
        Route::post('/sellers/{id}/reject', [AdminController::class, 'rejectSeller']);

        Route::get('/orders', [AdminController::class, 'orders']);
        Route::post('/orders/{id}/validate', [AdminController::class, 'validateOrder']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::get('/products', [AdminController::class, 'products']);
        Route::delete('/products/{id}', [AdminController::class, 'deleteProduct']);

        Route::get('/reports', [AdminController::class, 'reports']);
    });
});
