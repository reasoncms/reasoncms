<?php
class DummyDemo {
	public $notSoSecret;

	protected function saySomething() {
		return "YO: ";
	}
}

/**
 * this is a comment about the HelloWorld class
 */
class HelloWorld extends DummyDemo {
	public $identifier;

	/**
	 * Greets the world
	 *
	 * @author Tom Feiler
	 *
	 * @param string $x - the thing to greet
	 *
	 * @return void
	 */
	public function hello($x) {
		echo $this->saySomething() . "hello: " . $this->upperCaser($x) . "\n";
	}

	private function upperCaser($s) {
		return strtoupper($s);
	}
}

// $hw = new HelloWorld();
// $hw->hello("tom");
?>
