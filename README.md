# Reviu PHP Client

Client PHP ufficiale per pianificare richieste di recensione tramite Reviu API v1.

## Requisiti

- PHP 7.3 o successivo
- estensione PHP cURL
- un token API Reviu

## Installazione

```bash
composer require reviu/php-client:^0.1
```

## Configurazione

Conserva il token fuori dal codice, per esempio nella variabile d'ambiente
`REVIU_API_TOKEN`:

```php
<?php

require 'vendor/autoload.php';

$reviu = new Reviu\Client(getenv('REVIU_API_TOKEN'));
```

Il client usa automaticamente `https://reviu.online`. È possibile specificare
un URL diverso come secondo argomento del costruttore per un ambiente dedicato
di collaudo.

## Pianificare una richiesta

```php
$job = $reviu->scheduleReview(
    array(
        'external_code' => 'C-84721',
        'name' => 'Mario Rossi',
        'email' => 'mario@example.com',
        'mobile' => '+393331234567',
    ),
    'ordine-84721-recensione',
    new DateTimeImmutable(
        'tomorrow 18:30',
        new DateTimeZone('Europe/Rome')
    )
);

echo $job['id'];
```

`external_code` identifica il cliente nel gestionale di origine. È necessario
indicare almeno uno tra `email` e `mobile`.

Il secondo parametro è la chiave di idempotenza. Deve identificare stabilmente
l'operazione nel gestionale: ripetendo la chiamata con la stessa chiave non
viene creato un secondo job.

La data è facoltativa. Se viene omessa, la richiesta è disponibile per il
primo ciclo di elaborazione utile:

```php
$job = $reviu->scheduleReview(
    array(
        'external_code' => 'C-84722',
        'name' => 'Giulia Bianchi',
        'email' => 'giulia@example.com',
    ),
    'ordine-84722-recensione'
);
```

## Controllare lo stato

```php
$job = $reviu->getReviewRequest(42);

echo $job['status']; // pending, processing, sent oppure failed
```

## Gestire gli errori

```php
try {
    $job = $reviu->getReviewRequest(42);
} catch (Reviu\ApiException $exception) {
    echo $exception->getHttpStatus();
    echo $exception->getApiCode();
    print_r($exception->getDetails());
}
```

Gli errori di configurazione locale, come un token malformato o una chiave di
idempotenza non valida, generano `InvalidArgumentException`.

## Sicurezza

- usa il token solamente lato server;
- non inserire il token in URL, JavaScript o repository Git;
- assegna un token distinto a ogni ambiente o integrazione;
- conserva la stessa chiave di idempotenza quando ritenti una chiamata il cui
  esito non è noto.

## Licenza

MIT
