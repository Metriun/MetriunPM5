<?php

declare(strict_types=1);

namespace Metriun\Metriun\analyzers;

use Metriun\Metriun\API;
use Metriun\Metriun\Main;
use pocketmine\scheduler\Task;

use function count;
use function date;
use function round;

class ServerLatency {
	private ?string $chart_token;

	private ?Main $plugin;

	public function __construct(Main $plugin, string $token) {
		$this->chart_token = $token;
		$this->plugin = $plugin;

		$this->plugin->getScheduler()->scheduleRepeatingTask(new ServerLatencyTask($this), 20 * 60 * 120);
	}

	public function getServerLatency(): int {
		$players = $this->plugin->getServer()->getOnlinePlayers();
		$totalPing = 0;
		$playerCount = count($players);

		foreach ($players as $player) {
			$ping = $player->getNetworkSession()->getAveragePacketRoundTripTime();
			$totalPing += $ping;
		}

		if ($playerCount > 0) {
			$averagePing = $totalPing / $playerCount;
			return (int) round($averagePing);
		}

		return 0;
	}

	public function sendRequest(): void {
		$data = date("m/Y");
		$serverLatency = $this->getServerLatency();

		API::request([$data, $serverLatency], $data, $this->chart_token);
	}
}

class ServerLatencyTask extends Task {
	private bool $_primary = false;

	public function __construct(private ServerLatency $owner) {
	}

	public function onRun(): void {
		if ($this->_primary) {
			$this->owner->sendRequest();
		} else {
			$this->_primary = true;
		}
	}
}
