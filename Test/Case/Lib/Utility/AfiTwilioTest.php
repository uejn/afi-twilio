<?php
App::uses('AfiTwilio', 'Twilio.Lib/Utility');

/**
 * El SMS sale como campos de un POST, nunca como un comando.
 *
 * El transporte se reemplaza por uno que anota el pedido: acá no se le habla a Twilio.
 */
class AfiTwilioTest extends CakeTestCase {

	/**
	 * Los pedidos que llegaron al transporte.
	 *
	 * @var array
	 */
	public $pedidos = array();

	/**
	 * El código HTTP que contesta el transporte.
	 *
	 * @var int
	 */
	public $respuesta = 201;

	public function setUp() {
		parent::setUp();
		Configure::write('Afigestion.twilio_sid', 'ACprueba');
		Configure::write('Afigestion.token_auth_sms', 'clave-de-prueba');
		$this->pedidos = array();
		$this->respuesta = 201;
		$test = $this;
		AfiTwilio::$transporte = function ($url, $campos, $credenciales) use ($test) {
			$test->pedidos[] = compact('url', 'campos', 'credenciales');
			return $test->respuesta;
		};
	}

	public function tearDown() {
		AfiTwilio::$transporte = null;
		parent::tearDown();
	}

	public function testElTextoViajaTalCualAunqueTraigaComillasOComandos() {
		$texto = "Afigestion TOKEN: 1234. El usuario Juan D'Angelo; touch /tmp/x \$(id) `id` acaba de solicitar acceso restringido.";

		$this->assertTrue(AfiTwilio::enviarSms('+5491122334455', $texto));

		$this->assertCount(1, $this->pedidos);
		$this->assertEquals('https://api.twilio.com/2010-04-01/Accounts/ACprueba/Messages.json', $this->pedidos[0]['url']);
		$this->assertEquals($texto, $this->pedidos[0]['campos']['Body']);
		$this->assertEquals('+5491122334455', $this->pedidos[0]['campos']['To']);
		$this->assertEquals(AfiTwilio::MESSAGING_SERVICE_SID, $this->pedidos[0]['campos']['MessagingServiceSid']);
		$this->assertEquals('ACprueba:clave-de-prueba', $this->pedidos[0]['credenciales']);
	}

	public function testSiTwilioNoLoAceptaDevuelveFalse() {
		$this->respuesta = 400;
		$this->assertFalse(AfiTwilio::enviarSms('+5491122334455', 'hola'));

		$this->respuesta = 0;
		$this->assertFalse(AfiTwilio::enviarSms('+5491122334455', 'hola'));
	}

	public function testSinTelefonoNoManda() {
		$this->assertFalse(AfiTwilio::enviarSms('', 'hola'));
		$this->assertFalse(AfiTwilio::enviarSms('  ', 'hola'));
		$this->assertCount(0, $this->pedidos);
	}

	public function testSinCredencialesNoManda() {
		Configure::write('Afigestion.token_auth_sms', '');
		$this->assertFalse(AfiTwilio::enviarSms('+5491122334455', 'hola'));
		$this->assertCount(0, $this->pedidos);
	}

}
