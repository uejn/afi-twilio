# afi-twilio

Plugin de CakePHP 2 que manda SMS por la API de Twilio. Lo usa AfiGestión, que tiene una copia
en `Plugin/Twilio/`: si cambiás algo acá, copialo allá en el mismo cambio.

```php
App::uses('AfiTwilio', 'Twilio.Lib/Utility');

AfiTwilio::enviarSms('+5491122334455', 'El texto del mensaje');  // true si Twilio lo aceptó
```

Las credenciales salen de la configuración de Afi Croogo: `Afigestion.twilio_sid` y
`Afigestion.token_auth_sms`. Si Twilio no acepta el mensaje, lo que contestó queda en el log
de errores.

El pedido sale por la extensión curl de PHP, nunca por una shell: el texto viaja como campo del
formulario, así que puede traer comillas o lo que sea.

Los tests: `Console/cake test Twilio Lib/Utility/AfiTwilio`.
