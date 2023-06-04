<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

use function date;

class PlayersRegistrationsDays implements Listener {
	private ?Config $config;
	private ?string $chart_token;

	private ?int $first_joins = 0;
	private string $actual_date = "";

	public function __construct(Main $plugin, string $token) {
		// Registering event
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		// Defining the variables.
		$this->config = new Config($plugin->getDataFolder() . "data/PlayersRegistrationsDays.yml");
		$this->chart_token = $token;
		$this->actual_date = date("d/m/Y");

		// Starting the task.
		$plugin->getScheduler()->scheduleRepeatingTask(new PlayersRegistrationsDaysTask($this), 20 * 60 * 45);
	}

	public function save(): void {
		// Save the data for the day.
		$this->config->set($this->actual_date, $this->first_joins);
		$this->config->save();
	}

	public function init(): void {
		// Get the stored data for the day.
		$this->first_joins = (int) $this->config->get($this->actual_date, 0);
	}

	public function onJoin(PlayerJoinEvent $ev): void {
		if (!$ev->getPlayer()->hasPlayedBefore()) {
			$this->first_joins++;
		}
	}

	public function sendRequest(): void {
		API::request([
			$this->actual_date,
			$this->first_joins
		], $this->actual_date, $this->chart_token);
	}
}

class PlayersRegistrationsDaysTask extends Task {
	private bool $primary = false;
	private PlayersRegistrationsDays $owner;

	public function __construct(PlayersRegistrationsDays $owner) {
		$this->owner = $owner;
	}

	public function onRun(): void {
		if ($this->primary) {
			$this->owner->save();
			$this->owner->sendRequest();
		} else {
			$this->primary = true;
		}
	}
}
