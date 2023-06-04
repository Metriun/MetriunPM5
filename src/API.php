<?php

declare(strict_types=1);

namespace Metriun\Metriun;

use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Internet;
use pocketmine\utils\JSON;

use function time;

class API {
	const BASE_URL = "https://api.metriun.com/";

	public static function request(array $data, string|bool $change = false, string $token = ""): array {
		$data = [
			"time" => time(),
			"change" => $change,
			"data" => $data
		];

		$curl = Internet::getURLHandle(self::BASE_URL . "v1/send", [
			"method" => "PUT",
			"content" => JSON::encode($data),
			"header" => [
				"Authorization: Bearer " . $token,
				"Content-Type: application/json"
			],
		]);

		if ($curl === null) {
			Server::getInstance()->getLogger()->warning(TextFormat::YELLOW . "Failed to create cURL handle");
			return [];
		}

		$response = Internet::fetchURL($curl);
		$error = Internet::getURLHandleError($curl);
		Internet::releaseURLHandle($curl);

		if ($error) {
			Server::getInstance()->getLogger()->warning(TextFormat::YELLOW . $error["message"]);
			return JSON::decode($error, true);
		}

		return JSON::decode($response, true);
	}
}
