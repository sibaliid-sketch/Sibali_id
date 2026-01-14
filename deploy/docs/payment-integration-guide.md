# Panduan Integrasi Metode Pembayaran Lokal/Internasional

## Alur Checkout

### Standard Checkout Flow
```php
// Checkout controller implementation
class CheckoutController extends Controller
{
    public function initiateCheckout(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|in:credit_card,bank_transfer,qris,gopay,ovo,dana',
        ]);

        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'course_id' => $request->course_id,
            'amount' => Course::find($request->course_id)->price,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
            'order_number' => 'ORD-' . strtoupper(uniqid()),
        ]);

        // Initialize payment based on method
        return $this->initializePayment($order, $request->payment_method);
    }

    private function initializePayment(Order $order, string $method)
    {
        return match($method) {
            'credit_card' => $this->initiateCreditCardPayment($order),
            'bank_transfer' => $this->initiateBankTransfer($order),
            'qris' => $this->initiateQRISPayment($order),
            'gopay' => $this->initiateEWalletPayment($order, 'gopay'),
            'ovo' => $this->initiateEWalletPayment($order, 'ovo'),
            'dana' => $this->initiateEWalletPayment($order, 'dana'),
        };
    }
}
```

### QRIS Flow Implementation
```php
// QRIS payment integration
class QRISPaymentService
{
    private $midtransConfig;

    public function __construct()
    {
        $this->midtransConfig = config('services.midtrans');
        \Midtrans\Config::$serverKey = $this->midtransConfig['server_key'];
        \Midtrans\Config::$isProduction = app()->environment('production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    public function createQRISPayment(Order $order)
    {
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $order->amount,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id' => $order->course->id,
                    'price' => $order->course->price,
                    'quantity' => 1,
                    'name' => Str::limit($order->course->title, 50),
                ]
            ],
            'qris' => [
                'acquirer' => 'gopay', // or 'airpay shopee'
            ],
        ];

        try {
            $response = \Midtrans\Snap::createTransaction($params);

            // Store QRIS data
            $order->update([
                'payment_token' => $response->token,
                'qris_url' => $response->redirect_url,
                'qris_string' => $this->generateQRISString($response),
            ]);

            return [
                'success' => true,
                'qris_string' => $order->qris_string,
                'qris_url' => $order->qris_url,
                'expires_at' => now()->addMinutes(15),
            ];
        } catch (\Exception $e) {
            Log::error('QRIS payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create QRIS payment',
            ];
        }
    }

    private function generateQRISString($response)
    {
        // Generate QRIS string from Midtrans response
        // This would typically come from the payment gateway
        return $response->qris_string ?? '';
    }
}
```

### Webhook Security Implementation
```php
// Secure webhook handling
class PaymentWebhookController extends Controller
{
    public function handleMidtransWebhook(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response('Invalid signature', 403);
        }

        $notification = $request->all();

        // Prevent replay attacks
        if ($this->isReplayAttack($notification)) {
            Log::warning('Replay attack detected', $notification);
            return response('Replay attack detected', 403);
        }

        // Process payment notification
        $this->processPaymentNotification($notification);

        return response('OK', 200);
    }

    private function verifyWebhookSignature(Request $request)
    {
        $signature = $request->header('X-Midtrans-Signature');
        $payload = $request->getContent();
        $serverKey = config('services.midtrans.server_key');

        $expectedSignature = hash('sha512', $payload . $serverKey);

        return hash_equals($expectedSignature, $signature);
    }

    private function isReplayAttack(array $notification)
    {
        $orderId = $notification['order_id'];
        $transactionId = $notification['transaction_id'];
        $status = $notification['transaction_status'];

        // Check if we've already processed this exact notification
        $existingNotification = DB::table('payment_notifications')
            ->where('order_id', $orderId)
            ->where('transaction_id', $transactionId)
            ->where('status', $status)
            ->first();

        if ($existingNotification) {
            return true;
        }

        // Store notification to prevent future replays
        DB::table('payment_notifications')->insert([
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'status' => $status,
            'payload' => json_encode($notification),
            'processed_at' => now(),
        ]);

        return false;
    }

    private function processPaymentNotification(array $notification)
    {
        $order = Order::where('order_number', $notification['order_id'])->first();

        if (!$order) {
            Log::error('Order not found for webhook', $notification);
            return;
        }

        $status = $notification['transaction_status'];

        switch ($status) {
            case 'capture':
            case 'settlement':
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $notification['transaction_id'],
                ]);

                // Enroll user in course
                $this->enrollUserInCourse($order);
                break;

            case 'pending':
                $order->update(['status' => 'pending']);
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
            case 'failure':
                $order->update(['status' => 'failed']);
                break;
        }

        Log::info('Payment notification processed', [
            'order_id' => $order->id,
            'status' => $status,
        ]);
    }
}
```

