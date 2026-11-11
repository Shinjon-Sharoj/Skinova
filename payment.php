<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth-check.php';

$orderId  = get_int('order');
$order    = $orderId ? get_order($orderId, (int) $authUser['id']) : null;

if (!$order) not_found();
if ($order['payment_method'] !== 'online') redirect('order-details.php?id=' . $orderId);
if ($order['payment_status'] === 'paid') redirect('order-success.php?id=' . $orderId);
if (!in_array($order['status'], ['pending'], true)) redirect('order-details.php?id=' . $orderId);

$pageTitle        = 'Payment';
$hideFloatingChat = true;
$provider         = online_payment_provider(); // 'sandbox' or 'stripe'

include __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:540px">
    <h1 class="h3 mb-4">Complete Payment</h1>

    <!-- Order info -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <span class="text-muted">Order</span>
                <strong><?= e($order['order_number']) ?></strong>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <span class="text-muted">Amount due</span>
                <strong class="text-primary fs-5"><?= e(money($order['total'])) ?></strong>
            </div>
        </div>
    </div>

    <?php if ($provider === 'sandbox'): ?>
    <!-- Sandbox / test payment form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header"><i class="bi bi-credit-card me-1"></i> Test payment (sandbox)</div>
        <div class="card-body">
            <p class="small text-muted">This is a test gateway. No real charge will be made. Use any card details.</p>
            <form method="post" action="<?= e(url('actions/payments/sandbox-pay.php')) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <div class="mb-3">
                    <label for="card_number" class="form-label">Card number</label>
                    <input type="text" id="card_number" class="form-control" placeholder="4242 4242 4242 4242" maxlength="19" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="card_expiry" class="form-label">Expiry</label>
                        <input type="text" id="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="col-6">
                        <label for="card_cvc" class="form-label">CVC</label>
                        <input type="text" id="card_cvc" class="form-control" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-lock me-1"></i> Pay <?= e(money($order['total'])) ?>
                </button>
            </form>
        </div>
    </div>

    <?php elseif ($provider === 'stripe'): ?>
    <!-- Stripe payment -->
    <div class="card shadow-sm mb-4">
        <div class="card-header"><i class="bi bi-credit-card me-1"></i> Pay with card</div>
        <div class="card-body">
            <div id="stripe-payment-element"></div>
            <div id="stripe-errors" class="text-danger small mt-2"></div>
            <button id="stripePayBtn" class="btn btn-primary w-100 py-2 mt-3">
                <i class="bi bi-lock me-1"></i> Pay <?= e(money($order['total'])) ?>
            </button>
        </div>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
    (async () => {
        const res = await SKINOVA.api('payments/stripe-intent.php', 'POST', { order_id: <?= $order['id'] ?> });
        if (!res.ok) { document.getElementById('stripe-errors').textContent = res.message; return; }
        const stripe = Stripe(<?= json_encode(STRIPE_PUBLISHABLE_KEY) ?>);
        const elements = stripe.elements({ clientSecret: res.data.client_secret });
        const payEl = elements.create('payment');
        payEl.mount('#stripe-payment-element');
        document.getElementById('stripePayBtn').addEventListener('click', async () => {
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: { return_url: <?= json_encode(absolute_url('actions/payments/stripe-return.php?order=' . $order['id'])) ?> }
            });
            if (error) document.getElementById('stripe-errors').textContent = error.message;
        });
    })();
    </script>
    <?php endif; ?>

    <div class="text-center">
        <a href="<?= e(url('orders.php')) ?>" class="text-muted small">Cancel &amp; view orders</a>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
