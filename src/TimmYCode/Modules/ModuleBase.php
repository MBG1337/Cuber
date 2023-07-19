<?php

namespace TimmYCode\Modules;

use pocketmine\event\Event;
use pocketmine\player\Player;
use TimmYCode\Config\ConfigManager;
use TimmYCode\Discord\Webhook;
use TimmYCode\Modules\Combat\AntiAutoClicker;
use TimmYCode\Modules\Combat\AntiKillaura;
use TimmYCode\Modules\Combat\AntiNoKnockback;
use TimmYCode\Modules\Combat\AntiReach;
use TimmYCode\Modules\Movement\AntiAirJump;
use TimmYCode\Modules\Movement\AntiFly;
use TimmYCode\Modules\Movement\AntiGlide;
use TimmYCode\Modules\Movement\AntiHighJump;
use TimmYCode\Modules\Movement\AntiSpeedA;
use TimmYCode\Modules\Movement\AntiSpeedB;
use TimmYCode\Modules\Movement\AntiStep;
use TimmYCode\Modules\Other\AntiAutoArmor;
use TimmYCode\Modules\Other\AntiInventoryMove;
use TimmYCode\Modules\Other\AntiPacketBlock;
use TimmYCode\Punishment\Notification;
use TimmYCode\SpyOne;
use TimmYCode\Utils\ClientUtil;
use TimmYCode\Utils\PlayerUtil;

class ModuleBase
{
	private bool $active = false;
	private array $modules = array();
	private int $warnings = 0, $notificationCooldown = 30;

	public function registerModules(bool $setup): void
	{
		$this->modules = array(
			"AntiSpeedA" => new AntiSpeedA(),
			"AntiSpeedB" => new AntiSpeedB(),
			"AntiHighJump" => new AntiHighJump(),
			"AntiStep" => new AntiStep(),
			"AntiGlide" => new AntiGlide(),
			"AntiReach" => new AntiReach(),
			"AntiNoKnockback" => new AntiNoKnockback(),
			"AntiKillaura" => new AntiKillaura(),
			"AntiAutoClicker" => new AntiAutoClicker(),
			"AntiAirJump" => new AntiAirJump(),
			"AntiFly" => new AntiFly(),
			"AntiInventoryMove" => new AntiInventoryMove(),
			"AntiAutoArmor" => new AntiAutoArmor(),
			"AntiPacketBlock" => new AntiPacketBlock()
		);

		if ($setup) $this->setupModules();
	}

	public function setupModules(): void
	{
		foreach ($this->modules as $key => $module) {
			if (ConfigManager::getModuleConfiguration($module->getName())["enable"]) {
				$module->activate();
				$module->setup();
			}
		}
	}

	public function activate(): void
	{
		$this->active = true;
	}

	public function deactivate(): void
	{
		$this->active = false;
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function getIgnored(Player $player): bool
	{
		return $player->hasPermission("spyone.ignore");
	}

	public function getModuleList(): array
	{
		return $this->modules;
	}

	public function getModule($moduleName): ?Module
	{
		foreach ($this->modules as $key => $value) {
			if (strcmp($key, $moduleName) == 0) {
				return $value;
			}
		}
		return null;
	}

	public function checkAndFirePunishment(Module $module, Player $player): void
	{
		if ($module->getWarningLimit() <= $this->warnings) {
			ConfigManager::getPunishment($module->getName())->fire($player);
			$this->sendNotifications($module, $player);
			$module->resetWarning();
		}
	}

	private function sendNotifications(Module $module, Player $player): void
	{
		if (PlayerUtil::getlastNotificationServerTick($player) + $this->notificationCooldown >= ClientUtil::getServerTick()) return;

		PlayerUtil::addlastNotificationServerTick($player, ClientUtil::getServerTick());
		if (ConfigManager::getWebhookConfiguration()["enable"]) {
			if (ConfigManager::getWebhookConfiguration()["webhook"] != "") {
				SpyOne::getInstance()->getServer()->getAsyncPool()->submitTask(new Webhook($player->getNameTag(), $module->getName(), PlayerUtil::getPing($player)));
			} else {
				SpyOne::getInstance()->getLogger()->info("You need to set the weebhook url");
			}
		}
		if (ConfigManager::getModuleConfiguration($module->getName())["notify"]) new Notification($player, $module->getName());
	}

	public function getWarning(): int
	{
		return $this->warnings;
	}

	public function setWarning(int $warning): void
	{
		$this->warnings = $warning;
	}

	public function resetWarning(): void
	{
		$this->warnings = 0;
	}

	public function addWarning(int $warning, Player $player): void
	{
		if (!PlayerUtil::recentlyRespawned($player)) $this->warnings += $warning;
	}

	public function checkA(Event $event, Player $player): string
	{
		return "";
	}

	public function checkB(Event $event, Player $player, Player $target): string
	{
		return "";
	}

}