## Reconciliation Batch Jobs

### Daily Reconciliation Process
```php
// Reconciliation job
class ProcessPaymentReconciliation implements ShouldQueue
{
    public function handle()
    {
        $yesterday = now()->subDay()->toDateString();

        // Get payments from our system
        $ourPayments = Order::whereDate('paid_at', $yesterday)
            ->where('status', 'paid')
            ->get()
            ->keyBy('order_number');

        // Get payments from payment gateway
        $gatewayPayments = $this->getGatewayPayments($yesterday);

        // Compare and reconcile
        $this->reconcilePayments($ourPayments, $gatewayPayments);

        // Generate reconciliation report
        $this->generateReconciliationReport($ourPayments, $gatewayPayments);
    }

    private function getGatewayPayments($date)
    {
        // Fetch from Midtrans API
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.midtrans.com/v2/status', [
            'auth' => [
                config('services.midtrans.server_key'),
                ''
            ],
            'query' => [
                'from_date' => $date,
                'to_date' => $date,
            ]
        ]);

        $transactions = json_decode($response->getBody(), true);

        return collect($transactions)->keyBy('order_id');
    }

    private function reconcilePayments($ourPayments, $gatewayPayments)
    {
        $discrepancies = [];

        // Check for payments in our system but not in gateway
        foreach ($ourPayments as $orderNumber => $order) {
            if (!isset($gatewayPayments[$orderNumber])) {
                $discrepancies[] = [
                    'type' => 'missing_in_gateway',
                    'order_number' => $orderNumber,
                    'amount' => $order->amount,
                ];
            } elseif ($gatewayPayments[$orderNumber]['gross_amount'] != $order->amount) {
                $discrepancies[] = [
                    'type' => 'amount_mismatch',
                    'order_number' => $orderNumber,
                    'our_amount' => $order->amount,
                    'gateway_amount' => $gatewayPayments[$orderNumber]['gross_amount'],
                ];
            }
        }

        // Check for payments in gateway but not in our system
        foreach ($gatewayPayments as $orderNumber => $transaction) {
            if (!isset($ourPayments[$orderNumber])) {
                $discrepancies[] = [
                    'type' => 'missing_in_system',
                    'order_number' => $orderNumber,
                    'amount' => $transaction['gross_amount'],
                ];
            }
        }

        // Store discrepancies for manual review
        foreach ($discrepancies as $discrepancy) {
            PaymentDiscrepancy::create($discrepancy);
        }
    }

    private function generateReconciliationReport($ourPayments, $gatewayPayments)
    {
        $report = [
            'date' => now()->subDay()->toDateString(),
            'our_total_payments' => $ourPayments->count(),
            'our_total_amount' => $ourPayments->sum('amount'),
            'gateway_total_payments' => $gatewayPayments->count(),
            'gateway_total_amount' => collect($gatewayPayments)->sum('gross_amount'),
            'discrepancies' => PaymentDiscrepancy::whereDate('created_at', today())->count(),
        ];

        // Send report via email
        Mail::to('finance@sibali.id')->send(new ReconciliationReport($report));

        // Store report
        ReconciliationReport::create($report);
    }
}
```

### Test Vectors and Error Codes Mapping

