<?php
class DummyDemo {
	public $notSoSecret;

	protected function saySomething() {
		return "YO: ";
	}
}

class HelloWorld extends DummyDemo {
	public $identifier;

	public function hello($x) {
		echo $this->saySomething() . "hello: " . $this->upperCaser($x) . "\n";
	}

	private function upperCaser($s) {
		return strtoupper($s);
	}
}

$hw = new HelloWorld();
$hw->hello("tom");
?>
