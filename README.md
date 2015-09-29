Yolo
====

Advanced retry implementation.

## Installation

```cmd
composer require gos/yolo
```

## Example

Perform an action

```php
$pusher->push($notification); #send notification over network
```

With YOLO :

```php
use Gos\Component\Yolo\Yolo;

$maxRetry = 10; // Default is 5
$timeout = 10; // Default is 5 (in second), set 0 for not timeout
$allowedException = ['RuntimeException']; // empty by default, consider exception as a "success"
$yoloPush = new Yolo(array($pusher, 'push'), array($notification), $maxRetry, $timeout);
$yoloPush->run();
```

Sometimes we need more swag to retry over a webservice.

```php
use Gos\Component\Yolo\Yolo;

$yoloPush = new Yolo(array($pusher, 'push'), array($notification));
$yoloPush->tryUntil(function(){
    $result = false;
    if ($fp = @fsockopen('my-web-service.dev', 1337, $errCode, $errStr, 1)) {
        $result = true;
        fclose($fp);
    }

    return $result;
});
```

If your operation have an hight cost, perform it when service is available instead of dummy retry.

You also can do :

```php
use Gos\Component\Yolo\Yolo;

$yoloPush = new Yolo(array($pusher, 'push'), array($notification));
$yoloPush->tryUntil($pusher);
```

By implementing `Gos\Component\Yolo\YoloInterface` on your object. Add `isAvailable` and return true when it's ok.

You also can attach a logger to yolo (We implement `Psr\Log\LoggerAwareInterface`) . Swag

```php
use Gos\Component\Yolo\Yolo;

$yolo = new Yolo(function(){});
$yolo->setLogger($mySwagPsrLogger);
```

## Built in Callback

#### Ping Back

```php
use Gos\Component\Yolo\Yolo;
use Gos\Component\Yolo\Callback\PingBack;

$pingger = new PingBack('127.0.0.1', 80);

$yoloPush = new Yolo(function(){});
$yoloPush->tryUntil($pingger);
```


