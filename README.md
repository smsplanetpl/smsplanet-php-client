# SMSPLANET PHP Client

## Wprowadzenie

Platforma SMSPLANET.PL umożliwia masową rozsyłkę SMS-ów oraz MMS-ów marketingowych. Umożliwiamy integrację naszej platformy z dowolnym systemem komputerowym za pomocą opisanego w niniejszym dokumencie API.

### Rozpoczęcie współpracy

Aby zacząć korzystać z platformy należy założyć konto w serwisie SMSPLANET pod adresem https://www.smsplanet.pl. Następnie należy uzupełnić dane firmy w zakładce 'Mój Profil' oraz doładować konto punktami (PrePaid) lub podpisać umowę abonamentową (PostPaid) co umożliwi wysyłkę wiadomości.

### Klucz API

Każdy użytkownik systemu posiada unikalny klucz API, który należy przekazywać we wszystkich żądaniach HTTP wysyłanych do SMSPLANET. Klucz pozwala na identyfikację użytkownika, pełni rolę loginu dla interfejsu API. Swój klucz można znaleźć w zakładce 'API'.

### Hasło do API

Hasło do interfejsu API po rejestracji nowego konta jest takie samo jak hasło do panelu WWW. Aby zmienić hasło do API, należy skorzystać z formularza w zakładce 'API'. Hasło do API oraz hasło do panelu WWW są od siebie niezależne.

## PHP Client

### Instalacja pakietu

```composer require smsplanet/smsplanet-php-client```

### Autoryzacja

Podczas inicjalizacji klienta, należy podać klucz i hasło do API.

```php
<?php
    
$client = new \SMSPLANET\PHP\Client([
    'key' => '<KLUCZ API>',
    'password' => '<HASŁO DO API>'
]);

// (...)
```

### Wysyłanie wiadomości SMS

```php
<?php

$client = new \SMSPLANET\PHP\Client(/* init */);

// Wersja podstawowa
    
$message_id = $client->sendSimpleSMS([
    'from' => 'TEST',             // Nazwa nadawcy zgodnie z ustawieniami konta
    'to' => '48xxxxxxxxx',
    'msg' => 'Treść wiadomości'
]);
  
// Wersja zaawansowana
    
$message_id = $client->sendSMS([ 
    'from' => 'TEST',                 // Nazwa nadawcy zgodnie z ustawieniami konta
    'to' => '48xxxxxxxxx',   
    'msg' => 'Treść wiadomości',
    'date' => '21-05-2017 10:05:00',
    'clear_polish' => 1,      
    'test' => 1,                  
]);
```

### Wysyłka MMS

```php
<?php

$message_id = $client->sendMMS([
    'from' => 'TEST',
    'to' => '<numer abonenta>',
    'msg' => 'Treść wiadomości',
    'title' => 'Tytuł wiadomości',
    'attachments' => '<http://adres.do.obrazka.pl/obrazek.jpg>',
    'clear_polish' => 1,
    'date' => '21-05-2017 10:05:00',
    'test' => 1,
]);
```


Opis parametrów:

#### `from` 

Widoczna przez odbiorców nazwa nadawcy SMS. W przypadku wysyłek jednokierunkowych można korzystać z nazw domyślnych lub z nazw
App World 2017 wcześniej zdefiniowanych w panelu www (zakładka _Pole nadawcy_) i zaakceptowanych
przez administrację serwisu. W przypadku komunikacji dwukierunkowej (2WAY), należy
podać specjalny numer telefonu dedykowany do
komunikacji dwustronnej. 

Parametr jest wymagany.

#### `to`

Numer odbiorcy wiadomości. Dozwolone formaty:
* [0-9]{9} tj. XXXXXXXXX
* 48[0-9]{9} tj. 48XXXXXXXXX

Element ten można zdefiniować jako tablicę numerów, co spowoduje wysłanie danej wiadomości do wielu odbiorców na raz.
* Maksymalna ilość odbiorców w jednym żądaniu wynosi 10000.
* Nieprawidłowe numery zostaną pominięte.
* Jeśli numer występuje 2 lub więcej razy, duplikaty zostaną pominięte.

