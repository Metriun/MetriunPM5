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

class VisitsInDay implements Listener {
	private ?Config $config;
	private int $peak_players = 0;
	private ?string $chart_token;
	private string $actual_date = "";

	public function __construct(Main $plugin, string $token) {
		// Registrando evento
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

		// Definindo as variáveis.
		$this->config = new Config($plugin->getDataFolder() . "data/VisitsInDay.yml", Config::YAML);
		$this->chart_token = $token;
		$this->actual_date = date("d/m/Y");

		// Iniciando a task.
		$plugin->getScheduler()->scheduleRepeatingTask(new VisitsInDayTask($this), 20 * 60 * 30);
	}

	public function save(): void {
		// Salvar os dados do dia.
		$this->config->set($this->actual_date, $this->peak_players);
		$this->config->save();
	}

	public function init(): void {
		// Pegar os dados guardados do dia.
		$this->peak_players = $this->config->get($this->actual_date, 0);
	}

	public function onJoin(PlayerJoinEvent $ev): void {
		$this->peak_players++;
	}

	public function sendRequest(): void {
		API::request([
			$this->actual_date,
			$this->peak_players
		], $this->actual_date, $this->chart_token);
	}
}

class VisitsInDayTask extends Task {
	private bool $primary = false;

	public function __construct(
		private VisitsInDay $owner) {
		//
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
