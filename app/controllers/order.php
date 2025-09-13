<?php
// Controller: order result page – returns data for public/order.php
require_once __DIR__ . '/../../src/BarqouqClient.php';
require_once __DIR__ . '/../../src/CheckoutUtil.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$orderSession = isset($_GET['session']) ? (string)$_GET['session'] : null;

$order = null; $error = null; $items = []; $breakdown = ['currency'=>null,'subtotal'=>null,'shipping_fee'=>null,'payment_fee'=>null,'discount'=>null,'total'=>null];

try {
    // Prefer resolving by session when available
    if ($orderSession) {
        $fo = \App\CheckoutUtil::findOrderBySession($orderSession);
        if (empty($fo['error']) && !empty($fo['order'])) {
            $order = $fo['order'];
            if ($orderId === null && method_exists($order,'getOrderId')) {
                try { $orderId = (int)$order->getOrderId(); } catch (\Throwable $e) {}
            }
        }
    }

    // Fallback: lookup by id when not found via session
    if (!$order && $orderId) {
        $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
        $req = new \Barqouq\Shopfront\Order\OrderRequest();
        \BarqouqClient::applyAuth($req);
        // Most clients expect Order inside OrderRequest for FindById
        $o = new \Barqouq\Shared\Order(); $o->setOrderId((int)$orderId); $req->setOrder($o);
        list($rep,$st) = $client->FindOrderById($req)->wait();
        if (($st->code??0)===0 && $rep instanceof \Barqouq\Shared\OrderReply && method_exists($rep,'hasOrder') && $rep->hasOrder()) {
            $order = $rep->getOrder();
        } else {
            $error = ['message' => 'Order not found'];
        }
    }

    // Build items and totals if we have an order
    if ($order instanceof \Barqouq\Shared\Order) {
        // Items list supports both getItems() and getProducts()
        $itemsGetter = null;
        if (method_exists($order, 'getItems')) { $itemsGetter = 'getItems'; }
        elseif (method_exists($order, 'getProducts')) { $itemsGetter = 'getProducts'; }
        $list = $itemsGetter ? (array)call_user_func([$order, $itemsGetter]) : [];
        foreach ($list as $it) {
            $name = method_exists($it,'getName') ? (string)$it->getName() : '';
            $qty = method_exists($it,'getQty') ? (int)$it->getQty() : (method_exists($it,'getQuantity') ? (int)$it->getQuantity() : 1);
            $unit = null; $total = null; $currency = '';
            if (method_exists($it,'getUnitPrice') && $it->getUnitPrice()) {
                $m = $it->getUnitPrice();
                $unit = (method_exists($m,'getUnits')? (float)$m->getUnits() : 0) + (method_exists($m,'getNanos')? ((float)$m->getNanos()/1e9) : 0);
                $currency = method_exists($m,'getCurrencyCode')? (string)$m->getCurrencyCode() : '';
            } elseif (method_exists($it,'getPrice') && $it->getPrice()) {
                $m = $it->getPrice();
                $unit = (method_exists($m,'getUnits')? (float)$m->getUnits() : 0) + (method_exists($m,'getNanos')? ((float)$m->getNanos()/1e9) : 0);
                $currency = method_exists($m,'getCurrencyCode')? (string)$m->getCurrencyCode() : '';
            }
            if (method_exists($it,'getTotalPrice') && $it->getTotalPrice()) {
                $m = $it->getTotalPrice();
                $total = (method_exists($m,'getUnits')? (float)$m->getUnits() : 0) + (method_exists($m,'getNanos')? ((float)$m->getNanos()/1e9) : 0);
                $currency = $currency ?: (method_exists($m,'getCurrencyCode')? (string)$m->getCurrencyCode() : '');
            } elseif (method_exists($it,'getTotal') && $it->getTotal()) {
                $m = $it->getTotal();
                $total = (method_exists($m,'getUnits')? (float)$m->getUnits() : 0) + (method_exists($m,'getNanos')? ((float)$m->getNanos()/1e9) : 0);
                $currency = $currency ?: (method_exists($m,'getCurrencyCode')? (string)$m->getCurrencyCode() : '');
            }
            $items[] = ['name'=>$name,'qty'=>$qty,'unit'=>$unit,'total'=>$total,'currency'=>$currency];
        }

        // Totals breakdown via dynamic getter selection
        $extract = function($money){ if(!$money) return [null,null]; $u=method_exists($money,'getUnits')?(int)$money->getUnits():0; $n=method_exists($money,'getNanos')?(int)$money->getNanos():0; $c=method_exists($money,'getCurrencyCode')?(string)$money->getCurrencyCode():null; $v=$u + ($n/1e9); return [$v,$c]; };
        $get = function($obj, array $cands) {
            foreach ($cands as $m) { if (method_exists($obj, $m)) { return call_user_func([$obj, $m]); } }
            return null;
        };
        $m = $get($order, ['getSubtotalMoney','getSubtotal']); if ($m) { [$v,$c]=$extract($m); $breakdown['subtotal']=$v; $breakdown['currency']=$c?:$breakdown['currency']; }
        $m = $get($order, ['getShippingFeeMoney','getShippingFee']); if ($m) { [$v,$c]=$extract($m); $breakdown['shipping_fee']=$v; $breakdown['currency']=$c?:$breakdown['currency']; }
        $m = $get($order, ['getPaymentFeeMoney','getPaymentFee']); if ($m) { [$v,$c]=$extract($m); $breakdown['payment_fee']=$v; $breakdown['currency']=$c?:$breakdown['currency']; }
        $m = $get($order, ['getDiscountMoney','getDiscount']); if ($m) { [$v,$c]=$extract($m); $breakdown['discount']=$v; $breakdown['currency']=$c?:$breakdown['currency']; }
        $m = $get($order, ['getTotalMoney','getTotalAmount']); if ($m) { [$v,$c]=$extract($m); $breakdown['total']=$v; $breakdown['currency']=$c?:$breakdown['currency']; }
    }
} catch (\Throwable $e) {
    $error = ['message'=>'Exception','details'=>$e->getMessage()];
}

return compact('order','orderId','orderSession','items','breakdown','error');
