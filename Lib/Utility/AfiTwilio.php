<?php
/**
 * AfiTwilio Utility: manda SMS por la API de Twilio.
 *
 * Antes armaba un `curl` con el texto pegado adentro y lo corría con `exec()`. Un nombre
 * con apóstrofo (D'Angelo) rompía el comando, uno con `;` o `$(...)` ejecutaba lo que
 * trajera en el servidor, y la clave de Twilio quedaba a la vista de cualquiera que
 * listara los procesos. Ahora el pedido sale por la extensión curl de PHP: los datos
 * viajan como campos del formulario y no pasan por ninguna shell.
 *
 * Las credenciales son las de siempre, de las variables de configuración de Afi Croogo:
 * `Afigestion.twilio_sid` y `Afigestion.token_auth_sms`.
 */
App::uses('CakeLog', 'Log');

class AfiTwilio {

	/** El número de Twilio que figura como remitente. */
	const REMITENTE = '(205) 619-1827';

	/** El Messaging Service de la cuenta. */
	const MESSAGING_SERVICE_SID = 'MG5e1f20853d4988ed2253881ee4c1d019';

	/** Segundos que se espera a Twilio antes de dar el envío por fallido. */
	const TIMEOUT = 10;

	/**
	 * Quién hace el pedido HTTP. Es reemplazable para los tests: recibe la URL, los campos
	 * y el `sid:token`, y devuelve el código HTTP de la respuesta (0 si no hubo respuesta).
	 *
	 * @var callable|null
	 */
	public static $transporte = null;

	/**
	 * Manda un SMS.
	 *
	 * @param string $telefono Destino, en el formato que acepta Twilio (+5491122334455).
	 * @param string $texto
	 * @return bool Si Twilio lo aceptó.
	 */
	public static function enviarSms($telefono, $texto) {
		$sid = Configure::read('Afigestion.twilio_sid');
		$token = Configure::read('Afigestion.token_auth_sms');
		$telefono = trim((string)$telefono);
		if (empty($sid) || empty($token) || $telefono === '') {
			return false;
		}

		$url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';
		$campos = array(
			'From' => self::REMITENTE,
			'To' => $telefono,
			'MessagingServiceSid' => self::MESSAGING_SERVICE_SID,
			'Body' => (string)$texto,
		);
		$transporte = self::$transporte ? self::$transporte : array(__CLASS__, 'post');
		$status = (int)call_user_func($transporte, $url, $campos, $sid . ':' . $token);
		return $status >= 200 && $status < 300;
	}

	/**
	 * El POST, con la extensión curl de PHP. Si Twilio no lo acepta, queda en el log de
	 * errores con lo que contestó.
	 *
	 * @param string $url
	 * @param array $campos
	 * @param string $credenciales `sid:token`
	 * @return int El código HTTP, o 0 si no hubo respuesta.
	 */
	public static function post($url, array $campos, $credenciales) {
		$curl = curl_init($url);
		curl_setopt_array($curl, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($campos),
			CURLOPT_USERPWD => $credenciales,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
			CURLOPT_TIMEOUT => self::TIMEOUT,
		));
		$respuesta = curl_exec($curl);
		$status = ($respuesta === false) ? 0 : (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($status < 200 || $status >= 300) {
			$detalle = ($respuesta === false) ? curl_error($curl) : $respuesta;
			CakeLog::write('error', 'Twilio no aceptó el SMS (HTTP ' . $status . '): ' . $detalle);
		}
		curl_close($curl);
		return $status;
	}

}
