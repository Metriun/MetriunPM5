<?php

declare(strict_types=1);

namespace Metriun\Metriun;

use Metriun\Metriun\analyzers\MaxPlayersPerDay;
use Metriun\Metriun\analyzers\PlayerCountry;
use Metriun\Metriun\analyzers\PlayersRegistrationsDays;
use Metriun\Metriun\analyzers\ServerLatency;
use Metriun\Metriun\analyzers\SessionAverage;
use Metriun\Metriun\analyzers\TpsPerTime;
use Metriun\Metriun\analyzers\VisitsInDay;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

use function method_exists;
use function mkdir;

class Main extends PluginBase {
	use SingletonTrait;

	private array $analyzers = [];

	protected function onEnable(): void {
		@mkdir($this->getDataFolder() . "data");

		$this->saveResource("config.yml");
		$this->saveDefaultConfig();

		// Load the analyzers.
		$this->loadAnalyzers();

		foreach ($this->analyzers as $analyzer) {
			if (method_exists($analyzer, "init")) {
				$analyzer->init();
			}
		}
	}

	protected function onDisable(): void {
		foreach ($this->analyzers as $analyzer) {
			if (method_exists($analyzer, "save")) {
				$analyzer->save();
			}
		}
	}

	private function loadAnalyzers(): void {
		$config = $this->getConfig();

		// Maximum players per day.
		if ($config->getNested("max-players-per-day.enable")) {
			$this->analyzers[] = new MaxPlayersPerDay($this, $config->getNested("max-players-per-day.chart_token"));
		}

		// Visitors in the day.
		if ($config->getNested("visits-in-the-day.enable")) {
			$this->analyzers[] = new VisitsInDay($this, $config->getNested("visits-in-the-day.chart_token"));
		}

		// TPS per time.
		if ($config->getNested("tps-per-time.enable")) {
			$this->analyzers[] = new TpsPerTime($this, $config->getNested("tps-per-time.chart_token"), (int) $config->getNested("tps-per-time.send_time"));
		}

		// Session average time per player.
		if ($config->getNested("session-average.enable")) {
			$this->analyzers[] = new SessionAverage($this, $config->getNested("session-average.chart_token"));
		}

		// Player countries.
		if ($config->getNested("player-by-country.enable")) {
			$this->analyzers[] = new PlayerCountry($this, $config->getNested("player-by-country.chart_token"));
		}

		// Server latency.
		if ($config->getNested("server-latency.enable")) {
			$this->analyzers[] = new ServerLatency($this, $config->getNested("server-latency.chart_token"));
		}

		// Player registrations per day.
		if ($config->getNested("player-registration-per-day.enable")) {
			$this->analyzers[] = new PlayersRegistrationsDays($this, $config->getNested("player-registration-per-day.chart_token"));
		}
	}
}