### Payment Gateway Error Codes
```php
// Error code mapping and handling
class PaymentErrorHandler
{
    const ERROR_CODES = [
        // Midtrans error codes
        '200' => 'Success',
        '201' => 'Credit card authentication challenge',
        '202' => 'Credit card denied by bank',
        '300' => 'Payment method not available',
        '400' => 'Bad request',
        '401' => 'Unauthorized access',
        '402' => 'Payment required',
        '403' => 'Forbidden access',
        '404' => 'Transaction not found',
        '405' => 'HTTP method not allowed',
        '406' => 'Not acceptable',
        '407' => 'Proxy authentication required',
        '408' => 'Request timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length required',
        '412' => 'Precondition failed',
        '413' => 'Request entity too large',
        '414' => 'Request-URI too long',
        '415' => 'Unsupported media type',
        '416' => 'Requested range not satisfiable',
        '417' => 'Expectation failed',
        '500' => 'Internal server error',
        '501' => 'Not implemented',
        '502' => 'Bad gateway',
        '503' => 'Service unavailable',
        '504' => 'Gateway timeout',

        // Custom application errors
        '6001' => 'Invalid payment amount',
        '6002' => 'Payment method not supported',
        '6003' => 'Order already paid',
        '6004' => 'Payment expired',
        '6005' => 'Insufficient funds',
        '6006' => 'Card blocked',
        '6007' => 'Invalid card details',
        '6008' => 'Transaction limit exceeded',
    ];

    public static function getErrorMessage($code, $default = 'Unknown error')
    {
        return self::ERROR_CODES[$code] ?? $default;
    }

    public static function handlePaymentError($error, Order $order)
    {
        $errorCode = $error['error_code'] ?? 'unknown';
        $errorMessage = self::getErrorMessage($errorCode);

        // Log error
        Log::error('Payment error occurred', [
            'order_id' => $order->id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'gateway_response' => $error,
        ]);

        // Update order status
        $order->update([
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ]);

        // Send notification to user
        $order->user->notify(new PaymentFailedNotification($order, $errorMessage));

        // Trigger retry logic for certain errors
        if (in_array($errorCode, ['408', '502', '503', '504'])) {
            $this->schedulePaymentRetry($order);
        }
    }
}
```

### Test Card Numbers and Sandbox Endpoints
```php
// Test data for development and testing
class PaymentTestData
{
    const TEST_CARDS = [
        'visa' => [
            'number' => '4111111111111111',
            'expiry' => '12/25',
            'cvv' => '123',
            'name' => 'John Doe',
        ],
        'mastercard' => [
            'number' => '5111111111111118',
            'expiry' => '12/25',
            'cvv' => '123',
            'name' => 'Jane Smith',
        ],
        'amex' => [
            'number' => '371111111111114',
            'expiry' => '12/25',
            'cvv' => '1234',
            'name' => 'Bob Johnson',
        ],
    ];

    const SANDBOX_ENDPOINTS = [
        'midtrans' => 'https://api.sandbox.midtrans.com/v2/charge',
        'gopay' => 'https://api.sandbox.gopay.com/v1/payments',
        'ovo' => 'https://api.sandbox.ovo.id/v1.0/payments',
        'dana' => 'https://api.sandbox.dana.id/v1/payments',
    ];

    const TEST_SCENARIOS = [
        'success' => [
            'amount' => 100000,
            'expected_status' => 'settlement',
            'description' => 'Successful payment',
        ],
        'pending' => [
            'amount' => 150000,
            'expected_status' => 'pending',
            'description' => 'Payment pending approval',
        ],
        'failed' => [
            'amount' => 200000,
            'expected_status' => 'deny',
            'description' => 'Payment denied by bank',
        ],
        'expired' => [
            'amount' => 50000,
            'expected_status' => 'expire',
            'description' => 'Payment expired',
        ],
    ];
}
```

## Idempotency Keys and Retry Strategy

### Idempotency Implementation
```php
// Idempotency key handling
class PaymentService
{
    public function processPaymentWithIdempotency(Request $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            return response()->json(['error' => 'Idempotency-Key header required'], 400);
        }

        // Check if we've already processed this request
        $existingPayment = DB::table('idempotency_keys')
            ->where('key', $idempotencyKey)
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($existingPayment) {
            // Return the previous response
            return response()->json(json_decode($existingPayment->response, true), $existingPayment->status_code);
        }

        // Process the payment
        DB::beginTransaction();
        try {
            $result = $this->processPayment($request->all());

            // Store the idempotency key and response
            DB::table('idempotency_keys')->insert([
                'key' => $idempotencyKey,
                'response' => json_encode($result),
                'status_code' => 200,
                'created_at' => now(),
            ]);

            DB::commit();
            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorResponse = ['error' => $e->getMessage()];

            // Store failed response
            DB::table('idempotency_keys')->insert([
                'key' => $idempotencyKey,
                'response' => json_encode($errorResponse),
                'status_code' => 500,
                'created_at' => now(),
            ]);

            return response()->json($errorResponse, 500);
        }
    }
}
```

