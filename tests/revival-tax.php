<?php

declare(strict_types=1);

namespace pocketmine\event {
	interface Listener {}
}

namespace pocketmine\plugin {
	final class DisablePluginException extends \RuntimeException {}

	final class TestConfig {
		/** @param array<string, mixed> $values */
		public function __construct(private array $values) {}
		public function get(string $key, mixed $default = null) : mixed { return $this->values[$key] ?? $default; }
	}

	final class TestPluginManager {
		public int $registrations = 0;
		public function registerEvents(\pocketmine\event\Listener $listener, PluginBase $plugin) : void {
			++$this->registrations;
		}
	}

	final class TestServer {
		public TestPluginManager $manager;
		public function __construct() { $this->manager = new TestPluginManager(); }
		public function getPluginManager() : TestPluginManager { return $this->manager; }
	}

	class PluginBase {
		/** @var array<string, mixed> */
		public static array $configValues = [];
		public static TestServer $server;
		public function saveDefaultConfig() : void {}
		public function getConfig() : TestConfig { return new TestConfig(self::$configValues); }
		public function getServer() : TestServer { return self::$server; }
	}
}

namespace pocketmine\player {
	final class Player {
		/** @var list<string> */
		public array $messages = [];
		public function getName() : string { return 'Alex'; }
		public function sendMessage(string $message) : void { $this->messages[] = $message; }
	}
}

namespace pocketmine\event\player {
	final class PlayerDeathEvent {
		public function __construct(private \pocketmine\player\Player $player) {}
		public function getPlayer() : \pocketmine\player\Player { return $this->player; }
	}
}

namespace onebone\economyapi {
	final class EconomyAPI {
		public const RET_SUCCESS = 1;
		public const RET_CANCELLED = -2;
		public static self $instance;
		public float|bool $balance = 10.15;
		public int $result = self::RET_SUCCESS;
		/** @var list<array{name: string, amount: float}> */
		public array $reductions = [];
		public static function getInstance() : self { return self::$instance; }
		public function myMoney(\pocketmine\player\Player $player) : float|bool { return $this->balance; }
		public function reduceMoney(string $name, float $amount) : int {
			$this->reductions[] = ['name' => $name, 'amount' => $amount];
			return $this->result;
		}
	}
}

namespace {
	require __DIR__ . '/../src/TaxPolicy.php';
	require __DIR__ . '/../src/Main.php';

	use NhanAZ\RevivalTax\Main;
	use NhanAZ\RevivalTax\TaxPolicy;
	use onebone\economyapi\EconomyAPI;
	use pocketmine\event\player\PlayerDeathEvent;
	use pocketmine\player\Player;
	use pocketmine\plugin\DisablePluginException;
	use pocketmine\plugin\PluginBase;
	use pocketmine\plugin\TestServer;

	function check(bool $condition, string $message) : void {
		if (!$condition) {
			throw new \RuntimeException($message);
		}
	}

	foreach ([false, '10', -1, 101, INF, NAN] as $invalid) {
		try {
			TaxPolicy::validatePercent($invalid);
			throw new \RuntimeException('Invalid percent was accepted');
		} catch (\InvalidArgumentException) {
		}
	}
	check(TaxPolicy::validatePercent(10) === 10.0, 'Integer percent');
	check(TaxPolicy::validatePercent(0.5) === 0.5, 'Fractional percent');
	check(TaxPolicy::calculateTax(10.15, 10.0) === 1.02, 'Two decimal tax');
	check(TaxPolicy::calculateTax(0.99, 10.0) === 0.0, 'Legacy minimum balance');
	check(TaxPolicy::calculateTax(20.0, 0.0) === 0.0, 'Zero percent');

	PluginBase::$configValues = ['taxPercent' => 10, 'taxMessage' => 'Charged {taxMoney} at {taxPercent}%'];
	PluginBase::$server = new TestServer();
	$economy = EconomyAPI::$instance = new EconomyAPI();
	$plugin = new Main();
	(new \ReflectionMethod($plugin, 'onEnable'))->invoke($plugin);
	check(PluginBase::$server->manager->registrations === 1, 'Listener registration');
	$player = new Player();
	$event = new PlayerDeathEvent($player);
	$plugin->onPlayerDeath($event);
	check($economy->reductions === [['name' => 'Alex', 'amount' => 1.02]], 'Successful reduction');
	check($player->messages === ['Charged 1.02 at 10%'], 'Success message');

	$economy->result = EconomyAPI::RET_CANCELLED;
	$plugin->onPlayerDeath($event);
	check(count($player->messages) === 1, 'Cancelled reduction must not claim success');
	$economy->balance = false;
	$plugin->onPlayerDeath($event);
	check(count($economy->reductions) === 2, 'Missing account must not be charged');
	$economy->balance = 0.99;
	$plugin->onPlayerDeath($event);
	check(count($economy->reductions) === 2, 'Small balance must not be charged');

	PluginBase::$configValues['taxPercent'] = 150;
	try {
		$invalidPlugin = new Main();
		(new \ReflectionMethod($invalidPlugin, 'onEnable'))->invoke($invalidPlugin);
		throw new \RuntimeException('Invalid config was accepted');
	} catch (DisablePluginException) {
	}
	check(PluginBase::$server->manager->registrations === 1, 'Invalid config must not register listener');
	PluginBase::$configValues = ['taxMessage' => []];
	try {
		$invalidPlugin = new Main();
		(new \ReflectionMethod($invalidPlugin, 'onEnable'))->invoke($invalidPlugin);
		throw new \RuntimeException('Invalid message was accepted');
	} catch (DisablePluginException) {
	}
	check(PluginBase::$server->manager->registrations === 1, 'Invalid message must not register listener');
	PluginBase::$configValues = [];
	$defaultPlugin = new Main();
	(new \ReflectionMethod($defaultPlugin, 'onEnable'))->invoke($defaultPlugin);
	check(PluginBase::$server->manager->registrations === 2, 'Legacy config defaults');
	echo "PASS: RevivalTax policy and EconomyAPI result handling\n";
}
