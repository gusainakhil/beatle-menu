<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(brand('company_name')) ?> - Authentication</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '<?= brand('colors.navy') ?>',
                        'navy-deep': '<?= brand('colors.navy_deep') ?>',
                        orange: '<?= brand('colors.orange') ?>',
                        teal: '<?= brand('colors.teal') ?>',
                        ink: '<?= brand('colors.ink') ?>',
                        muted: '<?= brand('colors.muted') ?>',
                        tint: '<?= brand('colors.card_tint') ?>',
                        'tint-warm': '<?= brand('colors.card_tint_warm') ?>',
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-navy-deep min-h-screen flex items-center justify-center p-4">
    <?= $content ?>
</body>
</html>