### Retry Strategy Implementation
```php
// Payment retry logic with exponential backoff
class PaymentRetryService
{
    const MAX_RETRIES = 3;
    const BASE_DELAY = 60; // seconds

    public function schedulePaymentRetry(Order $order)
    {
        if ($order->retry_count >= self::MAX_RETRIES) {
            Log::error('Max retries exceeded for order', ['order_id' => $order->id]);
            return;
        }

        $delay = $this->calculateDelay($order->retry_count);

        RetryPayment::dispatch($order)
            ->delay(now()->addSeconds($delay))
            ->onQueue('payment-retries');
    }

    private function calculateDelay($retryCount)
    {
        // Exponential backoff with jitter
        $delay = self::BASE_DELAY * pow(2, $retryCount);
        $jitter = random_int(0, 30); // Add up to 30 seconds of jitter

        return $delay + $jitter;
    }
}

class RetryPayment implements ShouldQueue
{
    public $tries = 1; // Only try once per dispatch
    public $maxExceptions = 1;

    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        $order = $this->order->fresh();

        if ($order->status !== 'failed') {
            return; // Already processed
        }

        try {
            // Attempt payment again
            $paymentService = app(PaymentService::class);
            $result = $paymentService->retryPayment($order);

            if ($result['success']) {
                $order->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'retry_count' => $order->retry_count + 1,
                ]);
            } else {
                // Schedule another retry if applicable
                $order->increment('retry_count');
                app(PaymentRetryService::class)->schedulePaymentRetry($order);
            }

        } catch (\Exception $e) {
            Log::error('Payment retry failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $order->increment('retry_count');
            app(PaymentRetryService::class)->schedulePaymentRetry($order);
        }
    }

    public function failed(\Exception $exception)
    {
        // Handle final failure
        $this->order->fresh()->update([
            'status' => 'permanently_failed',
            'final_failure_at' => now(),
        ]);

        // Notify user and admin
        $this->order->user->notify(new PaymentPermanentlyFailedNotification($this->order));
        Notification::route('slack', config('services.slack.payment_alerts'))
            ->notify(new PaymentRetryExhaustedNotification($this->order));
    }
}
```

## PCI Scoping Notes

### PCI DSS Compliance Considerations
```php
// PCI compliance implementation
class PCIComplianceService
{
    // Data that CANNOT be stored
    const PROHIBITED_DATA = [
        'full_card_number',
        'cvv',
        'pin',
        'magnetic_stripe_data',
    ];

    // Data that CAN be stored (with restrictions)
    const ALLOWED_DATA = [
        'last_four_digits',
        'expiry_month',
        'expiry_year',
        'card_brand',
        'tokenized_card_reference',
    ];

    public function validatePaymentData(array $paymentData)
    {
        foreach (self::PROHIBITED_DATA as $field) {
            if (isset($paymentData[$field])) {
                throw new PCIComplianceException("Storage of {$field} is prohibited by PCI DSS");
            }
        }

        return true;
    }

    public function sanitizePaymentData(array $paymentData)
    {
        $sanitized = [];

        foreach ($paymentData as $key => $value) {
            if (in_array($key, self::ALLOWED_DATA)) {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }

        return $sanitized;
    }

    private function sanitizeValue($value)
    {
        // Remove any potentially sensitive characters
        return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    public function logPCIViolation($violation, $context = [])
    {
        Log::critical('PCI DSS Violation Detected', [
            'violation' => $violation,
            'context' => $context,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Alert security team
        Notification::route('slack', config('services.slack.security'))
            ->notify(new PCIViolationAlert($violation, $context));
    }
}
```

## Monitoring for Payment Latency and Failed Transactions

