<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use function date;

class TpsPerTime {
	private ?string $chart_token;
	private ?Main $plugin;
	private string $actual_date = "";

	public function __construct(Main $plugin, string $token, int $send_time) {
		// Verificar o tempo
		if ($send_time < 3600) {
			$plugin->getLogger()->error(TextFormat::RED . "O send_time do tps-per-time requer maior ou igual a 3600.");
			return;
		}

		// Definindo as variáveis.
		$this->chart_token = $token;
		$this->plugin = $plugin;
		$this->actual_date = date("d/m/Y - H:i");

		// Iniciando a task.
		$plugin->getScheduler()->scheduleRepeatingTask(new TpsPerTimeTask($this), 20 * $send_time);
	}

	public function sendRequest(): void {
		API::request([
			$this->actual_date,
			$this->plugin->getServer()->getTicksPerSecond()
		], false, $this->chart_token);
	}
}

class TpsPerTimeTask extends Task {
	private bool $primary_request = false;

	public function __construct(
		private TpsPerTime $owner) {
		//
	}

	public function onRun(): void {
		if ($this->primary_request) {
			$this->owner->sendRequest();
		} else {
			$this->primary_request = true;
		}
	}
}