Parametr jest wymagany.
    
#### `msg`

Treść wiadomości. Pojedynczy SMS może mieć długość 160 znaków lub 70 znaków jeśli w wiadomości występuje przynajmniej jeden znak
specjalny (w tym polskie znaki). 

Jeśli treść wiadomości jest dłuższa zostanie podzielona na kilka SMS (max. 6).

Parametr jest wymagany.

#### `date`

Data określająca kiedy wiadomość ma być wysłana. Brak daty lub data przeszła spowodują natychmiastowe wysłanie wiadomości.

Dozwolone formaty:

* Unixtime (np. 1276623871)
* Y-m-d H:i:s (np. 2019-07-20 10:05:00)
* d-m-Y H:i:s (np. 20-07-2019 10:05:00)

Rozsyłki są planowane wg polskiej strefy czasowej.

#### `clear_polish`

Jeśli wartość tego parametru wynosi `1` to wszystkie polskie znaki w treści wiadomości zostaną zastąpione na swoje odpowiedniki, np. ą=a, ć=c, ł=l, itd.

#### `title`

Tytuł MMS. Nie wszystkie telefony wyświetlają to pole, zależy to od danego modelu. Przed
skorzystaniem z tego pola należy się upewnić, że telefon odbiorcy wyświetla tytuł MMS.

#### `test`

Jeśli wartość tego parametru wynosi `1`, wiadomość nie jest wysyłana. Służy celom testowym.

#### `attachments`

Należy podać adres url do załącznika.

### Lista pól nadawcy

```php
$lista = $client->getSenderFields($product);
```

API umożliwia pobranie listy dostępnych pól nadawcy. Pola te można wykorzystać jako wartość parametru `from`.
Domyślnie zwracana jest lista pól nadawcy dla produktu SMS. Aby pobrać listę pól nadawcy dla innych produktów np. MMS lub 2WAY,
należy podać dodatkowy parametr `$product`. Możliwe wartości parametru `$product` to:
* SMS
* MMS
* 2WAY

### Sprawdzenie stanu konta

```php
$balance = $client->getBalance($product);
```

API umożliwia sprawdzenie stanu konta tj. ilości punktów do wykorzystania na wysyłki SMS / MMS / 2WAY.

Możliwe wartości parametru `$product` to:
* SMS
* MMS
* 2WAY

### Anulowanie zaplanowanej wysyłki

```php
$client->cancelMessage($message_id);
```

API umożliwia anulowanie zaplanowanej (nie zrealizowanej) wysyłki.
Jeśli operacja przebiegnie poprawnie, środki pobrane za wysyłkę są
zwracane na saldo konta (w przypadku konta pre-paid).

### Sprawdzenie statusu wysyłki

```php
$status = $client->getMessageStatus($message_id);
    
/* przykładowa odpowiedź

$status = [
    'from' => [
        'Pole nadawcy' => 'TEST',
        'Nazwa wysyłki' => '',
        'Treść wiadomości' => 'Test SMS',
        'Data wysyłki' => '2019-07-01 16:52:47',
        'Wysłane' => '1',
        'Dostarczone' => '1',
        'Zwroty' => '0',
    ],
    'to' => [
        [
          'Numer telefonu' => 'XXXXXXXX',
          'Dostarczono' => 'TAK',
          'Data dostarczenia' => '2019-07-01 16:52:49',
          'Powód odrzucenia' => '',
          'Pobrano opłatę' => 'TAK',
        ],
        [
          'Numer telefonu' => 'YYYYYYYYY',
          'Dostarczono' => 'TAK',
          'Data dostarczenia' => '2019-07-01 16:52:50',
          'Powód odrzucenia' => '',
          'Pobrano opłatę' => 'TAK',
        ],
    ],
];

*/
    
```

API umożliwia sprawdzenie szczegółów dotyczących zaplanowanej lub zrealizowanej wysyłki. 

