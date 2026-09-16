<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // إعدادات الـ Middleware توضع هنا (إن وجدت)
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // 1. أخطاء التحقق (Validation)
        $exceptions->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 2. أخطاء "البيانات غير موجودة" (404 Not Found)
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'errors' => null,
                ], 404);
            }
        });

        // 3. أخطاء المصادقة (Unauthenticated 401)
        $exceptions->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'errors' => null,
                ], 401);
            }
        });

        // 4. أخطاء الصلاحيات (Forbidden 403)
        // نلتقط AccessDeniedHttpException لأنه النوع الذي يعتمده Laravel داخلياً
        $exceptions->renderable(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                    'errors' => null,
                ], 403);
            }
        });
        // وكذلك نلتقط AuthorizationException تحسباً لأي مسار آخر
        $exceptions->renderable(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                    'errors' => null,
                ], 403);
            }
        });

        // 5. أخطاء كثرة الطلبات (Rate Limiting - 429)
        $exceptions->renderable(function (ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'errors' => null,
                ], 429);
            }
        });

        // 6. الحماية الشاملة للـ API (Catch-all Fallback) لأي خطأ غير متوقع
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                // منع التداخل مع الأخطاء التي تم التعامل معها في الأعلى
                if (
                    $e instanceof ValidationException ||
                    $e instanceof AuthenticationException ||
                    $e instanceof AuthorizationException ||
                    $e instanceof NotFoundHttpException ||
                    $e instanceof AccessDeniedHttpException ||
                    $e instanceof ThrottleRequestsException
                ) {
                    return; // اتركها للمعالجات السابقة
                }

                // استخراج كود الخطأ (إذا كان HTTP Error مثل 405 نعيده كما هو، وإلا نعيد 500)
                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                return response()->json([
                    'success' => false,
                    // إخفاء رسائل الأخطاء الحساسة في الـ Production
                    'message' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
                    'errors' => null,
                ], $statusCode);
            }
        });
    })->create();
