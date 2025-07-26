<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My E-Commerce Site</title>
    <link rel="stylesheet" href="/bootstrap.min.css">
    <style>
        .price { font-size: 1.25rem; font-weight: bold; }
        .currency-symbol { font-size: 0.875rem; color: #6c757d; }
        .card-img-top {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background-color: #f8f9fa;
        }
        .no-image {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            background-color: #f0f0f0;
            color: #aaa;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-center">Products</h1>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <?php
                            $imageUrl = $product->getImage()->getUrl();
                            $hasImage = !empty($imageUrl);
                        ?>
                        <?php if ($hasImage): ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" class="card-img-top" alt="<?= htmlspecialchars($product->getName()) ?>">
                        <?php else: ?>
                            <div class="no-image">No image available</div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($product->getName()) ?></h5>
                            <p class="card-text price">
                                <span class="currency-symbol"><?= $product->getPrice()->getCurrencyCode() ?></span>
                                <?= number_format($product->getPrice()->getUnits() + $product->getPrice()->getNanos() / 1e9, 2) ?>
                            </p>
                            <a href="#" class="btn btn-primary mt-auto">Add to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</body>
</html>