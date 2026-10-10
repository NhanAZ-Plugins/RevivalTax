<?php

declare(strict_types=1);

namespace NhanAZ\RevivalTax;

use InvalidArgumentException;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	private float $taxPercent;
	private string $taxMessage;

	protected function onEnable() : void {
		$this->saveDefaultConfig();
		try {
			$this->taxPercent = TaxPolicy::validatePercent($this->getConfig()->get('taxPercent', 10));
		} catch (InvalidArgumentException $error) {
			throw new DisablePluginException($error->getMessage(), 0, $error);
		}
		$message = $this->getConfig()->get('taxMessage', '§aYou have been deducted §e{taxMoney} §arevival tax! §f(§bTax Percent: §c{taxPercent}%§f)§r');
		if (!is_string($message)) {
			throw new DisablePluginException('taxMessage must be a string');
		}
		$this->taxMessage = $message;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();
		$economy = EconomyAPI::getInstance();
		$balance = $economy->myMoney($player);
		if ($balance === false) {
			return;
		}
		$tax = TaxPolicy::calculateTax((float) $balance, $this->taxPercent);
		if ($tax <= 0.0) {
			return;
		}
		if ($economy->reduceMoney($player->getName(), $tax) !== EconomyAPI::RET_SUCCESS) {
			return;
		}
		$player->sendMessage(strtr($this->taxMessage, [
			'{taxMoney}' => number_format($tax, 2, '.', ''),
			'{taxPercent}' => (string) $this->taxPercent,
		]));
	}
}