### Payment Monitoring Implementation
```php
// Payment performance monitoring
class PaymentMonitoringService
{
    public function recordPaymentMetrics(Order $order, $startTime, $endTime)
    {
        $duration = $endTime - $startTime;

        // Record metrics
        $this->recordMetric('payment.duration', $duration, [
            'payment_method' => $order->payment_method,
            'amount' => $order->amount,
            'status' => $order->status,
        ]);

        // Check for slow payments
        if ($duration > 30) { // 30 seconds threshold
            Log::warning('Slow payment detected', [
                'order_id' => $order->id,
                'duration' => $duration,
                'payment_method' => $order->payment_method,
            ]);
        }
    }

    public function monitorFailedTransactions()
    {
        $failedRate = $this->calculateFailureRate();

        if ($failedRate > 0.05) { // 5% failure rate threshold
            $this->alertHighFailureRate($failedRate);
        }
    }

    private function calculateFailureRate()
    {
        $totalPayments = Order::where('created_at', '>=', now()->subHour())->count();
        $failedPayments = Order::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $totalPayments > 0 ? $failedPayments / $totalPayments : 0;
    }

    private function alertHighFailureRate($rate)
    {
        Notification::route('slack', config('services.slack.payment_alerts'))
            ->notify(new HighPaymentFailureRateAlert($rate));

        Log::error('High payment failure rate detected', [
            'failure_rate' => $rate,
            'time_window' => '1_hour',
        ]);
    }

    private function recordMetric($metric, $value, $tags = [])
    {
        // Send to monitoring service (e.g., DataDog, New Relic)
        if (config('services.datadog.enabled')) {
            \DataDog\Metrics::gauge($metric, $value, $tags);
        }

        // Store locally for reporting
        DB::table('payment_metrics')->insert([
            'metric' => $metric,
            'value' => $value,
            'tags' => json_encode($tags),
            'recorded_at' => now(),
        ]);
    }
}
```

## Implementation Checklist

### Payment Integration Setup
- [ ] Payment gateway accounts configured for sandbox and production
- [ ] Webhook endpoints secured and tested
- [ ] Idempotency handling implemented
- [ ] Error handling and user feedback configured
- [ ] PCI DSS compliance reviewed

### QRIS Implementation
- [ ] QRIS payment flow integrated
- [ ] QR code generation working
- [ ] Mobile wallet integrations tested
- [ ] Offline payment handling implemented

### Reconciliation Process
- [ ] Daily reconciliation job scheduled
- [ ] Discrepancy detection and alerting configured
- [ ] Manual reconciliation process documented
- [ ] Financial reporting automated

### Monitoring and Alerting
- [ ] Payment latency monitoring active
- [ ] Failure rate alerts configured
- [ ] Performance metrics being collected
- [ ] Incident response procedures documented

### Testing and Validation
- [ ] All payment methods tested with test cards
- [ ] Webhook signature validation verified
- [ ] Idempotency handling tested
- [ ] Error scenarios covered
- [ ] Load testing completed

## Security Considerations

### Data Protection
- Never store sensitive payment data
- Use tokenization for card references
- Implement proper encryption for stored data
- Regular security audits of payment systems

### Fraud Prevention
- Implement velocity checks
- Monitor for suspicious patterns
- Use fraud detection services
- Implement chargeback handling procedures

### Compliance
- Maintain PCI DSS compliance
- Regular security assessments
- Documented incident response procedures
- Employee training on security procedures

## Support and Maintenance

### Payment Gateway Support
- **Midtrans**: support@midtrans.com | 021-806-09-777
- **GoPay**: support@gopay.co.id | 1500-366
- **OVO**: support@ovo.id | 1500-696
- **DANA**: support@dana.id | 1500-368

### Monitoring Contacts
- **Payment Alerts**: payment-alerts@sibali.id
- **Finance Team**: finance@sibali.id
- **DevOps Team**: devops@sibali.id
- **Security Team**: security@sibali.id

### Emergency Procedures
1. **Payment System Down**: Contact payment gateway support immediately
2. **Fraud Suspected**: Isolate affected systems and contact security team
3. **Data Breach**: Follow incident response plan and notify authorities
4. **High Failure Rate**: Check gateway status and scale infrastructure if needed
