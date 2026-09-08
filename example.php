<?php

require __DIR__ . '/vendor/autoload.php';

$reviu = new Reviu\Client(getenv('REVIU_API_TOKEN'));

try {
    $job = $reviu->scheduleReview(
        array(
            'external_code' => 'C-84721',
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'mobile' => '+393331234567',
        ),
        'order-84721-review-v1',
        new DateTimeImmutable('tomorrow 18:30', new DateTimeZone('Europe/Rome'))
    );

    echo 'Job creato: ' . $job['id'] . PHP_EOL;
} catch (Reviu\ApiException $exception) {
    echo 'Errore Reviu [' . $exception->getApiCode() . ']: ' . $exception->getMessage() . PHP_EOL;
}
